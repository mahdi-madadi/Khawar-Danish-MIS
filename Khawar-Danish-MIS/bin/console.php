<?php
// Never expose this directory as a web root.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);require $root.'/app/bootstrap.php';require $root.'/app/Backup.php';
function ask(string $prompt): string {echo $prompt;return trim(fgets(STDIN));}
try {
 $command=$argv[1]??'help';
 if($command==='install'){
  if(scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='users'"))throw new RuntimeException('Database already initialized. No data changed.');
  echo "Importing schema into configured EMPTY database.\n";if(ask('Type INSTALL: ')!=='INSTALL')exit(1);
  db()->exec(file_get_contents($root.'/database/001_schema.sql'));
  echo "Schema imported. Run: php bin/console.php admin\n";
 }elseif($command==='admin'){
  if((int)scalar('SELECT COUNT(*) FROM users')>0)throw new RuntimeException('Use reset-admin for an existing installation.');
  $name=ask('Administrator name: ');$username=ask('Username: ');echo "Password will be visible in this local console. Use a private room.\n";$password=ask('New password (12–72 bytes): ');if(strlen($password)<12||strlen($password)>72||!$username||!$name)throw new RuntimeException('Invalid input');
  tx(function()use($name,$username,$password){q('SELECT version FROM schema_versions WHERE version=1 FOR UPDATE');if(scalar('SELECT COUNT(*) FROM users'))throw new RuntimeException('Already initialized');q('INSERT INTO users(name,username,password_hash,role_id) VALUES(?,?,?,1)',[$name,$username,password_hash($password,PASSWORD_DEFAULT)]);audit('initialize','users',(int)db()->lastInsertId());});echo "Administrator created.\n";
 }elseif($command==='reset-admin'){
  $username=ask('Existing administrator username: ');$u=one('SELECT * FROM users WHERE username=? AND role_id=1',[$username]);if(!$u)throw new RuntimeException('Administrator not found');if(ask('Type RESET to change this password: ')!=='RESET')exit(1);$p=ask('New password (visible; 12–72 bytes): ');if(strlen($p)<12||strlen($p)>72)throw new RuntimeException('Invalid password length');tx(function()use($p,$u){q('UPDATE users SET password_hash=?,active=1,auth_version=auth_version+1 WHERE id=?',[password_hash($p,PASSWORD_DEFAULT),$u['id']]);q('DELETE FROM login_attempts');audit('local_password_reset','users',(int)$u['id']);});echo "Password reset. Existing sessions invalidated.\n";
 }elseif($command==='backup'){$f=tx(fn()=>Backup::create());echo "Backup: $f\n";
 }elseif($command==='restore'){
  $file=$argv[2]??'';if(!is_file($file))throw new RuntimeException('Provide backup ZIP path');if(ask('Restore REPLACES all data. Stop Apache first. Type RESTORE: ')!=='RESTORE')exit(1);file_put_contents($root.'/storage/maintenance.lock',date(DATE_ATOM));try{$pre=tx(fn()=>Backup::create());echo "Pre-restore backup: $pre\n";tx(fn()=>Backup::restore($file));echo "Restored. Restart Apache.\n";}finally{@unlink($root.'/storage/maintenance.lock');}
 }elseif($command==='demo'){
  if(scalar('SELECT COUNT(*) FROM students')||scalar('SELECT COUNT(*) FROM batches')||scalar('SELECT COUNT(*) FROM teachers')||scalar('SELECT COUNT(*) FROM subjects')||scalar('SELECT COUNT(*) FROM programs')||scalar('SELECT COUNT(*) FROM rooms'))throw new RuntimeException('Demo requires empty academic data');if(ask('Insert fictional demo data? Type DEMO: ')!=='DEMO')exit(1);tx(fn()=>db()->exec(file_get_contents($root.'/database/demo.sql')));echo "Demo imported.\n";
 }else echo "Commands: install | admin | reset-admin | backup | restore BACKUP.zip | demo\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
