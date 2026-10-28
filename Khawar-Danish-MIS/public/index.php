<?php
declare(strict_types=1);
$root=dirname(__DIR__);
if(is_file($root.'/storage/maintenance.lock')){http_response_code(503);exit('در حال بازیابی / Maintenance');}
require $root.'/app/bootstrap.php';
require $root.'/app/Workflow.php';require $root.'/app/Lookup.php';require $root.'/app/Finance.php';require $root.'/app/Academic.php';require $root.'/app/Admin.php';require $root.'/app/Backup.php';require $root.'/app/View.php';require $root.'/app/pages.php';
try { db(); if((int)scalar('SELECT MAX(version) FROM schema_versions')!==1)throw new RuntimeException('Schema version'); } catch(Throwable $e){logError($e);http_response_code(503);exit('اتصال دیتابیس یا نصب را بررسی کنید. / Check database configuration, services and installation. See README.');}
$route=(string)($_GET['r']??'dashboard');$started=false;
try {
 if(isset($_SESSION['uid'])&&time()-($_SESSION['last']??0)>(int)$config['session_minutes']*60){$_SESSION=[];session_regenerate_id(true);flash(t('session_expired'));go('login');}
 if(isset($_SESSION['uid']))$_SESSION['last']=time();
 if($_SERVER['REQUEST_METHOD']==='POST'){
  verifyCsrf();$verified=true;$action=(string)($_POST['action']??'');
  if($action==='login'){
   $username=input('username',80);$key=hash('sha256',strtolower($username));$ip=hash('sha256','ip:'.($_SERVER['REMOTE_ADDR']??''));
   $u=tx(function()use($username,$key,$ip){q('SELECT version FROM schema_versions WHERE version=1 FOR UPDATE');if((int)scalar('SELECT COUNT(*) FROM login_attempts WHERE login_key IN (?,?) AND happened_at > DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$key,$ip])>=10)return null;$u=one('SELECT * FROM users WHERE username=? AND active=1 AND ((employee_id IS NULL AND role_id=1) OR EXISTS(SELECT 1 FROM employees em WHERE em.id=users.employee_id AND em.status=\'active\'))',[$username]);if(!$u||!password_verify((string)($_POST['password']??''),$u['password_hash'])){q('INSERT INTO login_attempts(login_key,happened_at) VALUES(?,NOW()),(?,NOW())',[$key,$ip]);audit('login_failed','auth');return null;}q('DELETE FROM login_attempts WHERE login_key=?',[$key]);return $u;});
   if(!$u)throw new DomainException('login_failed');session_regenerate_id(true);$_SESSION['uid']=(int)$u['id'];$_SESSION['av']=(int)$u['auth_version'];$_SESSION['last']=time();$_SESSION['csrf']=token();audit('login','users',(int)$u['id']);go('dashboard');
  }
  if(!user())go('login');
  if($action==='logout'){audit('logout','users',(int)user()['id']);$_SESSION=[];session_destroy();go('login');}
  if($action==='preferences'){$_SESSION['lang']=($_POST['lang']??'')==='en'?'en':'fa';$_SESSION['calendar']=($_POST['calendar']??'')==='g'?'g':'j';$args=$_GET;unset($args['r'],$args['date'],$args['from'],$args['to'],$args['month']);go($route,$args);}
  $requestToken=requestToken();$processed=$_SESSION['processed'][$requestToken]??null;
  $payload=$_POST;if(isset($_FILES['photo'])&&($_FILES['photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$photo=$_FILES['photo'];$payload['_photo']=['error'=>$photo['error'],'hash'=>is_string($photo['tmp_name']??null)&&is_uploaded_file($photo['tmp_name'])?hash_file('sha256',$photo['tmp_name']):null];}unset($payload['csrf'],$payload['token'],$payload['next_step']);ksort($payload);$fingerprint=hash('sha256',json_encode($payload,JSON_THROW_ON_ERROR));
  if($processed&&$processed['uid']===(int)user()['id']&&$processed['action']===$action){
   if(($processed['fingerprint']??'')!==$fingerprint){$_POST['token']=token();throw new DomainException('saved_form_changed');}
   flash(t('already_saved'));go($processed['redirect'][0],$processed['redirect'][1]);
  }
  $redirect=tx(function()use($action){
   // A single InnoDB row serializes all writes, including duplicate submissions and capacity checks.
   q('SELECT version FROM schema_versions WHERE version=1 FOR UPDATE');
   switch($action){
    case 'save_module':$module=input('module');$savedId=Academic::save($module);if($module==='students'&&($_POST['next_step']??'')==='enroll')return ['enrollments',['student_id'=>$savedId,'focus'=>'enroll']];if($module==='assessments')return ['marks',['id'=>$savedId]];return $module==='students'?['student',['id'=>$savedId]]:[$module,[]];
    case 'enroll':need('students.create');Academic::enroll();$student=id('student_id');if(($_POST['next_step']??'')==='payment'&&can('finance.create'))return ['finance',['student_id'=>$student,'focus'=>'receipt']];return ['student',['id'=>$student]];
    case 'close_enrollment':need('students.update');Academic::closeEnrollment();return ['enrollments',[]];
    case 'teaching_group':need('academic.create');createTeachingGroup();return ['batches',[]];
    case 'timetable':need('academic.create');Academic::timetable();return ['timetable',[]];
    case 'attendance':need('attendance.create');Academic::attendance();return ['attendance',['class_subject_id'=>id('class_subject_id'),'date'=>input('date')]];
    case 'marks':need('exams.create');Academic::marks();return ['marks',['id'=>id('assessment_id')]];
    case 'receipt':need('finance.create');$id=Finance::receipt();return ['receipt',['id'=>$id]];
    case 'charge':need('finance.create');Finance::charge();$student=(int)scalar('SELECT student_id FROM enrollments WHERE id=?',[id('enrollment_id')]);return ['finance',['student_id'=>$student,'focus'=>'receipt']];
    case 'discount':need('finance.discount');Finance::discount();return ['finance',[]];
    case 'allocate':need('finance.create');$id=id('receipt_id');Finance::allocate($id);audit('allocate','receipts',$id);return ['receipt',['id'=>$id]];
    case 'refund':need('finance.refund');Finance::refund();return ['receipt',['id'=>id('receipt_id')]];
    case 'movement':need('finance.create');Finance::movement();return ['reports',[]];
    case 'reverse':need('finance.refund');Finance::reverse();return ['reports',[]];
    case 'agreement':need('payroll.create');Finance::agreement();return ['payroll',[]];
    case 'approve_payroll':need('payroll.create');Finance::approve();return ['payroll',['teacher_id'=>id('teacher_id'),'period'=>input('period'),'payroll_id'=>(int)scalar('SELECT id FROM payroll WHERE teacher_id=? AND period=?',[id('teacher_id'),input('period')]),'focus'=>'payroll_pay']];
    case 'payroll_pay':need('payroll.create');Finance::pay();return ['payroll',[]];
    case 'reconcile':need('finance.create');$id=id('account_id');$date=dateInput('date');q('INSERT INTO reconciliations(account_id,date,expected,counted,note) VALUES(?,?,?,?,?)',[$id,$date,Domain::money(Finance::accountBalance($id,$date)),amount('counted',false),input('note',2000,false)]);audit('reconcile','accounts',$id);return ['reports',[]];
    case 'account':need('settings.manage');q('INSERT INTO accounts(name,opening_balance) VALUES(?,?)',[input('name',100),amount('opening_balance',false)]);audit('create','accounts',(int)db()->lastInsertId());return ['reports',[]];
    case 'certificate':need('exams.create');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');Academic::certificate();return ['certificates',[]];
    case 'revoke':need('exams.create');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');$id=id('certificate_id');q('UPDATE certificates SET revoked=1,reason=? WHERE id=?',[input('reason'),$id]);audit('revoke','certificates',$id);return ['certificates',[]];
    case 'certificate_print':need('print');need('exams.view');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');$id=id('certificate_id');q('UPDATE certificates SET print_count=print_count+1 WHERE id=?',[$id]);audit('record_print','certificates',$id);return ['document',['type'=>'certificate','id'=>$id]];
    case 'approve_employee_payroll':need('payroll.create');$pay=EmployeePayroll::approve();return ['employee_payroll',['employee_id'=>id('employee_id'),'period'=>input('period'),'employee_payroll_id'=>$pay]];
    case 'employee_pay':need('payroll.create');EmployeePayroll::pay();return ['employee_payroll',['employee_payroll_id'=>id('employee_payroll_id')]];
    case 'user':Admin::users();return ['users',[]];
    case 'role':Admin::roles();return ['users',[]];
    case 'settings':Admin::settings();return ['settings',[]];
    case 'backup':need('backup.manage');Backup::create();return ['backup',[]];
    default:throw new DomainException('invalid_input');
   }
  });
  $_SESSION['processed'][$requestToken]=['uid'=>(int)user()['id'],'action'=>$action,'redirect'=>$redirect,'fingerprint'=>$fingerprint];
  if(count($_SESSION['processed'])>100)$_SESSION['processed']=array_slice($_SESSION['processed'],-100,null,true);
  if($action==='backup'){$files=glob($root.'/storage/backups/KD-*.zip');rsort($files);foreach(array_slice($files,(int)setting('backup_retention','10')) as $f)unlink($f);}
  flash(t('saved'));go($redirect[0],$redirect[1]);
 }
 if(!user()){
  if($route!=='login')go('login');pageStart('login');$started=true;panel('login');formStart('login');field('username');field('password','password');formEnd('login');echo '<p class="muted">'.e(t('password_help')).'</p>';endPanel();pageEnd();exit;
 }
 if($route==='photo')servePersonPhoto();
 if($route==='student_balance'){
  $summary=studentBalanceSummary(id('student_id'));header('Content-Type: application/json; charset=UTF-8');echo json_encode($summary,JSON_THROW_ON_ERROR);exit;
 }
 if($route==='lookup'){
  $rows=lookupRows((string)($_GET['kind']??''),(string)($_GET['q']??''),($_GET['active']??'')==='1');
  header('Content-Type: application/json; charset=UTF-8');echo json_encode(['rows'=>array_slice($rows,0,20),'more'=>count($rows)>20],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
 }
 if($route==='backup_download'){
  need('backup.manage');$name=(string)($_GET['file']??'');if(!preg_match('/^KD-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.zip$/D',$name))throw new DomainException('not_found');$f=$root.'/storage/backups/'.$name;if(!is_file($f))throw new DomainException('not_found');audit('download','backup');header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($f));readfile($f);exit;
 }
 if($route==='export'){
  need('finance.view');need('export');$from=Domain::date((string)($_GET['from']??''),cal());$to=Domain::date((string)($_GET['to']??''),cal());header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="cash-report.csv"');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,[t('category'),'AFN'],',','"','');foreach(reportData($from,$to) as $r)fputcsv($out,array_map([Domain::class,'csv'],$r),',','"','');exit;
 }
 if(isset($_GET['print']))need('print');
 authorizeRoute($route);pageStart($route);$started=true;renderRoute($route);pageEnd();
}catch(Throwable $e){
 if(db()->inTransaction())db()->rollBack();logError($e);
 if(!$started)http_response_code($e instanceof DomainException?(in_array($e->getMessage(),['forbidden','csrf_error'],true)?403:($e->getMessage()==='not_found'?404:422)):500);
 if(in_array($route,['lookup','student_balance'],true)){http_response_code($e instanceof DomainException&&$e->getMessage()==='forbidden'?403:400);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['error'=>t('lookup_error')]);exit;}
 $message=$e instanceof DomainException?t($e->getMessage()):t('system_error');
 if(!$started&&!empty($verified)&&$e instanceof DomainException&&!in_array($e->getMessage(),['forbidden','csrf_error','login_failed'],true)&&user()){
  http_response_code(422);$GLOBALS['replay']=true;pageStart($route);$started=true;
  echo '<div class="notice error" role="alert" tabindex="-1" data-form-error>'.e($message).'<p>'.e(t('values_kept')).'</p></div>';
  try{renderRoute($route);}catch(Throwable $renderError){logError($renderError);echo '<div class="notice error">'.e(t('system_error')).'</div>';}
  pageEnd();exit;
 }
 if(!$started){pageStart('error');$started=true;}echo '<section class="panel"><div class="notice error" role="alert">'.e($message).'</div>'.linkTo('dashboard','dashboard').'<p class="muted">'.e(t('retry_hint')).'</p></section>';pageEnd();
}
