<?php
require_once __DIR__.'/Employees.php';
final class Academic {
 static function save(string $module): int {
  $modules=require __DIR__.'/modules.php';if(!isset($modules[$module]))throw new DomainException('invalid_input');$m=$modules[$module];$rid=optionalId('id');need($m['permission'].($rid?'.update':'.create'));$values=[];
  foreach($m['fields'] as $field=>$spec){$optional=str_starts_with($spec,'?');$type=ltrim($spec,'?');$v=Domain::digits(trim((string)($_POST[$field]??'')));
   if($optional&&$v===''){$values[$field]=null;continue;}
   if($type==='date')$v=Domain::date($v,cal());elseif($type==='money')$v=amount($field,false);elseif($type==='int'||str_starts_with($type,'ref:')){$v=id($field);}elseif(str_starts_with($type,'choice:')){if(!in_array($v,explode('|',substr($type,7)),true))throw new DomainException('invalid_input');}else{$v=input($field,$type==='textarea'?2000:160,!$optional);}$values[$field]=$v;
  }
  if(in_array($module,['students','teachers','employees'],true))validateTazkira($values);
  if($module==='employees'){
   if($values['end_date']&&$values['end_date']<$values['joining_date'])throw new DomainException('invalid_date');
   if($rid&&$values['status']==='archived'&&scalar('SELECT COUNT(*) FROM users WHERE employee_id=? AND role_id=1 AND active=1',[$rid])&&!(int)scalar('SELECT COUNT(*) FROM users WHERE role_id=1 AND active=1 AND (employee_id IS NULL OR employee_id<>?)',[$rid]))throw new DomainException('last_admin');
   if($values['status']==='archived'&&!$values['end_date'])throw new DomainException('employment_end_required');
   if($rid&&scalar('SELECT COUNT(*) FROM employee_payroll WHERE employee_id=?',[$rid])){
    $existing=one('SELECT joining_date,end_date FROM employees WHERE id=?',[$rid]);
    $last=(string)scalar('SELECT MAX(period) FROM employee_payroll WHERE employee_id=?',[$rid]);if($values['end_date']&&$values['end_date']!==$existing['end_date']&&$values['end_date']<=date('Y-m-t',strtotime($last.'-01')))throw new DomainException('closed_period');if($existing['joining_date']!==$values['joining_date'])throw new DomainException('linked_record');
   }
  }
  if(isset($values['start_date'])&&$values['end_date']<$values['start_date'])throw new DomainException('invalid_date');
  if($module==='students'&&$values['dob']&&$values['dob']>$values['registered_date'])throw new DomainException('invalid_date');
  if($module==='batches'&&$rid){$old=one('SELECT * FROM batches WHERE id=?',[$rid]);if(scalar('SELECT COUNT(*) FROM enrollments WHERE batch_id=?',[$rid])||scalar('SELECT COUNT(*) FROM timetable tt JOIN class_subjects cs ON cs.id=tt.class_subject_id WHERE cs.batch_id=?',[$rid])){foreach(['program_id','start_date','end_date'] as $f)if((string)$old[$f]!== (string)$values[$f])throw new DomainException('linked_record');}if((int)$values['capacity']<(int)scalar("SELECT COUNT(*) FROM enrollments WHERE batch_id=? AND status='active'",[$rid]))throw new DomainException('capacity');}
  if($module==='batches'&&$rid&&scalar('SELECT COUNT(*) FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id WHERE cs.batch_id=? AND s.program_id IS NOT NULL AND s.program_id<>?',[$rid,$values['program_id']]))throw new DomainException('subject_course_mismatch');
  if($module==='subjects'){
   if(scalar('SELECT status FROM programs WHERE id=?',[$values['program_id']])!=='active')throw new DomainException('invalid_assignment');
   if($rid&&scalar('SELECT COUNT(*) FROM class_subjects cs JOIN batches b ON b.id=cs.batch_id WHERE cs.subject_id=? AND b.program_id<>?',[$rid,$values['program_id']]))throw new DomainException('linked_record');
  }
  if($module==='rooms'&&$rid&&scalar('SELECT COUNT(*) FROM timetable tt JOIN class_subjects cs ON cs.id=tt.class_subject_id JOIN batches b ON b.id=cs.batch_id WHERE tt.room_id=? AND b.capacity>? AND b.end_date>=?',[$rid,$values['capacity'],date('Y-m-d')]))throw new DomainException('capacity');
  if($module==='batches'&&$rid&&scalar('SELECT COUNT(*) FROM timetable tt JOIN class_subjects cs ON cs.id=tt.class_subject_id JOIN rooms r ON r.id=tt.room_id WHERE cs.batch_id=? AND r.capacity<?',[$rid,$values['capacity']]))throw new DomainException('capacity');
  if($module==='class_subjects'){
   $course=scalar('SELECT program_id FROM batches WHERE id=?',[$values['batch_id']]);$subjectCourse=scalar('SELECT program_id FROM subjects WHERE id=?',[$values['subject_id']]);if(!$subjectCourse||(int)$course!==(int)$subjectCourse)throw new DomainException('subject_course_mismatch');foreach(['batches'=>'batch_id','subjects'=>'subject_id','teachers'=>'teacher_id'] as $table=>$field)if(scalar("SELECT status FROM `$table` WHERE id=?",[$values[$field]])!=='active')throw new DomainException('invalid_assignment');}
  if($module==='class_subjects'&&$rid)throw new DomainException('linked_record'); // assignments are immutable to preserve historical ownership
  if($module==='assessments'){
   if($rid){$previous=one('SELECT class_subject_id FROM assessments WHERE id=?',[$rid]);if(!$previous)throw new DomainException('not_found');scopedClass((int)$previous['class_subject_id']);}
   $cs=scopedClass((int)$values['class_subject_id']);if($values['date']<$cs['start_date']||$values['date']>$cs['end_date'])throw new DomainException('invalid_date');
   if(Domain::cents($values['max_marks'])<=0||Domain::cents($values['weight'])<=0||Domain::cents($values['pass_marks'])>Domain::cents($values['max_marks']))throw new DomainException('invalid_marks');
   if($rid&&scalar('SELECT COUNT(*) FROM marks WHERE assessment_id=?',[$rid]))throw new DomainException('linked_record');
  }
  if($module==='students'&&!$rid&&!isset($_POST['duplicate_confirm'])&&scalar('SELECT COUNT(*) FROM students WHERE name=? AND father_name=?',[$values['name'],$values['father_name']]))throw new DomainException('duplicate_student');
  $before=$rid?one("SELECT * FROM `$module` WHERE id=?",[$rid]):null;
  if($rid&&!$before)throw new DomainException('not_found');
  if($rid&&in_array($module,['students','teachers','employees'],true))ensurePersonCode($module,$rid);
  if($rid){q("UPDATE `$module` SET ".implode(',',array_map(fn($k)=>"`$k`=?",array_keys($values))).' WHERE id=?',array_merge(array_values($values),[$rid]));}else{q("INSERT INTO `$module` (`".implode('`,`',array_keys($values)).'`) VALUES('.implode(',',array_fill(0,count($values),'?')).')',array_values($values));$rid=(int)db()->lastInsertId();}
  if(in_array($module,['students','teachers','employees'],true)){ensurePersonCode($module,$rid);savePhoto($module,$rid);}
  if($module==='employees'){q('UPDATE users SET name=? WHERE employee_id=?',[$values['name'],$rid]);if($values['status']==='archived')q('UPDATE users SET active=0,auth_version=auth_version+1 WHERE employee_id=?',[$rid]);}
  audit($before?'update':'create',$module,$rid,['fields'=>array_keys($values)]);return $rid;
 }
 static function enroll(): void {
  $student=id('student_id');$batch=id('batch_id');$b=one('SELECT * FROM batches WHERE id=?',[$batch]);if(!$b||$b['status']!=='active'||scalar('SELECT status FROM students WHERE id=?',[$student])!=='active')throw new DomainException('invalid_input');
  if((int)scalar("SELECT COUNT(*) FROM enrollments WHERE batch_id=? AND status='active'",[$batch])>=(int)$b['capacity'])throw new DomainException('capacity');
  if(scalar('SELECT COUNT(*) FROM enrollments WHERE student_id=? AND batch_id=?',[$student,$batch]))throw new DomainException('already_processed');
  $start=dateInput('start_date');if($start<$b['start_date']||$start>$b['end_date'])throw new DomainException('invalid_date');
  q('INSERT INTO enrollments(student_id,batch_id,start_date) VALUES(?,?,?)',[$student,$batch,$start]);$eid=(int)db()->lastInsertId();$fee=amount('fee',false);
  if(Domain::cents($fee)>0)q("INSERT INTO charges(enrollment_id,kind,description,amount,due_date) VALUES(?,'tuition',?,?,?)",[$eid,$b['name'],$fee,$start]);audit('enroll','enrollments',$eid);
 }
 static function closeEnrollment(): void {
  $eid=id('enrollment_id');$en=one("SELECT e.*,b.end_date batch_end FROM enrollments e JOIN batches b ON b.id=e.batch_id WHERE e.id=?",[$eid]);if(!$en||$en['status']!=='active')throw new DomainException('invalid_input');$date=dateInput('end_date');if($date<$en['start_date']||$date>$en['batch_end'])throw new DomainException('invalid_date');
  if(scalar('SELECT COUNT(*) FROM attendance WHERE enrollment_id=? AND date>?',[$eid,$date]))throw new DomainException('linked_record');
  if(scalar('SELECT COUNT(*) FROM marks m JOIN assessments a ON a.id=m.assessment_id WHERE m.enrollment_id=? AND a.date>?',[$eid,$date]))throw new DomainException('linked_record');
  $status=input('status');if(!in_array($status,['withdrawn','completed','transferred'],true))throw new DomainException('invalid_input');q('UPDATE enrollments SET end_date=?,status=? WHERE id=?',[$date,$status,$eid]);audit($status,'enrollments',$eid);
  if($status==='transferred'){if(dateInput('start_date')<$date||id('batch_id')===(int)$en['batch_id'])throw new DomainException('invalid_date');$_POST['student_id']=$en['student_id'];self::enroll();}
 }
 static function timetable(): void {
  $cs=scopedClass(id('class_subject_id'));$room=id('room_id');$weekday=(int)Domain::digits(input('weekday'));$s=input('start_time');$e=input('end_time');$sd=dateInput('start_date');$ed=dateInput('end_date');
  if($weekday<0||$weekday>6||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$s)||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$e)||$s>=$e||$sd>$ed||$sd<$cs['start_date']||$ed>$cs['end_date'])throw new DomainException('invalid_input');
  if(scalar('SELECT status FROM rooms WHERE id=?',[$room])!=='active')throw new DomainException('invalid_assignment');
  $conflict=scalar('SELECT COUNT(*) FROM timetable tt JOIN class_subjects cs ON cs.id=tt.class_subject_id WHERE tt.weekday=? AND tt.start_date<=? AND tt.end_date>=? AND tt.start_time<? AND tt.end_time>? AND (tt.room_id=? OR cs.teacher_id=? OR cs.batch_id=?)',[$weekday,$ed,$sd,$e,$s,$room,$cs['teacher_id'],$cs['batch_id']]);if($conflict)throw new DomainException('overlap');
  if((int)scalar('SELECT capacity FROM rooms WHERE id=?',[$room])<(int)scalar('SELECT capacity FROM batches WHERE id=?',[$cs['batch_id']]))throw new DomainException('capacity');
  q('INSERT INTO timetable(class_subject_id,room_id,weekday,start_time,end_time,start_date,end_date) VALUES(?,?,?,?,?,?,?)',[$cs['id'],$room,$weekday,$s,$e,$sd,$ed]);audit('create','timetable',(int)db()->lastInsertId());
 }
 static function roster(int $csid,string $date): array {$cs=scopedClass($csid);return q('SELECT e.id enrollment_id,s.id student_id,s.name,s.father_name FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.batch_id=? AND e.start_date<=? AND (e.end_date IS NULL OR e.end_date>=?) ORDER BY s.name,s.id',[$cs['batch_id'],$date,$date])->fetchAll();}
 static function attendance(): void {
  $csid=id('class_subject_id');$cs=scopedClass($csid);$date=dateInput('date');if($date<$cs['start_date']||$date>$cs['end_date']||$date>date('Y-m-d'))throw new DomainException('invalid_date');
  foreach(self::roster($csid,$date) as $s){$status=$_POST['attendance'][$s['enrollment_id']]??'';if(!in_array($status,['present','absent','late','excused'],true))throw new DomainException('invalid_input');q('INSERT INTO attendance(enrollment_id,class_subject_id,date,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)',[$s['enrollment_id'],$csid,$date,$status]);}audit('record','attendance',$csid,['date'=>$date]);
 }
 static function marks(): void {
  $a=one('SELECT * FROM assessments WHERE id=?',[id('assessment_id')]);if(!$a)throw new DomainException('not_found');scopedClass((int)$a['class_subject_id']);
  foreach(self::roster((int)$a['class_subject_id'],$a['date']) as $s){$eid=$s['enrollment_id'];$status=$_POST['mark_status'][$eid]??'';if(!in_array($status,['graded','absent','exempt'],true))throw new DomainException('invalid_input');$mark=null;if($status==='graded'){$c=Domain::cents($_POST['marks'][$eid]??'');if($c<0||$c>Domain::cents($a['max_marks']))throw new DomainException('invalid_marks');$mark=Domain::money($c);}
   q('INSERT INTO marks(assessment_id,enrollment_id,marks,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE marks=VALUES(marks),status=VALUES(status)',[$a['id'],$eid,$mark,$status]);}audit('record','marks',(int)$a['id']);
 }
 static function certificate(): void {
  $kind=input('kind');if(!in_array($kind,['completion','appreciation'],true))throw new DomainException('invalid_input');$eid=id('enrollment_id');$en=one('SELECT * FROM enrollments WHERE id=?',[$eid]);if(!$en||($kind==='completion'&&$en['status']!=='completed'))throw new DomainException('not_completed');
  q('INSERT INTO certificates(enrollment_id,kind,date,wording) VALUES(?,?,?,?)',[$eid,$kind,dateInput('date'),input('wording',2000)]);audit('issue','certificates',(int)db()->lastInsertId());
 }
}
