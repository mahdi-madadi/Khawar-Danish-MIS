<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/Backup.php';
try {
 echo 'PRE_UPGRADE_BACKUP='.tx(fn()=>Backup::create(true)).PHP_EOL;
 $schema=file_get_contents(dirname(__DIR__).'/database/001_schema.sql');
 foreach(['employees','employee_payroll','employee_payroll_payments'] as $table){
  if(!preg_match('/CREATE TABLE '.preg_quote($table,'/').' \(.*?\) ENGINE=InnoDB;/s',$schema,$match))throw new RuntimeException('Missing table definition');
  db()->exec(str_replace('CREATE TABLE '.$table,'CREATE TABLE IF NOT EXISTS '.$table,$match[0]));
 }
 foreach(['students','teachers'] as $table){
  $columns=q("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
  $types=['tazkira_type'=>"VARCHAR(20) NOT NULL DEFAULT 'not_available'",'tazkira_number'=>'VARCHAR(160) NULL','tazkira_volume'=>'VARCHAR(160) NULL','tazkira_page'=>'VARCHAR(160) NULL','tazkira_registration'=>'VARCHAR(160) NULL','tazkira_province'=>'VARCHAR(160) NULL','tazkira_issue_date'=>'DATE NULL'];
  foreach($types as $column=>$definition)if(!in_array($column,$columns,true))db()->exec("ALTER TABLE $table ADD COLUMN $column $definition");
 }
 $columns=q('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
 if(!in_array('employee_id',$columns,true))db()->exec('ALTER TABLE users ADD COLUMN employee_id INT NULL UNIQUE, ADD CONSTRAINT fk_user_employee FOREIGN KEY(employee_id) REFERENCES employees(id)');
 audit('upgrade_employees','settings');echo "EMPLOYEE_SCHEMA_READY\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
