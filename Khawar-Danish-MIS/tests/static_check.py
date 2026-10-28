"""Static packaging checks only. These do NOT execute or validate PHP/MySQL behavior."""
from pathlib import Path
import re,sys,json
root=Path(__file__).resolve().parents[1]
checks=[]
def check(name,cond):
 checks.append((name,bool(cond)));print(('PASS ' if cond else 'FAIL ')+name)
files=list(root.rglob('*.php'))
check('PHP files start with opening tag',all(p.read_text(encoding='utf-8').startswith('<?php') for p in files))
# Lexical delimiter validation, deliberately not a PHP parser.
def balance(s):
 tokens=[];i=0;stack=[];pairs={')':'(',']':'[','}':'{'}
 while i<len(s):
  c=s[i]
  if s[i:i+2] in ['//','/*'] or c=='#':
   if s[i:i+2]=='/*':
    j=s.find('*/',i+2)
    if j<0:return False
    i=j+2
   else:
    j=s.find('\n',i);i=len(s) if j<0 else j+1
   continue
  if c in ['"',"'"]:
   quote=c;i+=1
   while i<len(s):
    if s[i]=='\\':i+=2;continue
    if s[i]==quote:break
    i+=1
   if i>=len(s):return False
   i+=1;continue
  if c in '([{':stack.append(c)
  elif c in ')]}':
   if not stack or stack.pop()!=pairs[c]:return False
  i+=1
 return not stack
check('PHP lexical quotes/comments/delimiters balanced (not syntax lint)',all(balance(p.read_text(encoding='utf-8')) for p in files))
public=root/'public'
check('No remote asset dependencies in public HTML/CSS/JS or view source',not any(re.search(r'(?:src|href)=[\"\']https?://|@import\s+|url\([\"\']?https?://',p.read_text(encoding='utf-8')) for p in [root/'app/View.php',public/'assets/app.css',public/'assets/app.js']))
check('Local Persian-capable font bundled', (public/'assets/DejaVuSans.ttf').stat().st_size>100000)
check('Font license bundled',(root/'licenses/DejaVu.txt').exists())
check('No production config or credentials packaged',not (root/'config/config.php').exists() and not (root/'config/test.php').exists())
check('Private directories have Apache deny rules',all((root/d/'.htaccess').read_text(encoding='utf-8').strip()=='Require all denied' for d in ['app','config','database','storage','bin','tests']))
check('Single public front controller',(public/'index.php').exists() and not (public/'config.php').exists())
source='\n'.join(p.read_text(encoding='utf-8') for p in files if p.name!='translations.php')
translations=(root/'app/translations.php').read_text(encoding='utf-8');keys=set(re.findall(r"'([^']+)'\s*=>\s*\[",translations,re.M))
literals=set(re.findall(r"\bt\('([^']+)'\)",source));missing=sorted(literals-keys)
check('All literal translation lookups exist',not missing)
if missing: print('Missing:',missing)
forms=set(re.findall(r"formStart\('([^']+)'",source))
index=(public/'index.php').read_text(encoding='utf-8');handlers=set(re.findall(r"case '([^']+)'",index))|{'login','logout','preferences'}
check('Every literal POST form has an action handler',not forms-handlers)
if forms-handlers:print('Missing handlers',forms-handlers)
check('Money schema uses DECIMAL, not FLOAT/DOUBLE',not re.search(r'\b(?:FLOAT|DOUBLE)\b',(root/'database/001_schema.sql').read_text(encoding='utf-8')))
check('Idempotency constraints for cash writes',(root/'database/001_schema.sql').read_text(encoding='utf-8').count('token CHAR(64) NOT NULL UNIQUE')==5)
check('POST CSRF validation is present','verifyCsrf();' in index)
check('Write serialization lock present',"SELECT version FROM schema_versions WHERE version=1 FOR UPDATE" in index)
check('CSV UTF-8 BOM and formula neutralization present','\\xEF\\xBB\\xBF' in index and "[Domain::class,'csv']" in index)
check('PHP unit and DB integration tests bundled',(root/'tests/unit.php').exists() and (root/'tests/integration.php').exists())
check('Schema, demo, CLI installer exist',all((root/p).exists() for p in ['database/001_schema.sql','database/demo.sql','bin/console.php']))
print(f'\n{sum(v for _,v in checks)}/{len(checks)} static checks passed. PHP runtime NOT tested.')
sys.exit(0 if all(v for _,v in checks) else 1)
