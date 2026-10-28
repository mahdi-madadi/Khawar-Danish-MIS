<?php
declare(strict_types=1);
require __DIR__.'/Domain.php';
date_default_timezone_set('Asia/Kabul');
ini_set('display_errors','0');ini_set('log_errors','1');ini_set('error_log',dirname(__DIR__).'/storage/logs/php.log');
$configFile=dirname(__DIR__).'/config/config.php';
if(!is_file($configFile)){http_response_code(503);exit('ابتدا راهنمای نصب README_FA.md را بخوانید. / Follow README_EN.md to configure and initialize the database.');}
$config=require $configFile;
session_name('KDSESSION');session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);ini_set('session.use_strict_mode','1');session_start();
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('Cache-Control: no-store');
function db(): PDO {static $p;global $config;if(!$p)$p=new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);return $p;}
function q(string $sql,array $args=[]): PDOStatement {$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]): ?array {return q($sql,$args)->fetch()?:null;}
function scalar(string $sql,array $args=[]){return q($sql,$args)->fetchColumn();}
function tx(callable $fn){db()->beginTransaction();try{$r=$fn();db()->commit();return $r;}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}}
function audit(string $action,string $entity,?int $id=null,array $details=[]): void {q('INSERT INTO audit(actor_id,action,entity,record_id,details) VALUES(?,?,?,?,?)',[$_SESSION['uid']??null,$action,$entity,$id,json_encode($details,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);}
function e($v): string {return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function lang(): string {return $_SESSION['lang']??'fa';}
function cal(): string {return $_SESSION['calendar']??'j';}
function t(string $k): string {static $dict;if(!$dict)$dict=require __DIR__.'/translations.php';return $dict[$k][lang()==='en'?0:1]??$k;}
function settings(): array {static $s;if(!$s)$s=q('SELECT name,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);return $s;}
function setting(string $k,string $default=''): string {return settings()[$k]??$default;}
function user(): ?array {static $u=false;if($u!==false)return $u;$u=isset($_SESSION['uid'])?one('SELECT u.*,r.permissions FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.active=1 AND ((u.employee_id IS NULL AND u.role_id=1) OR EXISTS(SELECT 1 FROM employees em WHERE em.id=u.employee_id AND em.status=\'active\'))',[$_SESSION['uid']]):null;if($u && (int)$u['auth_version']!==($_SESSION['av']??0))$u=null;return $u;}
function can(string $p): bool {return user()&&Domain::allowed(json_decode(user()['permissions'],true)??[],$p);}
function need(string $p): void {if(!can($p)){http_response_code(403);throw new DomainException('forbidden');}}
function scopedClass(int $id): array {$r=one('SELECT cs.*,b.start_date,b.end_date,b.name batch_name,s.name subject_name,t.name teacher_name FROM class_subjects cs JOIN batches b ON b.id=cs.batch_id JOIN subjects s ON s.id=cs.subject_id JOIN teachers t ON t.id=cs.teacher_id WHERE cs.id=?',[$id]);if(!$r)throw new DomainException('not_found');if(user()['teacher_id'] && (int)user()['teacher_id']!==(int)$r['teacher_id'] && !can('*'))throw new DomainException('forbidden');return $r;}
function csrf(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function verifyCsrf(): void {if(!is_string($_POST['csrf']??null)||!hash_equals(csrf(),$_POST['csrf']))throw new DomainException('csrf_error');}
function token(): string {return bin2hex(random_bytes(32));}
function requestToken(): string {$t=(string)($_POST['token']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$t))throw new DomainException('invalid_input');return $t;}
function input(string $key,int $max=255,bool $required=true): string {$raw=$_POST[$key]??'';if(!is_scalar($raw))throw new DomainException('invalid_input');$v=trim((string)$raw);if(($required&&$v==='')||strlen($v)>$max*4)throw new DomainException('invalid_input');return $v;}
function id(string $key): int {$v=Domain::digits((string)($_POST[$key]??$_GET[$key]??''));if(!ctype_digit($v)||(int)$v<1)throw new DomainException('invalid_input');return (int)$v;}
function optionalId(string $key): int {$raw=$_POST[$key]??'';if(!is_scalar($raw))throw new DomainException('invalid_input');$v=trim(Domain::digits((string)$raw));if($v==='')return 0;if(!ctype_digit($v))throw new DomainException('invalid_input');return (int)$v;}
function amount(string $key='amount',bool $positive=true): string {$c=Domain::cents(input($key));if(($positive&&$c<=0)||(!$positive&&$c<0))throw new DomainException('invalid_money');return Domain::money($c);}
function dateInput(string $key): string {return Domain::date(input($key),cal());}
function go(string $route,array $args=[]): never {header('Location: ?'.http_build_query(['r'=>$route]+$args));exit;}
function flash(string $msg): void {$_SESSION['flash']=$msg;}
function fdate(?string $v): string {return $v?Domain::displayDate($v,cal()):'';}
function sid(int $id): string {return personCode('students',$id);}
require __DIR__.'/People.php';
function logError(Throwable $e): void {error_log(get_class($e).': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());}
