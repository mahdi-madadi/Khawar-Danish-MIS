<?php
// Presentation/workflow helpers. Financial and academic rules remain in their services.
function authorizeRoute(string $route): void {
 $modules=require __DIR__.'/modules.php';
 if(isset($modules[$route])){
  need($modules[$route]['permission'].'.view');
  if(isset($_GET['new'])||isset($_GET['edit']))need($modules[$route]['permission'].((int)($_GET['id']??0)?'.update':'.create'));
 }else{
  $permissions=['student'=>'students.view','enrollments'=>'students.view','timetable'=>'academic.view','attendance'=>'attendance.view','register'=>'attendance.view','forms'=>'print','attendance_sheet'=>'attendance.view','marks'=>'exams.view','results'=>'exams.view','result_card'=>'exams.view','finance'=>'finance.view','receipt'=>'finance.view','payroll'=>'payroll.view','employee_payroll'=>'payroll.view','employee_payslip'=>'payroll.view','employee'=>'employees.view','reports'=>'finance.view','certificates'=>'exams.view','document'=>'print','users'=>'users.manage','settings'=>'settings.manage','backup'=>'backup.manage','audit'=>'audit.view'];
  if(isset($permissions[$route]))need($permissions[$route]);elseif($route!=='dashboard')throw new DomainException('not_found');
 }
 if(user()['teacher_id']&&!can('*')&&!in_array($route,['dashboard','class_subjects','timetable','attendance','assessments','results','result_card','marks','register','attendance_sheet'],true))throw new DomainException('forbidden');
 if(in_array($route,['register','attendance_sheet','result_card','employee_payslip'],true))need('print');
 if($route==='marks'){$assessment=one('SELECT class_subject_id FROM assessments WHERE id=?',[(int)($_GET['id']??0)]);if(!$assessment)throw new DomainException('not_found');scopedClass((int)$assessment['class_subject_id']);}
 if(in_array($route,['attendance','results','register','attendance_sheet','result_card'],true)&&!empty($_GET['class_subject_id']))scopedClass((int)$_GET['class_subject_id']);
}
function renderRoute(string $route): void {
 $modules=require __DIR__.'/modules.php';
 if($route==='batches'&&!isset($_GET['edit'])){teachingGroupsPage();return;}
 if(isset($modules[$route])){modulePage($route);return;}
 $pages=['dashboard'=>'dashboardPage','student'=>'studentPage','enrollments'=>'enrollmentsPage','timetable'=>'timetablePage','attendance'=>'attendancePage','register'=>'registerPage','forms'=>'formsPage','attendance_sheet'=>'attendanceSheetPage','marks'=>'marksPage','results'=>'resultsPage','result_card'=>'resultCardPage','finance'=>'financePage','receipt'=>'receiptPage','payroll'=>'payrollPage','employee_payroll'=>'employeePayrollPage','employee_payslip'=>'employeePayslipPage','employee'=>'employeePage','reports'=>'reportsPage','certificates'=>'certificatesPage','document'=>'documentPage','users'=>'usersPage','settings'=>'settingsPage','backup'=>'backupPage','audit'=>'auditPage'];
 if(!isset($pages[$route]))throw new DomainException('not_found');
 $pages[$route]();
}
function replaying(): bool {return !empty($GLOBALS['replay'])&&($GLOBALS['form_action']??'')===($_POST['action']??'');}
function formValue(string $key,$fallback='') {
 if(!replaying()||in_array($key,['password','csrf'],true))return $fallback;
 return $_POST[$key]??$fallback;
}
function formChecked(string $key,bool $fallback=false): bool {return replaying()?isset($_POST[$key]):$fallback;}
function workflowLinks(string $step,?int $student=null): void {
 echo '<nav class="workflow no-print" aria-label="'.e(t('daily_workflow')).'">';
 foreach([['students','register_student','students.create',['new'=>1]],['enrollments','new_enrollment','students.create',$student?['student_id'=>$student,'focus'=>'enroll']:[]],['finance','collect_payment','finance.create',$student?['student_id'=>$student,'focus'=>'receipt']:[]]] as [$route,$label,$permission,$args]){
  if(can($permission))echo linkTo($label,$route,$args,'workflow-step'.($step===$route?' current':''));
 }
 echo '</nav>';
}
function detailStart(string $title,bool $open=false): void {echo '<details class="form-details wide"'.($open?' open':'').'><summary>'.e(t($title)).'</summary><div class="form-grid details-content">';}
function detailEnd(): void {echo '</div></details>';}
function actionTable(array $headers,array $rows,array $sorting=[]): void {
 echo '<div class="table-wrap"><table'.($sorting?' data-server-sort':'').'><thead><tr>';foreach(array_merge($headers,['actions']) as $h)echo '<th>'.($sorting&&$h!=='actions'?moduleSortLink($sorting['module'],$h,t($h),$sorting['sort'],$sorting['dir'],$sorting['q']):e(t($h))).'</th>';echo '</tr></thead><tbody>';
 if(!$rows)echo '<tr><td class="empty" colspan="'.(count($headers)+1).'">'.e(t('no_records')).'</td></tr>';
 foreach($rows as [$values,$links]){echo '<tr>';foreach($values as $v)echo '<td>'.e($v).'</td>';echo '<td class="no-print"><div class="actions">';foreach($links as [$label,$route,$args])echo linkTo($label,$route,$args);echo '</div></td></tr>';}
 echo '</tbody></table></div>';
}
function rosterTools(): void {
 echo '<div class="roster-tools no-print"><input type="search" data-roster-search placeholder="'.e(t('search_roster')).'" aria-label="'.e(t('search_roster')).'"><span data-roster-count role="status"></span></div>';
}
function studentBalanceSummary(int $student): array {
 need('finance.view');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');
 if(!scalar('SELECT id FROM students WHERE id=?',[$student]))throw new DomainException('not_found');
 $charged=Domain::cents(scalar('SELECT COALESCE(SUM(c.amount),0) FROM charges c JOIN enrollments en ON en.id=c.enrollment_id WHERE en.student_id=?',[$student]));
 $credits=Domain::cents(scalar('SELECT COALESCE(SUM(cr.amount),0) FROM credits cr JOIN charges c ON c.id=cr.charge_id JOIN enrollments en ON en.id=c.enrollment_id WHERE en.student_id=?',[$student]));
 $allocated=Domain::cents(scalar('SELECT COALESCE(SUM(a.amount),0) FROM allocations a JOIN receipts r ON r.id=a.receipt_id WHERE r.student_id=?',[$student]));
 $received=Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE student_id=?',[$student]));
 $refunds=q('SELECT rf.allocation_id,rf.amount FROM refunds rf JOIN receipts r ON r.id=rf.receipt_id WHERE r.student_id=?',[$student])->fetchAll();$allocatedRefunds=0;$advanceRefunds=0;
 foreach($refunds as $refund){if($refund['allocation_id'])$allocatedRefunds+=Domain::cents($refund['amount']);else $advanceRefunds+=Domain::cents($refund['amount']);}
 return ['receivables'=>Domain::money($charged-$credits-$allocated+$allocatedRefunds),'advance'=>Domain::money($received-$allocated-$advanceRefunds)];
}
