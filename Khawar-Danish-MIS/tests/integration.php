<?php
// Uses a dedicated empty database ending in _test. NEVER use production credentials here.
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$testConfig=$root.'/config/test.php';if(!is_file($testConfig))exit("Copy config/config.example.php to config/test.php and use an EMPTY database named khawar_danish_test.\n");
$cfg=require $testConfig;if(!preg_match('/dbname=[a-zA-Z0-9_]+_test(?:;|$)/',$cfg['dsn']))exit("Database name must end in _test.\n");
require $root.'/app/Domain.php';date_default_timezone_set('Asia/Kabul');$pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function db():PDO{global $pdo;return $pdo;}function q(string $s,array $p=[]):PDOStatement{$st=db()->prepare($s);$st->execute($p);return $st;}function one(string $s,array $p=[]):?array{return q($s,$p)->fetch()?:null;}function scalar(string $s,array $p=[]){return q($s,$p)->fetchColumn();}function audit(...$x):void{}function input(string $k,...$x):string{return (string)($_POST[$k]??'');}function id(string $k):int{return (int)$_POST[$k];}function optionalId(string $k):int{return (int)($_POST[$k]??0);}function dateInput(string $k):string{return Domain::date(input($k));}function amount(string $k='amount',bool $positive=true):string{$c=Domain::cents(input($k));if(($positive&&$c<=0)||(!$positive&&$c<0))throw new DomainException('invalid_money');return Domain::money($c);}function requestToken():string{return $_POST['token'];}
require $root.'/app/Finance.php';
require $root.'/app/Academic.php';require $root.'/app/Lookup.php';
function need(string $permission):void{if(!can($permission))throw new DomainException('forbidden');}
function user():array{return ['teacher_id'=>null];}
function can(string $permission):bool{return !($GLOBALS['deny_lookup']??false);}
function sid(int $id):string{return 'KD-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);}
function t(string $key):string{return $key;}
function expect($a,$b,$label){if($a!==$b)throw new RuntimeException("FAIL $label: ".var_export($a,true));echo "PASS $label\n";}
if((int)scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')>0)exit("Test DB must be empty. The script never drops existing tables.\n");
db()->exec(file_get_contents($root.'/database/001_schema.sql'));db()->exec(file_get_contents($root.'/database/demo.sql'));db()->beginTransaction();
try{
 $student=one('SELECT * FROM students WHERE id=1');
 expect((int)lookupRows('students','KD-000001')[0]['id'],1,'search by student code');
 expect((int)lookupRows('students','',false,1)[0]['id'],1,'exact selected student');
 expect(count(lookupRows('students','%_!')),0,'literal wildcard search');
 $GLOBALS['deny_lookup']=true;
 try{lookupRows('students','');throw new RuntimeException('Unauthorized search allowed');}catch(DomainException $e){expect($e->getMessage(),'forbidden','search permission enforced');}
 $GLOBALS['deny_lookup']=false;
 $_POST=['student_id'=>1,'batch_id'=>1,'start_date'=>'2026-01-10','fee'=>'0'];
 try{Academic::enroll();throw new RuntimeException('Duplicate enrollment allowed');}catch(DomainException $e){expect($e->getMessage(),'already_processed','duplicate enrollment friendly validation');}
 $_POST=['student_id'=>1,'account_id'=>1,'amount'=>'1000.00','date'=>'2026-01-10','reference'=>'TEST','token'=>str_repeat('a',64)];$rid=Finance::receipt();expect(Finance::chargeBalance(1),50000,'partial payment balance');expect(Finance::receipt(),$rid,'idempotent duplicate receipt');expect((int)scalar('SELECT COUNT(*) FROM receipts'),1,'one receipt only');
 $_POST=['charge_id'=>1,'amount'=>'100.00','reason'=>'Scholarship'];Finance::discount();expect(Finance::chargeBalance(1),40000,'discount balance');
 $calc=Finance::calculate(1,'2026-01');expect($calc['fixed'],100000,'fixed payroll');expect($calc['variable'],20000,'20 percent on collected tuition');
 $_POST=['teacher_id'=>1,'period'=>'2026-01'];Finance::approve();expect((int)scalar('SELECT COUNT(*) FROM payroll'),1,'approved payroll');try{Finance::approve();throw new RuntimeException('Duplicate approval allowed');}catch(DomainException $e){echo "PASS duplicate approval denied\n";}
 $feb=Finance::calculate(1,'2026-02');expect($feb['variable'],0,'allocation not counted twice');
 $_POST=['receipt_id'=>$rid,'allocation_id'=>1,'account_id'=>1,'amount'=>'200.00','date'=>'2026-02-10','reason'=>'Refund','token'=>str_repeat('b',64)];Finance::refund();expect(Finance::chargeBalance(1),60000,'refund reopens charge');$feb=Finance::calculate(1,'2026-02');expect($feb['variable'],-4000,'refund payroll adjustment');expect(Finance::accountBalance(1,'2026-02-28'),80000,'cash after refund');
 $_POST=['kind'=>'expense','account_id'=>1,'category'=>'Rent','amount'=>'50.00','date'=>'2026-02-11','reason'=>'Test rent','token'=>str_repeat('c',64)];Finance::movement();expect(Finance::accountBalance(1,'2026-02-28'),75000,'expense cash effect');
 $_POST=['movement_id'=>1,'date'=>'2026-02-12','reason'=>'Correction','token'=>str_repeat('d',64)];Finance::reverse();expect(Finance::accountBalance(1,'2026-02-28'),80000,'reversal restores cash');
 $_POST=['payroll_id'=>1,'account_id'=>1,'amount'=>'500.00','date'=>'2026-02-15','token'=>str_repeat('e',64)];Finance::pay();Finance::pay();expect((int)scalar('SELECT COUNT(*) FROM payroll_payments'),1,'duplicate salary blocked');expect(Finance::accountBalance(1,'2026-02-28'),30000,'salary counted once');
 db()->rollBack();echo "PASS scenario. Test tables and demo remain; transaction changes rolled back. Drop test database manually before rerun.\n";
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
