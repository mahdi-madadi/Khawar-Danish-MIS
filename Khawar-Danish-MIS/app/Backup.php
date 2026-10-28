<?php
final class Backup {
 static function tables(): array {preg_match_all('/CREATE TABLE (\w+)/',file_get_contents(dirname(__DIR__).'/database/001_schema.sql'),$m);return $m[1];}
 static function create(bool $existingOnly=false): string {
  if(!class_exists('ZipArchive'))throw new DomainException('zip_missing');
  $root=dirname(__DIR__);$tables=self::tables();if($existingOnly){$present=q('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);$tables=array_values(array_intersect($tables,$present));}$data=['format'=>'KD1','schema'=>1,'created'=>date(DATE_ATOM),'tables'=>[]];foreach($tables as $t){$order=$t==='settings'?'name':($t==='schema_versions'?'version':'id');$data['tables'][$t]=q("SELECT * FROM `$t` ORDER BY `$order`")->fetchAll();}
  $file=$root.'/storage/backups/KD-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';$zip=new ZipArchive();if($zip->open($file,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Cannot open backup');$json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$zip->addFromString('database.json',$json);$hashes=['database.json'=>hash('sha256',$json)];
  foreach(glob($root.'/storage/uploads/*') as $f){if(is_file($f)&&preg_match('/^[a-f0-9]{32}\.(png|jpg)$/D',basename($f))){$name='uploads/'.basename($f);$zip->addFile($f,$name);$hashes[$name]=hash_file('sha256',$f);}}$zip->addFromString('manifest.json',json_encode($hashes,JSON_THROW_ON_ERROR));if(!$zip->close())throw new RuntimeException('Cannot finish backup');audit('backup','backup');return $file;
 }
 static function restore(string $file): void {
  if(PHP_SAPI!=='cli')throw new RuntimeException('Local CLI only');$root=dirname(__DIR__);$z=new ZipArchive();if($z->open($file)!==true)throw new RuntimeException('Invalid ZIP');
  if($z->numFiles>5000)throw new RuntimeException('Too many entries');$total=0;for($i=0;$i<$z->numFiles;$i++){$st=$z->statIndex($i);$total+=$st['size'];if($total>128*1024*1024)throw new RuntimeException('Backup exceeds 128 MiB restore limit');}
  $manifest=json_decode($z->getFromName('manifest.json')?:'',true,512,JSON_THROW_ON_ERROR);if(!is_array($manifest)||!isset($manifest['database.json']))throw new RuntimeException('Manifest missing');$files=[];
  foreach($manifest as $name=>$hash){if($name!=='database.json'&&!preg_match('~^uploads/[a-f0-9]{32}\.(png|jpg)$~D',$name))throw new RuntimeException('Unsafe path');$bytes=$z->getFromName($name);if($bytes===false||!hash_equals($hash,hash('sha256',$bytes)))throw new RuntimeException('Checksum mismatch');$files[$name]=$bytes;}
  $data=json_decode($files['database.json'],true,512,JSON_THROW_ON_ERROR);if(($data['format']??null)!=='KD1'||($data['schema']??null)!==1||!is_array($data['tables']??null)||array_diff(array_keys($data['tables']),self::tables()))throw new RuntimeException('Schema mismatch');
  foreach(self::tables() as $table)if(!array_key_exists($table,$data['tables'])){if(!in_array($table,['employees','employee_payroll','employee_payroll_payments'],true))throw new RuntimeException('Missing table');$data['tables'][$table]=[];}
  $orderedTables=[];foreach(self::tables() as $table)$orderedTables[$table]=$data['tables'][$table];$data['tables']=$orderedTables;
  foreach(self::tables() as $t){$cols=q("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);foreach($data['tables'][$t] as &$row){
   if(in_array($t,['students','teachers','employees'],true)&&($row['tazkira_type']??'not_available')!=='paper')foreach(['tazkira_volume','tazkira_page','tazkira_registration'] as $key)$row[$key]=null;
   if(!in_array('tazkira_district',$cols,true))unset($row['tazkira_district']);
   $ordered=[];foreach($cols as $col){if(!array_key_exists($col,$row)){if(in_array($col,['photo_path','public_code','employee_id'],true)||str_starts_with($col,'tazkira_')||($t==='subjects'&&$col==='program_id'))$row[$col]=$col==='tazkira_type'?'not_available':null;else throw new RuntimeException('Column mismatch');}$ordered[$col]=$row[$col];}
   if(array_diff(array_keys($row),$cols))throw new RuntimeException('Column mismatch');$row=$ordered;
   if(array_keys($row)!==$cols)throw new RuntimeException('Column mismatch');
  }unset($row);}
  if(!array_filter($data['tables']['users'],fn($u)=>(int)$u['role_id']===1&&(int)$u['active']===1))throw new RuntimeException('No active administrator');
  usort($data['tables']['movements'],fn($a,$b)=>(int)$a['id']<=>(int)$b['id']);
  // Add immutable uploaded files before committing DB; harmless orphan files on failure.
  foreach($files as $name=>$bytes)if($name!=='database.json'){if(file_put_contents($root.'/storage/'.$name,$bytes)===false)throw new RuntimeException('Upload write failed');}
  foreach(array_reverse(self::tables()) as $t){if($t==='movements'){foreach(q('SELECT id FROM movements ORDER BY id DESC')->fetchAll() as $r)q('DELETE FROM movements WHERE id=?',[$r['id']]);}else q("DELETE FROM `$t`");}
  foreach(self::tables() as $t)foreach($data['tables'][$t] as $row){q("INSERT INTO `$t` (`".implode('`,`',array_keys($row)).'`) VALUES('.implode(',',array_fill(0,count($row),'?')).')',array_values($row));}
  foreach(['students','teachers','employees'] as $kind)foreach(q("SELECT id FROM `$kind` WHERE public_code IS NULL")->fetchAll() as $person)ensurePersonCode($kind,(int)$person['id']);
  q('UPDATE users SET auth_version=auth_version+1');audit('restore','backup');$z->close();
 }
}
