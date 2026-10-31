<?php
// Match the Results page's existing enrollment dates and weighted pass rules.
function enrollmentResult(array $en,array $exams): array {
 $score=0;$pass=0;$weights=0;$missing=false;$marks=[];$details=[];
 foreach(q('SELECT * FROM marks WHERE enrollment_id=?',[$en['id']]) as $m)$marks[$m['assessment_id']]=$m;
 foreach($exams as $a){
  if($a['date']<$en['start_date']||($en['end_date']&&$a['date']>$en['end_date']))continue;
  $m=$marks[$a['id']]??null;$details[]=[$a,$m];
  if(!$m){$missing=true;continue;}if($m['status']==='exempt')continue;
  $w=(float)$a['weight'];$weights+=$w;$score+=($m['status']==='absent'?0:(float)$m['marks']/(float)$a['max_marks']*100)*$w;
  $pass+=(float)$a['pass_marks']/(float)$a['max_marks']*100*$w;
 }
 return ['score'=>$weights?number_format($score/$weights,2).'%':'—','result'=>$missing||!$weights?'incomplete':($score>=$pass?'passed':'failed'),'details'=>$details];
}
function resultCardPage(): void {
 need('exams.view');need('print');$cs=scopedClass(id('class_subject_id'));
 $en=one('SELECT en.*,s.name,s.father_name,s.id student_id FROM enrollments en JOIN students s ON s.id=en.student_id WHERE en.id=? AND en.batch_id=?',[id('enrollment_id'),$cs['batch_id']]);
 if(!$en)throw new DomainException('not_found');
 $exams=q('SELECT * FROM assessments WHERE class_subject_id=? ORDER BY date,id',[$cs['id']])->fetchAll();$result=enrollmentResult($en,$exams);
 $batch=one('SELECT b.level,p.name program FROM batches b JOIN programs p ON p.id=b.program_id WHERE b.id=?',[$cs['batch_id']]);
 echo '<div class="toolbar no-print">'.linkTo('back_results','results',['class_subject_id'=>$cs['id']]).'<span class="muted">'.e(t('result_pdf_hint')).'</span></div>';
 echo '<article class="result-card"><header class="result-card-header"><img class="result-logo kda-document-logo" src="assets/kda-logo-book-20261031.png" alt="KDA" width="62" height="62"><div><h2>'.e(setting(lang()==='en'?'center_name_en':'center_name')).'</h2><p>'.e(t('education_management')).'</p></div><div class="result-title">RESULT CARD<small>کارت نتیجه</small></div></header><section class="result-student">';
 foreach(['name'=>$en['name'],'student_id'=>sid((int)$en['student_id']),'level'=>($batch['program'].' / '.$batch['level']),'father_name'=>$en['father_name'],'batches'=>$cs['batch_name'],'teachers'=>$cs['teacher_name']] as $key=>$value)echo '<div><span>'.e(t($key)).'</span><b>'.e($value).'</b></div>';
 echo '</section><div class="table-wrap"><table><thead><tr>';foreach(['subjects','weighted_score','result','date'] as $key)echo '<th>'.e(t($key)).'</th>';
 echo '</tr></thead><tbody><tr><td>'.e($cs['subject_name']).'</td><td class="result-score">'.e($result['score']).'</td><td>'.e(t($result['result'])).'</td><td>'.e($result['details']?fdate($result['details'][count($result['details'])-1][0]['date']):'—').'</td></tr></tbody></table></div>';
 echo '<h3 class="result-detail-title">'.e(t('assessment_details')).'</h3><div class="table-wrap"><table><thead><tr>';foreach(['name','marks','max_marks','weight','status','date'] as $key)echo '<th>'.e(t($key)).'</th>';echo '</tr></thead><tbody>';
 foreach($result['details'] as [$a,$m])echo '<tr><td>'.e($a['name']).'</td><td>'.e($m&&$m['status']==='graded'?$m['marks']:($m&&$m['status']==='absent'?'0':'—')).'</td><td>'.e($a['max_marks']).'</td><td>'.e($a['weight']).'</td><td>'.e(t($m['status']??'unrecorded')).'</td><td>'.e(fdate($a['date'])).'</td></tr>';
 if(!$result['details'])echo '<tr><td colspan="6">'.e(t('no_records')).'</td></tr>';
 echo '</tbody></table></div><p class="muted">'.e(t('results_hint')).'</p><footer><div>'.e(t('teacher_signature')).': __________________</div><div>'.e(t('stamp')).': __________________</div></footer></article>';
}
