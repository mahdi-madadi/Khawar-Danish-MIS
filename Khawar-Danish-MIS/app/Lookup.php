<?php
function teachingGroupSearch(string $term,bool $subject=false): array {
 $where=[];$args=[];
 $normalize=fn($sql)=>"REPLACE(REPLACE(REPLACE($sql,'ي','ی'),'ى','ی'),'ك','ک')";
 foreach(preg_split('/\s+/u',$term,-1,PREG_SPLIT_NO_EMPTY) as $part){
  $like='%'.strtr($part,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
  $cols=['b.name','p.name','CAST(b.id AS CHAR)'];
  $matches=[];foreach($cols as $col){$matches[]=$normalize($col)." LIKE ? ESCAPE '!'";$args[]=$like;}
  $detail=[];foreach(['s.name','t.name','r.name','tt.start_time','tt.end_time'] as $col){$detail[]=$normalize($col)." LIKE ? ESCAPE '!'";$args[]=$like;}
  $matches[]='EXISTS (SELECT 1 FROM class_subjects x JOIN subjects s ON s.id=x.subject_id JOIN teachers t ON t.id=x.teacher_id LEFT JOIN timetable tt ON tt.class_subject_id=x.id LEFT JOIN rooms r ON r.id=tt.room_id WHERE '.($subject?'x.id=cs.id':'x.batch_id=b.id').' AND ('.implode(' OR ',$detail).'))';
  $where[]='('.implode(' OR ',$matches).')';
 }
 return [$where,$args];
}
function teachingGroupLookup(string $kind,string $term,bool $active=false,int $exact=0): array {
 $subject=$kind==='class_subjects';
 if($subject){if(!can('academic.view')&&!can('attendance.view')&&!can('exams.view'))throw new DomainException('forbidden');}
 else{if(!can('academic.view')&&!can('students.view'))throw new DomainException('forbidden');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');}
 $term=strtr(Domain::digits(trim($term)),['ي'=>'ی','ى'=>'ی','ك'=>'ک']);if(strlen($term)>160)throw new DomainException('invalid_input');
 $where=[];$args=[];
 if($exact){$where[]=$subject?'cs.id=?':'b.id=?';$args[]=$exact;}
 if($active)$where[]="b.status='active'";
 if($subject&&user()['teacher_id']&&!can('*')){$where[]='cs.teacher_id=?';$args[]=(int)user()['teacher_id'];}
 if(!$exact){[$searchWhere,$searchArgs]=teachingGroupSearch($term,$subject);array_push($where,...$searchWhere);array_push($args,...$searchArgs);}
 $sql=$subject?'SELECT cs.id,b.id batch_id,b.name,p.name program,b.start_date,b.end_date,b.capacity,b.status,b.fee FROM class_subjects cs JOIN batches b ON b.id=cs.batch_id JOIN programs p ON p.id=b.program_id':'SELECT b.id,b.id batch_id,b.name,p.name program,b.start_date,b.end_date,b.capacity,b.status,b.fee FROM batches b JOIN programs p ON p.id=b.program_id';
 $rows=q($sql.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY b.start_date DESC,b.name,'.($subject?'cs.id':'b.id').' DESC LIMIT 21',$args)->fetchAll();
 return array_map(function($row)use($subject){
  $lessons=q('SELECT cs.id,s.name subject,t.name teacher,tt.weekday,tt.start_time,tt.end_time,r.name room FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id JOIN teachers t ON t.id=cs.teacher_id LEFT JOIN timetable tt ON tt.class_subject_id=cs.id LEFT JOIN rooms r ON r.id=tt.room_id WHERE '.($subject?'cs.id=?':'cs.batch_id=?').' ORDER BY tt.weekday,tt.start_time',[$subject?$row['id']:$row['batch_id']])->fetchAll();
  $ltr=fn($v)=>"\u{2066}".$v."\u{2069}";
  $subjects=[];$times=[];foreach($lessons as $lesson){$subjects[]=$lesson['subject'].' / '.$lesson['teacher'];if($lesson['start_time'])$times[]=t('day'.$lesson['weekday']).' '.$ltr(substr($lesson['start_time'],0,5).'–'.substr($lesson['end_time'],0,5)).' / '.$lesson['room'];}
  $used=(int)scalar("SELECT COUNT(*) FROM enrollments WHERE batch_id=? AND status='active'",[$row['batch_id']]);$remaining=max(0,(int)$row['capacity']-$used);
  $title=$row['program'].' / '.$row['name'].' · #'.$row['batch_id'];
  $subtitle=implode('؛ ',array_unique($subjects)).' · '.($times?implode('؛ ',array_unique($times)):t('no_timetable'));
  $meta=t('available_seats').': '.$ltr($remaining.' / '.$row['capacity']).' · '.$ltr(fdate($row['start_date'])).' – '.$ltr(fdate($row['end_date'])).' · '.t($row['status']);
  return ['id'=>$row['id'],'label'=>$title.' · '.$subtitle,'title'=>$title,'subtitle'=>$subtitle,'meta'=>$meta,'available'=>$remaining,'start_date'=>fdate($row['start_date']),'end_date'=>fdate($row['end_date'])];
 },$rows);
}
function transactionLookup(string $kind,string $term,int $exact=0): array {
 $definitions=[
  'receipts'=>['finance.view','receipts x JOIN students s ON s.id=x.student_id','x.id,s.name,x.amount,x.date',"s.name",'RC-'],
  'charges'=>['finance.view','charges x JOIN enrollments en ON en.id=x.enrollment_id JOIN students s ON s.id=en.student_id','x.id,s.name,x.amount,x.due_date date,x.description',"CONCAT(s.name,' ',x.description)",'CH-'],
  'allocations'=>['finance.view','allocations x JOIN receipts r ON r.id=x.receipt_id JOIN students s ON s.id=r.student_id','x.id,s.name,x.amount,r.date,x.receipt_id',"s.name",'AL-'],
  'payroll'=>['payroll.view','payroll x JOIN teachers t ON t.id=x.teacher_id','x.id,t.name,x.total amount,x.period date',"t.name",'PR-'],
  'movements'=>['finance.view','movements x','x.id,x.reason name,x.amount,x.date',"x.reason",'MV-'],
  'certificates'=>['exams.view','certificates x JOIN enrollments en ON en.id=x.enrollment_id JOIN students s ON s.id=en.student_id','x.id,s.name,x.date',"s.name",'CT-'],
 ];
 if(!isset($definitions[$kind]))throw new DomainException('invalid_input');
 [$permission,$from,$columns,$search,$prefix]=$definitions[$kind];need($permission);
 if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');
 $term=Domain::digits(trim($term));if(strlen($term)>160)throw new DomainException('invalid_input');
 $where=[];$args=[];
 if($exact){$where[]='x.id=?';$args[]=$exact;}elseif($term!==''){
  $like='%'.strtr($term,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
  $where[]="($search LIKE ? ESCAPE '!' OR x.id=?)";$args=[$like,(int)preg_replace('/^[A-Z]+-0*/i','',$term)];
 }
 if($kind==='allocations'&&!$exact&&isset($_GET['receipt_id'])){$where[]='x.receipt_id=?';$args[]=(int)$_GET['receipt_id'];}
 $rows=q('SELECT '.$columns.' FROM '.$from.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY x.id DESC LIMIT 21',$args)->fetchAll();
 return array_map(fn($r)=>['id'=>$r['id'],'label'=>$prefix.$r['id'].' · '.$r['name'].' · '.$r['date'].(isset($r['amount'])?' · '.$r['amount'].' AFN':'').(isset($r['description'])?' · '.$r['description']:'')],$rows);
}
// Bounded database search: never download the full student or enrollment directory.
function lookupRows(string $kind,string $term,bool $active=false,int $exact=0): array {
 if(in_array($kind,['batches','class_subjects'],true))return teachingGroupLookup($kind,$term,$active,$exact);
 if(!in_array($kind,['students','enrollments'],true))return transactionLookup($kind,$term,$exact);
 need('students.view');
 if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');
 $term=Domain::digits(trim($term));
 if(strlen($term)>160)throw new DomainException('invalid_input');
 $parts=preg_split('/\s+/u',$term,-1,PREG_SPLIT_NO_EMPTY);$args=[];$conditions=[];
 $en=$kind==='enrollments';if(!$en&&$kind!=='students')throw new DomainException('invalid_input');
 if($exact){$conditions[]=$en?'en.id=?':'s.id=?';$args[]=$exact;$parts=[];}
 foreach($parts as $part){
  $like='%'.strtr($part,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
  $conditions[]="(s.name LIKE ? ESCAPE '!' OR s.father_name LIKE ? ESCAPE '!' OR s.phone LIKE ? ESCAPE '!' OR s.guardian_phone LIKE ? ESCAPE '!' OR COALESCE(s.public_code,CONCAT('KD-',LPAD(s.id,6,'0'))) LIKE ? ESCAPE '!' OR s.id=?".($en?" OR b.name LIKE ? ESCAPE '!' OR en.id=?":'').')';
  array_push($args,$like,$like,$like,$like,$like,ctype_digit($part)?(int)$part:0);if($en)array_push($args,$like,ctype_digit($part)?(int)$part:0);
 }
 if($active)$conditions[]=$en?"en.status='active'":"s.status='active'";
 $sql=$en?'SELECT en.id,s.id student_id,s.name,s.father_name,s.phone,en.status,b.name batch FROM enrollments en JOIN students s ON s.id=en.student_id JOIN batches b ON b.id=en.batch_id':'SELECT s.id,s.id student_id,s.name,s.father_name,s.phone,s.status FROM students s';
 $rows=q($sql.($conditions?' WHERE '.implode(' AND ',$conditions):'').' ORDER BY s.name,s.id LIMIT 21',$args)->fetchAll();
 return array_map(function($r)use($en){return ['id'=>$r['id'],'student_id'=>$r['student_id'],'label'=>sid((int)$r['student_id']).' · '.$r['name'].' · '.$r['father_name'].' · '.($r['phone']??'').($en?' · '.$r['batch'].' · #'.$r['id']:'').' · '.t($r['status'])];},$rows);
}
function lookupField(string $key,string $kind,$value,bool $required,string $inputId=''): void {
 $selected=null;$active=($GLOBALS['form_action']??'')==='close_enrollment'||($kind==='batches'&&($GLOBALS['form_action']??'')==='enroll');
 if($value){
  foreach(lookupRows($kind,'',false,(int)$value) as $r)if((string)$r['id']===(string)$value)$selected=$r;
 }
 echo '<div class="record-lookup" data-kind="'.e($kind).'" data-active="'.($active?'1':'0').'" data-empty="'.e(t('no_records')).'" data-error="'.e(t('lookup_error')).'" data-more="'.e(t('lookup_more')).'"><input id="'.e($inputId).'" class="lookup-query" aria-expanded="false" aria-controls="'.e($inputId).'-results" aria-describedby="'.e($inputId).'-status" type="search" autocomplete="off" placeholder="'.e(t(in_array($kind,['batches','class_subjects'],true)?'group_lookup_hint':(in_array($kind,['students','enrollments'],true)?'lookup_hint':'record_lookup_hint'))).'" aria-label="'.e(t($key)).'" value="'.e($selected['label']??'').'"'.($required?' required':'').'><input type="hidden" name="'.e($key).'" value="'.e($selected['id']??'').'"><button type="button" class="lookup-clear secondary" data-clear-lookup aria-label="'.e(t('clear_selection')).'">×</button><div id="'.e($inputId).'-results" class="lookup-results" role="group" aria-label="'.e(t('search')).'"></div><small id="'.e($inputId).'-status" class="lookup-status" role="status"></small><div class="lookup-selection"'.(empty($selected['meta'])?' hidden':'').'>'.e($selected['meta']??'').'</div></div>';
}
