<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
if(!in_array('--confirm=RESET',$argv,true)){fwrite(STDERR,"Explicit --confirm=RESET is required. Export a backup first.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/Backup.php';
try {
 $admin=one('SELECT * FROM users WHERE role_id=1 AND active=1 ORDER BY id LIMIT 1');if(!$admin)throw new RuntimeException('No active administrator. No data changed.');
 echo 'PRE_RESET_BACKUP='.tx(fn()=>Backup::create()).PHP_EOL;
 $sql=file_get_contents(dirname(__DIR__).'/database/RESET_KEEP_ADMIN.sql');
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $statement){if(trim($statement)==='')continue;$cursor=db()->query($statement);if($cursor->columnCount())$cursor->fetchAll();$cursor->closeCursor();}
 $after=one('SELECT * FROM users WHERE id=?',[$admin['id']]);
 foreach(['id','name','username','password_hash','photo_path','active','role_id'] as $key)if((string)($after[$key]??'')!==(string)$admin[$key])throw new RuntimeException('Administrator preservation verification failed');
 foreach(Backup::tables() as $table){$count=(int)scalar("SELECT COUNT(*) FROM $table");echo $table.'='.$count.PHP_EOL;if(!in_array($table,['schema_versions','roles','settings','users','accounts'],true)&&$count)throw new RuntimeException('Unexpected remaining records');}
 if((int)scalar('SELECT COUNT(*) FROM users')!==1)throw new RuntimeException('User reset mismatch');
 $directory=realpath(dirname(__DIR__).'/storage/uploads');$removed=0;
 if($directory)foreach(glob($directory.'/*') as $file){
  if(!is_file($file)||!preg_match('/^[a-f0-9]{32}\.(png|jpg)$/D',basename($file))||basename($file)===($admin['photo_path']??''))continue;
  $resolved=realpath($file);if(!$resolved||dirname($resolved)!==$directory)throw new RuntimeException('Unexpected upload path');
  if(!unlink($resolved))throw new RuntimeException('Could not remove obsolete photo');$removed++;
 }
 echo 'REMOVED_OLD_PHOTOS='.$removed.PHP_EOL;
 echo 'PRESERVED_ADMIN_ID='.$admin['id'].PHP_EOL;
 echo "RESET_COMPLETE_ADMIN_CREDENTIALS_UNCHANGED\n";
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
