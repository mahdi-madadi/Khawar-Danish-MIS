<?php
function createTeachingGroup(): int {
 need('academic.create');
 $program=id('program_id');$subject=id('subject_id');$teacher=id('teacher_id');$room=id('room_id');$capacity=id('capacity');
 if(scalar('SELECT status FROM programs WHERE id=?',[$program])!=='active'||scalar('SELECT status FROM teachers WHERE id=?',[$teacher])!=='active')throw new DomainException('invalid_assignment');
 $s=one('SELECT * FROM subjects WHERE id=?',[$subject]);$r=one('SELECT * FROM rooms WHERE id=?',[$room]);
 if(!$s||$s['status']!=='active'||(int)$s['program_id']!==$program)throw new DomainException('subject_course_mismatch');
 if(!$r||$r['status']!=='active')throw new DomainException('invalid_assignment');
 if($capacity>(int)$r['capacity'])throw new DomainException('capacity');
 $days=$_POST['weekdays']??[];if(!is_array($days)||!$days)throw new DomainException('choose_teaching_days');
 $days=array_unique(array_map(fn($d)=>Domain::digits((string)$d),$days));foreach($days as $d)if(!in_array($d,['0','1','2','3','4','5','6'],true))throw new DomainException('invalid_input');
 $start=dateInput('start_date');$end=dateInput('end_date');if($end<$start)throw new DomainException('invalid_date');
 $name=input('name',160);$fee=amount('fee',false);
 q('INSERT INTO batches(name,program_id,level,start_date,end_date,capacity,fee) VALUES(?,?,?,?,?,?,?)',[$name,$program,$s['name'],$start,$end,$capacity,$fee]);$batch=(int)db()->lastInsertId();
 q('INSERT INTO class_subjects(batch_id,subject_id,teacher_id,required) VALUES(?,?,?,1)',[$batch,$subject,$teacher]);$cs=(int)db()->lastInsertId();
 // Called within the request transaction: any conflict rolls back the entire group.
 $original=$_POST;
 try{$_POST['class_subject_id']=$cs;foreach($days as $day){$_POST['weekday']=$day;Academic::timetable();}}finally{$_POST=$original;}
 audit('create_teaching_group','batches',$batch);return $batch;
}
function teachingGroupsPage(): void {
 need('academic.view');
 if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');
 echo '<div class="hero"><h2>'.e(t('teaching_groups')).'</h2><p>'.e(t('academic_example')).'</p></div><div class="toolbar no-print">';
 if(can('academic.create'))echo '<a class="button" href="#panel-new_teaching_group">'.e(t('new_teaching_group')).'</a>';foreach(['rooms','programs','subjects','teachers'] as $route)echo linkTo($route,$route);echo '</div>';
 if(scalar('SELECT COUNT(*) FROM subjects WHERE program_id IS NULL'))echo '<div class="notice no-print">'.e(t('unassigned_course_hint')).' '.linkTo('subjects','subjects').'</div>';
 panel('teaching_groups');$rows=[];
 $search=trim((string)($_GET['q']??''));$page=max(1,(int)($_GET['page']??1));$where='';$args=[];
 if($search!==''){[$parts,$args]=teachingGroupSearch(strtr(Domain::digits($search),['ي'=>'ی','ى'=>'ی','ك'=>'ک']));$where=$parts?' WHERE '.implode(' AND ',$parts):'';}
 echo '<form class="searchbar no-print"><input type="hidden" name="r" value="batches"><input name="q" value="'.e($search).'" placeholder="'.e(t('group_lookup_hint')).'" aria-label="'.e(t('search')).'"><button>'.e(t('search')).'</button>'.linkTo('clear_selection','batches').'</form>';
 $count=(int)scalar('SELECT COUNT(*) FROM batches b JOIN programs p ON p.id=b.program_id'.$where,$args);
 $groups=q('SELECT b.*,p.name program FROM batches b JOIN programs p ON p.id=b.program_id'.$where.' ORDER BY b.id DESC LIMIT 20 OFFSET '.(($page-1)*20),$args)->fetchAll();
 foreach($groups as $group){$lessons=q('SELECT cs.id,s.name subject,t.name teacher FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id JOIN teachers t ON t.id=cs.teacher_id WHERE cs.batch_id=?',[$group['id']])->fetchAll();
  if(!$lessons)$lessons=[['id'=>0,'subject'=>'—','teacher'=>'—']];
  foreach($lessons as $lesson){$times=[];foreach(Domain::groupedSchedule(q('SELECT tt.*,r.name room FROM timetable tt JOIN rooms r ON r.id=tt.room_id WHERE tt.class_subject_id=? ORDER BY tt.start_time,tt.weekday',[$lesson['id']])->fetchAll()) as $tt)$times[]=implode('، ',array_map(fn($d)=>t('day'.$d),$tt['weekdays'])).' '.substr($tt['start_time'],0,5).'–'.substr($tt['end_time'],0,5).' / '.$tt['room'].' ('.fdate($tt['start_date']).'–'.fdate($tt['end_date']).')';
   $links=[];if(can('students.create'))$links[]=['new_enrollment','enrollments',['batch_id'=>$group['id']]];
   if(can('print')&&can('attendance.view')&&$lesson['id'])$links[]=['attendance_sheet','attendance_sheet',['class_subject_id'=>$lesson['id']]];
   if(can('academic.update'))$links[]=['edit','batches',['id'=>$group['id'],'edit'=>1]];
   $rows[]=[[$group['name'],$group['program'],$lesson['subject'],$lesson['teacher'],implode('؛ ',$times),(int)scalar("SELECT COUNT(*) FROM enrollments WHERE batch_id=? AND status='active'",[$group['id']]).' / '.$group['capacity'],t($group['status'])],$links];
  }
 }
 actionTable(['teaching_groups','programs','subjects','teachers','lesson_schedule','seat_capacity','status'],$rows);echo '<div class="pagination no-print">';if($page>1)echo linkTo('previous','batches',['page'=>$page-1,'q'=>$search]);echo '<span>'.e($page).' / '.max(1,(int)ceil($count/20)).'</span>';if($page*20<$count)echo linkTo('next','batches',['page'=>$page+1,'q'=>$search]);echo '</div>';endPanel();
 if(!can('academic.create'))return;
 panel('new_teaching_group');echo '<p class="muted">'.e(t('group_setup_hint')).'</p>';formStart('teaching_group');
 field('name','text');field('program_id','ref:programs');
 echo '<div class="field"><label for="group-subject">'.e(t('subject_id')).'</label><select id="group-subject" name="subject_id" required><option value="">—</option>';
 foreach(q("SELECT s.id,s.name,s.program_id,p.name program FROM subjects s JOIN programs p ON p.id=s.program_id WHERE s.status='active' AND p.status='active' ORDER BY p.name,s.name") as $s)echo '<option data-program="'.e($s['program_id']).'" value="'.e($s['id']).'"'.((string)formValue('subject_id','')===(string)$s['id']?' selected':'').'>'.e($s['program'].' / '.$s['name']).'</option>';
 echo '</select></div>';field('teacher_id','ref:teachers');field('room_id','ref:rooms');field('capacity','int','20');field('start_time','time','08:00');field('end_time','time','09:00');field('start_date','date');field('end_date','date');field('fee','money','0.00');
 echo '<fieldset class="wide teaching-days"><legend>'.e(t('teaching_days')).'</legend>';
 foreach([6,0,1,2,3,4,5] as $day){$selected=formValue('weekdays',[]);echo '<label><input type="checkbox" name="weekdays[]" value="'.$day.'"'.(is_array($selected)&&in_array((string)$day,array_map('strval',$selected),true)?' checked':'').'> '.e(t('day'.$day)).'</label>';}
 echo '</fieldset>';formEnd('save');endPanel();
 echo '<div class="toolbar no-print">'.linkTo('advanced_assignments','class_subjects').linkTo('timetable','timetable').'</div>';
}
