<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/Backup.php';
try {
 echo 'PRE_TAZKIRA_BACKUP='.tx(fn()=>Backup::create()).PHP_EOL;
 $counts=[];foreach(['students','teachers','employees','users'] as $table)$counts[$table]=(int)scalar("SELECT COUNT(*) FROM $table");
 foreach(['students','teachers','employees'] as $table){
  $columns=q("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
  if(in_array('tazkira_district',$columns,true))db()->exec("ALTER TABLE $table DROP COLUMN tazkira_district");
 }
 tx(function(){foreach(['students','teachers','employees'] as $table)q("UPDATE $table SET tazkira_volume=NULL,tazkira_page=NULL,tazkira_registration=NULL WHERE tazkira_type<>'paper'");audit('refine_tazkira','settings');});
 foreach($counts as $table=>$before)if($before!==(int)scalar("SELECT COUNT(*) FROM $table"))throw new RuntimeException('Record count verification failed');
 echo "EXISTING_RECORDS_PRESERVED\n";
 echo "TAZKIRA_FIELDS_UPDATED\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
