/* Browser/HTTP regression suite for a DISPOSABLE fixture from prepare_ui_fixture.py.
   Set KD_PHP, KD_FIXTURE, KD_NODE_MODULES and optionally KD_BROWSER / KD_SCREENSHOTS.
   SQLite UI tests do not replace MySQL integration.php. No production server is used. */
const assert=require('node:assert/strict');
const path=require('node:path');
const fs=require('node:fs');
const {spawn}=require('node:child_process');
const dependencies=process.env.KD_NODE_MODULES||path.join(process.env.USERPROFILE,'.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules');
const {chromium}=require(path.join(dependencies,'playwright'));
const fixture=path.resolve(process.env.KD_FIXTURE||process.argv[2]),php=path.resolve(process.env.KD_PHP||path.join(__dirname,'../../tmp/php-runtime/php.exe'));
const port=Number(process.env.KD_UI_PORT||18765),base=`http://127.0.0.1:${port}/`;
const server=spawn(php,['-d',`extension_dir=${path.dirname(php)}/ext`,'-d','extension=pdo_sqlite','-d',`session.save_path=${fixture}/storage/sessions`,'-S',`127.0.0.1:${port}`,'-t',path.join(fixture,'public')],{windowsHide:true,stdio:['ignore','pipe','pipe']});
let serverLog='';server.stderr.on('data',chunk=>serverLog+=chunk);
const passed=[];
const check=(label)=>{passed.push(label);console.log('PASS '+label);};
const route=(r,args={})=>base+'?'+new URLSearchParams({r,...args});
const formData=async form=>Object.fromEntries(await form.evaluate(f=>Array.from(new FormData(f).entries()).filter(([,v])=>typeof v==='string')));
const pause=ms=>new Promise(resolve=>setTimeout(resolve,ms));
(async()=>{
 let browser;
 try{
  for(let i=0;i<50;i++){try{await fetch(base);break;}catch{await pause(100);}}
  browser=await chromium.launch({headless:true,executablePath:process.env.KD_BROWSER||'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage();const errors=[];
  page.on('pageerror',error=>errors.push(error.message));
  await page.goto(base);
  await page.locator('[name="username"]').fill('uiadmin');await page.locator('[name="password"]').fill('test-password-123');
  await Promise.all([page.waitForURL('**r=dashboard'),page.locator('form[data-action="login"] button[type="submit"]').click()]);
  check('login and local assets');
  const prefs=page.locator('form[data-action="preferences"]');await prefs.locator('[name="calendar"]').selectOption('g');
  await Promise.all([page.waitForURL('**r=dashboard'),prefs.locator('button[type="submit"]').click()]);
  for(const [r,args] of [['dashboard',{}],['students',{}],['students',{new:1}],['student',{id:1}],['enrollments',{}],['teachers',{}],['programs',{}],['subjects',{}],['batches',{}],['class_subjects',{}],['rooms',{}],['timetable',{}],['holidays',{}],['attendance',{}],['attendance',{class_subject_id:1,date:'2026-10-20'}],['register',{class_subject_id:1,month:'2026-10'}],['assessments',{}],['marks',{id:1}],['results',{class_subject_id:1}],['finance',{}],['payroll',{}],['reports',{}],['certificates',{}],['users',{}],['settings',{}],['backup',{}],['audit',{}]]){
   const response=await page.goto(route(r,args));assert.equal(response.status(),200,r);assert.equal(await page.locator('.notice.error').count(),0,r+' render');
   assert.equal(await page.locator('form form').count(),0,r+' nested form');
  }
  check('27 routes/forms render with permissions intact');
  await page.goto(route('students',{new:1}));let student=page.locator('form[data-action="save_module"]');
  await student.locator('[name="name"]').fill('شاگرد جریان تازه');await student.locator('[name="father_name"]').fill('پدر جریان تازه');await student.locator('[name="phone"]').fill('0712345678');
  // Simulate a tampered client: native date controls reject this date themselves.
  await student.locator('[name="registered_date"]').evaluate(el=>el.type='text');
  await student.locator('[name="registered_date"]').fill('2026-02-31');
  await student.locator('button[value="enroll"]').click();await page.waitForSelector('[data-form-error]');
  student=page.locator('form[data-action="save_module"]');assert.equal(await student.locator('[name="name"]').inputValue(),'شاگرد جریان تازه');assert.equal(await student.locator('[name="registered_date"]').getAttribute('value'),'2026-02-31');
  check('validation errors preserve entered fields and raw dates');
  await student.locator('[name="registered_date"]').fill('2026-10-26');const studentPayload=await formData(student);studentPayload.next_step='enroll';
  await Promise.all([page.waitForURL('**r=enrollments**'),student.locator('button[value="enroll"]').click()]);
  let enroll=page.locator('form[data-action="enroll"]');const studentId=await enroll.locator('[name="student_id"]').inputValue();assert.ok(Number(studentId)>37);
  const duplicate=await page.request.post(route('students',{new:1}),{form:studentPayload});assert.equal(duplicate.status(),200);
  let data=await (await page.request.get(route('lookup',{kind:'students',q:'شاگرد جریان تازه'}))).json();assert.equal(data.rows.length,1);
  check('save-and-enroll carries student; replay does not create a duplicate');
  data=await (await page.request.get(route('lookup',{kind:'students',q:''}))).json();assert.equal(data.rows.length,20);assert.equal(data.more,true);
  data=await (await page.request.get(route('lookup',{kind:'enrollments',q:'English'}))).json();assert.equal(data.rows.length,2);
  check('bounded search and enrollment search by course');
  await enroll.locator('[data-kind="batches"] .lookup-query').fill('English');await enroll.locator('[data-kind="batches"] .lookup-result').first().waitFor();await enroll.locator('[data-kind="batches"] .lookup-result').first().click();assert.equal(await enroll.locator('[name="batch_id"]').inputValue(),'1');await enroll.locator('[name="start_date"]').fill('2026-10-26');await enroll.locator('[name="fee"]').fill('4500.00');
  await Promise.all([page.waitForURL('**r=finance**'),enroll.locator('button[value="payment"]').click()]);
  let receipt=page.locator('form[data-action="receipt"]');assert.equal(await receipt.locator('[name="student_id"]').inputValue(),studentId);
  await page.waitForFunction(()=>document.querySelector('[data-balance-preview]').textContent.includes('4500.00'));
  await receipt.locator('[name="account_id"]').selectOption('1');await receipt.locator('[name="amount"]').fill('1000.00');await receipt.locator('[name="date"]').fill('2026-10-26');const receiptPayload=await formData(receipt);
  await Promise.all([page.waitForURL('**r=receipt**'),receipt.locator('button[type="submit"]').click()]);
  const receiptId=new URL(page.url()).searchParams.get('id');assert.ok(Number(receiptId)>0);
  await page.request.post(route('finance',{student_id:studentId}),{form:receiptPayload});
  data=await (await page.request.get(route('student_balance',{student_id:studentId}))).json();assert.deepEqual(data,{receivables:'3500.00',advance:'0.00'});
  check('enroll-and-pay, live balances and once-only receipt submission');
  await page.goto(route('finance',{student_id:studentId}));receipt=page.locator('form[data-action="receipt"]');
  await receipt.locator('.lookup-query').fill('نمونه');await page.waitForSelector('.lookup-result');await receipt.locator('.lookup-query').press('ArrowDown');await page.keyboard.press('Enter');
  assert.ok(await receipt.locator('[name="student_id"]').inputValue());
  check('keyboard selection of database search results');
  const movement=page.locator('form[data-action="movement"]');await page.locator('#panel-other_movement>summary').click();
  assert.equal(await movement.locator('[name="destination_id"]').isDisabled(),true);await movement.locator('[name="kind"]').selectOption('transfer');assert.equal(await movement.locator('[name="destination_id"]').isEnabled(),true);
  await page.goto(route('enrollments',{enrollment_id:1,focus:'close_transfer'}));const close=page.locator('form[data-action="close_enrollment"]');
  assert.equal(await close.locator('[name="batch_id"]').isDisabled(),true);await close.locator('[name="status"]').selectOption('transferred');assert.equal(await close.locator('[name="batch_id"]').isEnabled(),true);
  check('transfer-only and destination-only fields appear when relevant');
  await close.locator('[name="status"]').selectOption('withdrawn');page.once('dialog',dialog=>dialog.dismiss());await close.locator('button[type="submit"]').click();await pause(100);assert.equal(await close.locator('button[type="submit"]').isEnabled(),true);check('canceling confirmation leaves the form usable');
  await page.goto(route('attendance',{class_subject_id:1,date:'2026-10-26'}));const statuses=page.locator('.attendance-status');assert.equal(await statuses.count(),3);
  await statuses.nth(2).selectOption('absent');await page.locator('[data-bulk="present"]').click();assert.equal(await statuses.nth(2).inputValue(),'absent');assert.equal(await statuses.nth(1).inputValue(),'present');
  await page.locator('[data-undo-bulk]').click();assert.equal(await statuses.nth(1).inputValue(),'');assert.equal(await statuses.nth(2).inputValue(),'absent');
  await page.locator('[data-roster-search]').fill('شاگرد جریان تازه');await page.locator('[data-bulk="late"]').click();assert.equal(await statuses.nth(2).inputValue(),'absent');assert.equal(await statuses.nth(0).inputValue(),'late');
  check('bulk attendance preserves existing/filtered rows and supports undo');
  await page.locator('[data-roster-search]').fill('');await page.locator('[data-bulk="present"]').click();await page.locator('form[data-action="attendance"] button[type="submit"]').click();await page.waitForLoadState('networkidle');assert.equal(await page.locator('.notice.error').count(),0);
  assert.equal(await page.locator('.attendance-status').nth(2).inputValue(),'absent');check('attendance submits and reloads all rows');
  await page.goto(route('marks',{id:1}));const markRow=page.locator('.roster-table tbody tr').first();await markRow.locator('select').selectOption('absent');assert.equal(await markRow.locator('input').isDisabled(),true);await markRow.locator('select').selectOption('graded');assert.equal(await markRow.locator('input').isEnabled(),true);
  check('absent/exempt marks do not require a score');
  await markRow.locator('input').fill('999');await page.locator('.roster-table tbody tr').nth(1).locator('input').fill('85');await page.locator('form[data-action="marks"] button[type="submit"]').click();await page.waitForSelector('[data-form-error]');assert.equal(await page.locator('.roster-table tbody tr').first().locator('input').inputValue(),'999');
  await page.locator('.roster-table tbody tr').first().locator('input').fill('90');const marksPayload=await formData(page.locator('form[data-action="marks"]'));await page.locator('form[data-action="marks"] button[type="submit"]').click();await page.waitForLoadState('networkidle');assert.equal(await page.locator('.notice.error').count(),0);check('marks validation preserves rows and corrected marks save');
  marksPayload['marks[1]']='80';const changedReplay=await page.request.post(route('marks',{id:1}),{form:marksPayload});assert.equal(changedReplay.status(),422);check('changed entries on an old saved form are flagged for review, never silently discarded');
  await page.locator('form[data-action="preferences"] [name="lang"]').selectOption('en');await page.locator('form[data-action="preferences"] button[type="submit"]').click();await page.waitForURL('**r=marks**');assert.equal(new URL(page.url()).searchParams.get('id'),'1');
  check('preferences retain the current workflow');
  if(process.env.KD_SCREENSHOTS){
   fs.mkdirSync(process.env.KD_SCREENSHOTS,{recursive:true});
   await page.goto(route('students',{new:1}));await page.locator('form[data-action="preferences"] [name="lang"]').selectOption('fa');await page.locator('form[data-action="preferences"] button[type="submit"]').click();await page.waitForLoadState('networkidle');
   for(const [name,r,args] of [['student-desktop','student',{id:studentId}],['enrollment-desktop','enrollments',{student_id:studentId}],['finance-desktop','finance',{student_id:studentId}]]){await page.goto(route(r,args));await page.waitForTimeout(150);await page.screenshot({path:path.join(process.env.KD_SCREENSHOTS,name+'.png'),fullPage:true});}
   await page.setViewportSize({width:390,height:844});await page.goto(route('enrollments',{student_id:studentId}));await page.screenshot({path:path.join(process.env.KD_SCREENSHOTS,'enrollment-mobile.png'),fullPage:true});
   assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile horizontal overflow');
   await page.locator('[data-menu]').click();assert.equal(await page.locator('[data-menu]').getAttribute('aria-expanded'),'true');
   check('mobile layout, menu and screenshot capture');
  }
  const teacherContext=await browser.newContext(),teacher=await teacherContext.newPage();await teacher.goto(base);await teacher.locator('[name="username"]').fill('uiteacher');await teacher.locator('[name="password"]').fill('test-password-123');const [deniedLogin]=await Promise.all([teacher.waitForResponse(r=>r.request().method()==='POST'),teacher.locator('form[data-action="login"] button[type="submit"]').click()]);assert.equal(deniedLogin.status(),422);assert.equal(await teacher.locator('.notice.error').count(),1);
  for(const [r,args] of [['lookup',{kind:'receipts',q:''}],['student_balance',{student_id:1}],['students',{}],['marks',{id:2}]]){const response=await teacher.request.get(route(r,args));assert.match(response.url(),/r=login/);}
  check('legacy teacher account cannot sign in; administrative employment is required');
  assert.deepEqual(errors,[],'browser JavaScript exceptions');check('no JavaScript exceptions');
  console.log(`ALL ${passed.length} UI CHECKS PASSED`);
 }catch(error){console.error(error);console.error(serverLog.slice(-8000));process.exitCode=1;}
 finally{await browser?.close();server.kill();}
})();
