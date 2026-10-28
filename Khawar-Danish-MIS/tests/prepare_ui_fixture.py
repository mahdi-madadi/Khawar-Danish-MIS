"""Create a disposable SQLite-backed UI fixture. Never points at a user's database.

Usage: python tests/prepare_ui_fixture.py --php PATH --out NEW_DIRECTORY
The copy adapts MySQL syntax for SQLite solely to exercise HTTP/browser behavior.
This is NOT a MySQL integration test; use integration.php for that.
"""
import argparse, re, shutil, sqlite3, subprocess
from pathlib import Path

parser=argparse.ArgumentParser()
parser.add_argument('--php',required=True)
parser.add_argument('--out',required=True)
args=parser.parse_args()
root=Path(__file__).resolve().parents[1]
out=Path(args.out).resolve()
if out.exists():
    raise SystemExit('Refusing to overwrite an existing fixture directory')
out.mkdir(parents=True)
for folder in ['app','public']:
    shutil.copytree(root/folder,out/folder)
for folder in ['config','storage/logs','storage/backups','storage/sessions']:
    (out/folder).mkdir(parents=True)
schema=(root/'database/001_schema.sql').read_text(encoding='utf-8')
schema=re.sub(r'\b(?:INT|BIGINT) AUTO_INCREMENT PRIMARY KEY\b','INTEGER PRIMARY KEY AUTOINCREMENT',schema)
schema=re.sub(r',\s*INDEX\([^)]*\)','',schema)
schema=schema.replace(' ENGINE=InnoDB','')
connection=sqlite3.connect(out/'fixture.sqlite')
connection.executescript(schema)
connection.executescript((root/'database/demo.sql').read_text(encoding='utf-8'))
connection.execute("INSERT INTO assessments(class_subject_id,name,date,max_marks,pass_marks,weight) VALUES(2,'Other teacher exam','2026-10-20',100,50,1)")
password=subprocess.check_output([args.php,'-r',"echo password_hash('test-password-123', PASSWORD_DEFAULT);"],text=True)
connection.execute('INSERT INTO users(id,username,name,password_hash,role_id) VALUES(1,?,?,?,1)',('uiadmin','UI Administrator',password))
connection.execute('INSERT INTO users(id,username,name,password_hash,role_id,teacher_id) VALUES(2,?,?,?,4,1)',('uiteacher','UI Teacher',password))
for number in range(3,38):
    connection.execute('INSERT INTO students(id,name,father_name,registered_date,phone) VALUES(?,?,?,?,?)',(number,f'شاگرد آزمایشی {number}','پدر آزمایشی','2026-01-01',f'070000{number:04d}'))
connection.commit();connection.close()
config="<?php return ['dsn'=>'sqlite:"+(out/'fixture.sqlite').as_posix()+"','user'=>null,'password'=>null,'session_minutes'=>60];"
(out/'config/config.php').write_text(config,encoding='utf-8')
bootstrap=out/'app/bootstrap.php'
source=bootstrap.read_text(encoding='utf-8')
a=source.index('function db(): PDO');b=source.index('function one(',a)
source=source[:a]+'''function db(): PDO {
 static $pdo;global $config;
 if(!$pdo){$pdo=new PDO($config['dsn'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $pdo->sqliteCreateFunction('CONCAT',fn(...$args)=>implode('',$args));
 $pdo->sqliteCreateFunction('LPAD',fn($s,$n,$pad)=>str_pad((string)$s,(int)$n,$pad,STR_PAD_LEFT));}
 return $pdo;
}
function q(string $sql,array $args=[]): PDOStatement {
 $sql=str_replace([' FOR UPDATE','DATE_SUB(NOW(),INTERVAL 15 MINUTE)','NOW()'],['',"datetime('now','-15 minutes')","datetime('now')"],$sql);
 $sql=preg_replace('/ON DUPLICATE KEY UPDATE/','ON CONFLICT DO UPDATE SET',$sql);
 $sql=preg_replace('/VALUES\\((\\w+)\\)/','excluded.$1',$sql);
 $st=db()->prepare($sql);$st->execute($args);return $st;
}
'''+source[b:]
bootstrap.write_text(source,encoding='utf-8')
print('Disposable UI fixture created:',out)
