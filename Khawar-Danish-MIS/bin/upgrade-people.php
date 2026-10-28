<?php
if(PHP_SAPI!=='cli')exit('CLI only');
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/Backup.php';
try {
 // Back up using the actual pre-upgrade column layout.
 echo 'Backup: '.tx(fn()=>Backup::create(true)).PHP_EOL;
 foreach(['students','teachers','users'] as $table){
  $columns=q("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
  if(!in_array('photo_path',$columns,true))db()->exec("ALTER TABLE `$table` ADD COLUMN photo_path VARCHAR(40) NULL");
  if($table!=='users'&&!in_array('public_code',$columns,true))db()->exec("ALTER TABLE `$table` ADD COLUMN public_code VARCHAR(32) NULL UNIQUE");
 }
 tx(function(){foreach(['students','teachers'] as $table)foreach(q("SELECT id FROM `$table` WHERE public_code IS NULL")->fetchAll() as $row)ensurePersonCode($table,(int)$row['id']);audit('upgrade_people','settings');});
 echo "People upgrade complete.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
