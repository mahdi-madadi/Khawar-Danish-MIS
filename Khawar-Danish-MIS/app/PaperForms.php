<?php
function formsPage(): void {
 need('print');
 echo '<div class="hero"><h2>'.e(t('forms')).'</h2><p>'.e(t('blank_form_hint')).'</p></div><div class="forms-catalog">';
 foreach(['blank_student','blank_teacher','blank_consent','blank_testimonial'] as $type){echo '<article class="panel"><span class="form-symbol" aria-hidden="true">▤</span><h2>'.e(t($type)).'</h2><p>'.e(t('blank_form_hint')).'</p>'.linkTo('open','document',['type'=>$type]).'</article>';}
 foreach([['attendance_sheet','attendance_sheet','attendance.view'],['receipt','finance','finance.view'],['results','results','exams.view']] as [$label,$route,$permission])if(can($permission))echo '<article class="panel"><span class="form-symbol" aria-hidden="true">▦</span><h2>'.e(t($label)).'</h2><p>'.e(t($route==='attendance_sheet'?'paper_attendance_hint':'record_document_hint')).'</p>'.linkTo('open',$route).'</article>';
 echo '</div>';
}

function attendanceSheetPage(): void {
 need('attendance.view');need('print');
 $id=(int)($_GET['class_subject_id']??0);
 $month=(string)($_GET['month']??substr(fdate(date('Y-m-d')),0,7));
 $days=Domain::month($month,cal());$start=$days[0];$end=$days[count($days)-1];
 $cs=$id?scopedClass($id):null;
 $schedule=$id?q('SELECT tt.*,r.name room FROM timetable tt JOIN rooms r ON r.id=tt.room_id WHERE tt.class_subject_id=? AND tt.start_date<=? AND tt.end_date>=? ORDER BY tt.start_time,tt.weekday',[$id,$end,$start])->fetchAll():[];
 $slots=[];foreach($schedule as $tt){$key=substr($tt['start_time'],0,5).'-'.substr($tt['end_time'],0,5);$slots[$key]=$key;}
 $slot=(string)($_GET['time_slot']??'');
 if($slot!==''&&!isset($slots[$slot]))throw new DomainException('invalid_input');
 if($slot===''&&count($slots)===1)$slot=(string)array_key_first($slots);
 echo '<section class="panel no-print"><h2>'.e(t('attendance_sheet')).'</h2><p>'.e(t('paper_attendance_hint')).'</p><form class="form-grid" data-paper-filters><input type="hidden" name="r" value="attendance_sheet">';
 field('class_subject_id','ref:class_subjects',$id);field('month','text',$month);
 echo '<div class="field"><label for="paper-time">'.e(t('time_slot')).'</label><select id="paper-time" name="time_slot"><option value="">'.e(t('choose_time')).'</option>';
 foreach($slots as $key)echo '<option value="'.e($key).'"'.($slot===$key?' selected':'').'>'.e($key).'</option>';
 echo '</select></div>';formEnd('load');echo '<p class="muted">'.e(t('paper_filter_hint')).'</p></section>';
 if(!$cs)return;
 if(!$schedule){echo '<p class="notice no-print">'.e(t('no_schedule_month')).'</p>';return;}
 if($slot===''){echo '<p class="notice no-print">'.e(t('choose_time')).'</p>';return;}
 $schedule=array_values(array_filter($schedule,fn($tt)=>substr($tt['start_time'],0,5).'-'.substr($tt['end_time'],0,5)===$slot));
 $roster=q('SELECT en.*,s.name,s.father_name FROM enrollments en JOIN students s ON s.id=en.student_id WHERE en.batch_id=? AND en.start_date<=? AND (en.end_date IS NULL OR en.end_date>=?) ORDER BY s.name,s.id',[$cs['batch_id'],$end,$start])->fetchAll();
 $holidays=q('SELECT date,name FROM holidays WHERE date BETWEEN ? AND ?',[$start,$end])->fetchAll(PDO::FETCH_KEY_PAIR);
 $closed=explode(',',setting('closure_days','5'));
 $scheduled=function(string $d)use($schedule,$cs,$holidays,$closed):bool{
  if($d<$cs['start_date']||$d>$cs['end_date']||isset($holidays[$d])||in_array(date('w',strtotime($d)),$closed,true))return false;
  foreach($schedule as $tt)if((int)$tt['weekday']===(int)date('w',strtotime($d))&&$d>=$tt['start_date']&&$d<=$tt['end_date'])return true;
  return false;
 };
 echo '<div class="toolbar no-print"><button type="button" data-print>'.e(t('save_attendance_pdf')).'</button><span>'.e(t('pdf_hint')).'</span></div>';
 $pages=array_chunk($roster,20);if(!$pages)$pages=[[]];
 foreach($pages as $page=>$students){
  echo '<article class="paper-attendance"><header><img class="paper-brand kda-document-logo" src="assets/kda-logo-book-20261031.png" alt="KDA" width="62" height="62"><div><h1>'.e(setting(lang()==='en'?'center_name_en':'center_name')).'</h1><p>'.e(setting('address').' · '.setting('phone')).'</p></div><div><h2>'.e(t('attendance_sheet')).'</h2><bdi>'.e($month).'</bdi></div></header><div class="paper-facts">';
  foreach(['batches'=>$cs['batch_name'],'subjects'=>$cs['subject_name'],'teachers'=>$cs['teacher_name'],'time_slot'=>$slot,'rooms'=>implode(' / ',array_unique(array_column($schedule,'room')))] as $label=>$value)echo '<span><strong>'.e(t($label)).': </strong>'.e($value).'</span>';
  echo '</div><div class="table-wrap"><table><thead><tr><th>#</th><th class="paper-name">'.e(t('name')).'</th><th class="paper-father">'.e(t('father_name')).'</th>';
  foreach($days as $d)echo '<th class="paper-day'.(!$scheduled($d)?' paper-off':'').'">'.e(substr(fdate($d),8,2)).'<br><span class="attendance-weekday">'.e(t('day'.date('w',strtotime($d)))).'</span></th>';
  echo '<th>'.e(t('present')).'</th><th>'.e(t('absent')).'</th></tr></thead><tbody>';
  foreach($students as $i=>$en){echo '<tr><td>'.($page*20+$i+1).'</td><td class="paper-name">'.e($en['name']).'<small><bdi>'.e(sid((int)$en['student_id'])).'</bdi></small></td><td class="paper-father">'.e($en['father_name']).'</td>';
   foreach($days as $d){$off=!$scheduled($d)||$d<$en['start_date']||($en['end_date']&&$d>$en['end_date']);echo '<td'.($off?' class="paper-off"':'').'>'.($off?'—':'').'</td>';}
   echo '<td></td><td></td></tr>';
  }
  if(!$students)echo '<tr><td colspan="'.(count($days)+5).'">'.e(t('no_records')).'</td></tr>';
  echo '</tbody></table></div><p class="paper-legend">'.e(t('paper_legend')).'</p><div class="paper-signatures"><span>'.e(t('teachers')).': __________________</span><span>'.e(t('signature')).': __________________</span><span>'.($page+1).' / '.count($pages).'</span></div></article>';
 }
}
