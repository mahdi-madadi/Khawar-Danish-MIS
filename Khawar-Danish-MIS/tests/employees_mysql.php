<?php
// Integration checks use a dedicated, newly created database ending in _test.
declare(strict_types=1);
$root=dirname(__DIR__);$configPath=$root.'/config/test.php';
if(!is_file($configPath)){fwrite(STDERR,"Create config/test.php for a dedicated EMPTY database ending in _test.\n");exit(1);}
$cfg=require $configPath;if(!preg_match('/dbname=([a-zA-Z0-9_]+_test)(?:;|$)/',$cfg['dsn'])){fwrite(STDERR,"Test database name must end in _test.\n");exit(1);}
$pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()>0){fwrite(STDERR,"Refusing a nonempty test database.\n");exit(1);}
function db():PDO{global $pdo;return $pdo;}
function q(string $sql,array $args=[]):PDOStatement{$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]):?array{return q($sql,$args)->fetch()?:null;}
function scalar(string $sql,array $args=[]){return q($sql,$args)->fetchColumn();}
function tx(callable $fn){db()->beginTransaction();try{$r=$fn();db()->commit();return $r;}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}}
function audit(string $action,string $entity,?int $id=null,array $details=[]):void{q('INSERT INTO audit(action,entity,record_id,details) VALUES(?,?,?,?)',[$action,$entity,$id,json_encode($details)]);}
function input(string $key,int $max=255,bool $required=true):string{$v=trim((string)($_POST[$key]??''));if($required&&$v==='')throw new DomainException('invalid_input');return $v;}
function id(string $key):int{return (int)($_POST[$key]??$_GET[$key]??0);}
function optionalId(string $key):int{return (int)($_POST[$key]??0);}
function amount(string $key='amount',bool $positive=true):string{$v=Domain::cents(input($key));if(($positive&&$v<=0)||(!$positive&&$v<0))throw new DomainException('invalid_money');return Domain::money($v);}
function requestToken():string{return $_POST['token'];}
function dateInput(string $key):string{return Domain::date(input($key),'g');}
function cal():string{return 'g';}
function can(string $p):bool{return true;}
function need(string $p):void{}
function user():array{return ['id'=>1,'teacher_id'=>null];}
function test($a,$b,string $label):void{if($a!==$b)throw new RuntimeException($label.' failed: '.var_export($a,true));echo 'PASS '.$label.PHP_EOL;}
function rejects(callable $fn,string $label):void{try{$fn();}catch(DomainException $e){echo 'PASS '.$label.PHP_EOL;return;}throw new RuntimeException('Expected rejection: '.$label);}
require $root.'/app/Domain.php';require $root.'/app/People.php';require $root.'/app/Academic.php';require $root.'/app/Finance.php';require $root.'/app/Admin.php';require $root.'/app/Backup.php';
date_default_timezone_set('Asia/Kabul');
$backupFile=null;
try {
 db()->exec(file_get_contents($root.'/database/001_schema.sql'));
 $hash=password_hash('integration-test-password',PASSWORD_DEFAULT);q('INSERT INTO users(id,username,name,password_hash,role_id) VALUES(1,?,?,?,1)',['testadmin','Preserved Administrator',$hash]);
 q("INSERT INTO employees(name,father_name,job_title,joining_date,monthly_salary,tazkira_type,tazkira_number) VALUES('Test employee','Test father','Accountant','2026-08-16',5000,'paper','TEST-1')");
 $employee=(int)db()->lastInsertId();
 test(EmployeePayroll::calculate($employee,'2026-08')['total'],'2580.65','MySQL prorated August salary');
 $_POST=['employee_id'=>$employee,'period'=>'2026-08'];$pay=tx(fn()=>EmployeePayroll::approve());
 rejects(fn()=>tx(fn()=>EmployeePayroll::approve()),'duplicate closed salary month rejected');
 $_POST=['employee_payroll_id'=>$pay,'account_id'=>1,'amount'=>'1000','date'=>'2026-09-01','token'=>str_repeat('a',64),'reference'=>'Part one'];
 tx(fn()=>EmployeePayroll::pay());tx(fn()=>EmployeePayroll::pay());test((int)scalar('SELECT COUNT(*) FROM employee_payroll_payments'),1,'payment token prevents double posting');
 test(EmployeePayroll::balance($pay),158065,'remaining salary after partial payment');
 $_POST['amount']='2000';$_POST['token']=str_repeat('b',64);rejects(fn()=>tx(fn()=>EmployeePayroll::pay()),'salary overpayment rejected');
 test(Finance::accountBalance(1,'2026-09-30'),-100000,'employee payment changes cash once');
 q('UPDATE employees SET monthly_salary=6000 WHERE id=?',[$employee]);test((string)scalar('SELECT total FROM employee_payroll WHERE id=?',[$pay]),'2580.65','approved snapshot survives salary edit');
 $_POST=['role_id'=>3,'name'=>'Forged name','username'=>'staff','password'=>'test-staff-password','active'=>'1'];rejects(fn()=>tx(fn()=>Admin::users()),'unlinked user rejected');
 $_POST['employee_id']=$employee;tx(fn()=>Admin::users());test((string)scalar("SELECT name FROM users WHERE username='staff'"),'Test employee','user name comes from employee record');
 $_POST['username']='staff2';rejects(fn()=>tx(fn()=>Admin::users()),'second account for same employee rejected');
 foreach(['students','teachers','employees'] as $table)db()->exec("ALTER TABLE $table ADD COLUMN tazkira_district VARCHAR(160) NULL");
 q("INSERT INTO students(name,father_name,registered_date,tazkira_type,tazkira_number,tazkira_volume,tazkira_page,tazkira_registration,tazkira_district) VALUES('Legacy identity','Test father','2026-07-01','electronic','TEST-E','old-volume','old-page','old-registration','old-district')");
 $backupFile=tx(fn()=>Backup::create());
 foreach(['students','teachers','employees'] as $table)db()->exec("ALTER TABLE $table DROP COLUMN tazkira_district");
 // The reset script must work with real foreign keys, and preserve the password.
 $sql=file_get_contents($root.'/database/RESET_KEEP_ADMIN.sql');
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $statement){if(trim($statement)==='')continue;$cursor=db()->query($statement);if($cursor->columnCount())$cursor->fetchAll();$cursor->closeCursor();}
 test((int)scalar('SELECT COUNT(*) FROM users'),1,'reset preserves only administrator');
 test((string)scalar('SELECT password_hash FROM users WHERE id=1'),$hash,'reset leaves administrator password unchanged');
 foreach(['employees','employee_payroll','employee_payroll_payments','students','teachers','receipts','audit'] as $table)test((int)scalar("SELECT COUNT(*) FROM $table"),0,'reset clears '.$table);
 test((int)scalar('SELECT COUNT(*) FROM roles'),5,'reset restores standard roles');test((int)scalar('SELECT COUNT(*) FROM accounts'),1,'reset creates empty cash account');
 tx(fn()=>Backup::restore($backupFile));test((int)scalar('SELECT COUNT(*) FROM employees'),1,'backup restores employee records');test((int)scalar('SELECT COUNT(*) FROM employee_payroll_payments'),1,'backup restores administrative payments');
 test((string)scalar('SELECT total FROM employee_payroll WHERE id=?',[$pay]),'2580.65','backup preserves salary snapshot');
 test((int)scalar("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='tazkira_district'"),0,'removed district not recreated by legacy restore');
 test(scalar('SELECT tazkira_volume FROM students LIMIT 1'),null,'legacy electronic paper-only metadata cleared on restore');
 echo "ALL MYSQL EMPLOYEE/RESET/BACKUP CHECKS PASSED\n";
} finally {
 if(db()->inTransaction())db()->rollBack();
 // Only the checked, originally empty _test database is cleaned up.
 foreach(array_reverse(Backup::tables()) as $table)db()->exec("DROP TABLE IF EXISTS $table");if($backupFile&&is_file($backupFile))unlink($backupFile);
}
