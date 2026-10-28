<?php
final class Admin {
 static function users(): void {
  need('users.manage');$uid=optionalId('id');$role=id('role_id');$active=isset($_POST['active'])?1:0;$old=$uid?one('SELECT * FROM users WHERE id=?',[$uid]):null;if($uid&&!$old)throw new DomainException('not_found');
  if(!one('SELECT id FROM roles WHERE id=?',[$role]))throw new DomainException('not_found');
  if($role===4)throw new DomainException('staff_accounts_only');
  $primary=$old&&(int)$old['role_id']===1&&!$old['employee_id'];$employee=$primary?0:optionalId('employee_id');
  if(!$primary&&!$employee)throw new DomainException('employee_required');
  if($employee){$em=one('SELECT * FROM employees WHERE id=?',[$employee]);if(!$em||($active&&$em['status']!=='active'))throw new DomainException('employee_inactive');if(scalar('SELECT COUNT(*) FROM users WHERE employee_id=? AND id<>?',[$employee,$uid]))throw new DomainException('employee_has_user');$name=$em['name'];}
  else{$name=input('name',160);if($role!==1)throw new DomainException('last_admin');}
  if($old&&(int)$old['role_id']===1&&(!$active||$role!==1)&&(int)scalar('SELECT COUNT(*) FROM users WHERE active=1 AND role_id=1')<=1)throw new DomainException('last_admin');
  $username=input('username',80);if(scalar('SELECT COUNT(*) FROM users WHERE username=? AND id<>?',[$username,$uid]))throw new DomainException('username_taken');
  if($uid)q('UPDATE users SET name=?,username=?,role_id=?,employee_id=?,teacher_id=NULL,active=?,auth_version=auth_version+1 WHERE id=?',[$name,$username,$role,$employee?:null,$active,$uid]);
  else{q('INSERT INTO users(name,username,role_id,employee_id,active,password_hash) VALUES(?,?,?,?,?,?)',[$name,$username,$role,$employee,$active,self::hash()]);$uid=(int)db()->lastInsertId();}
  if(!empty($_POST['password']))q('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[self::hash(),$uid]);
  if(!$employee)savePhoto('users',$uid);audit('manage','users',$uid,['employee_id'=>$employee?:null]);
 }
 static function hash(): string {$p=(string)($_POST['password']??'');if(strlen($p)<12||strlen($p)>72)throw new DomainException('password_length');return password_hash($p,PASSWORD_DEFAULT);}
 static function roles(): void {
  need('users.manage');$rid=optionalId('id');if($rid===1)throw new DomainException('last_admin');$allowed=self::permissions();$perms=$_POST['permissions']??[];foreach($perms as $p)if(!in_array($p,$allowed,true))throw new DomainException('invalid_input');
  if($rid)q('UPDATE roles SET name=?,permissions=? WHERE id=?',[input('name',100),json_encode(array_values($perms)),$rid]);else{q('INSERT INTO roles(name,permissions) VALUES(?,?)',[input('name',100),json_encode(array_values($perms))]);$rid=(int)db()->lastInsertId();}q('UPDATE users SET auth_version=auth_version+1 WHERE role_id=?',[$rid]);audit('manage','roles',$rid);
 }
 static function permissions(): array {return ['employees.view','employees.create','employees.update','students.view','students.create','students.update','academic.view','academic.create','academic.update','attendance.view','attendance.create','exams.view','exams.create','exams.update','finance.view','finance.create','finance.discount','finance.refund','payroll.view','payroll.create','users.manage','settings.manage','backup.manage','audit.view','print','export'];}
 static function settings(): void {
  need('settings.manage');foreach(['center_name','center_name_en','address','phone','footer','closure_days','low_attendance','backup_retention'] as $k){$v=input($k,1000,false);if(in_array($k,['closure_days','low_attendance','backup_retention'],true))$v=Domain::digits($v);if($k==='closure_days'&&!preg_match('/^[0-6](,[0-6])*$/D',$v))throw new DomainException('invalid_input');if($k==='low_attendance'&&(!ctype_digit($v)||(int)$v>100))throw new DomainException('invalid_input');if($k==='backup_retention'&&(!ctype_digit($v)||(int)$v<1||(int)$v>100))throw new DomainException('invalid_input');q('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$k,$v]);}audit('update','settings');
 }
}
