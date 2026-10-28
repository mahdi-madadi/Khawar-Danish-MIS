<?php
final class Finance {
 static function chargeBalance(int $id): int {
  $c=one('SELECT amount FROM charges WHERE id=?',[$id]);if(!$c)throw new DomainException('not_found');
  return Domain::cents($c['amount'])-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM credits WHERE charge_id=?',[$id]))-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM allocations WHERE charge_id=?',[$id]))+Domain::cents(scalar('SELECT COALESCE(SUM(r.amount),0) FROM refunds r JOIN allocations a ON a.id=r.allocation_id WHERE a.charge_id=?',[$id]));
 }
 static function advance(int $rid): int {
  $r=one('SELECT amount FROM receipts WHERE id=?',[$rid]);if(!$r)throw new DomainException('not_found');
  return Domain::cents($r['amount'])-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM allocations WHERE receipt_id=?',[$rid]))-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM refunds WHERE receipt_id=? AND allocation_id IS NULL',[$rid]));
 }
 static function allocate(int $rid): void {
  $r=one('SELECT * FROM receipts WHERE id=?',[$rid]);$remaining=self::advance($rid);
  $charges=q('SELECT c.id FROM charges c JOIN enrollments e ON e.id=c.enrollment_id WHERE e.student_id=? ORDER BY c.due_date,c.id',[$r['student_id']])->fetchAll();
  foreach($charges as $c){if($remaining<=0)break;$balance=self::chargeBalance((int)$c['id']);$part=min($remaining,$balance);if($part<=0)continue;
   // Never mutate an allocation already included in approved payroll.
   if(scalar('SELECT COUNT(*) FROM allocations WHERE receipt_id=? AND charge_id=?',[$rid,$c['id']]))continue;
   q('INSERT INTO allocations(receipt_id,charge_id,amount) VALUES(?,?,?)',[$rid,$c['id'],Domain::money($part)]);$remaining-=$part;
  }
 }
 static function receipt(): int {
  $token=requestToken();$existing=one('SELECT id FROM receipts WHERE token=?',[$token]);if($existing)return (int)$existing['id'];
  $student=id('student_id');$money=amount();q('INSERT INTO receipts(student_id,account_id,amount,date,reference,token) VALUES(?,?,?,?,?,?)',[$student,id('account_id'),$money,dateInput('date'),input('reference',160,false),$token]);$rid=(int)db()->lastInsertId();self::allocate($rid);audit('receive','receipts',$rid,['amount'=>$money]);return $rid;
 }
 static function charge(): void {
  $eid=id('enrollment_id');$kind=input('kind');if(!in_array($kind,['tuition','registration'],true))throw new DomainException('invalid_input');
  q('INSERT INTO charges(enrollment_id,kind,description,amount,due_date) VALUES(?,?,?,?,?)',[$eid,$kind,input('description'),amount(),dateInput('due_date')]);audit('create','charges',(int)db()->lastInsertId());
 }
 static function discount(): void {
  $cid=id('charge_id');$money=amount();if(Domain::cents($money)>self::chargeBalance($cid))throw new DomainException('exceeds_balance');
  q('INSERT INTO credits(charge_id,amount,reason) VALUES(?,?,?)',[$cid,$money,input('reason')]);audit('discount','charges',$cid,['amount'=>$money]);
 }
 static function refund(): void {
  $token=requestToken();if(scalar('SELECT COUNT(*) FROM refunds WHERE token=?',[$token]))return;
  $rid=id('receipt_id');$aid=optionalId('allocation_id');$money=amount();$receipt=one('SELECT * FROM receipts WHERE id=?',[$rid]);if(!$receipt)throw new DomainException('not_found');$date=dateInput('date');if($date<$receipt['date'])throw new DomainException('invalid_date');
  if($aid){$a=one('SELECT * FROM allocations WHERE id=? AND receipt_id=?',[$aid,$rid]);if(!$a)throw new DomainException('invalid_input');$available=Domain::cents($a['amount'])-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM refunds WHERE allocation_id=?',[$aid]));}else{$available=self::advance($rid);}
  if(Domain::cents($money)>$available)throw new DomainException('exceeds_balance');
  q('INSERT INTO refunds(receipt_id,allocation_id,account_id,amount,date,reason,token) VALUES(?,?,?,?,?,?,?)',[$rid,$aid?:null,id('account_id'),$money,$date,input('reason'),$token]);audit('refund','receipts',$rid,['amount'=>$money,'allocation'=>$aid]);
 }
 static function movement(): void {
  $token=requestToken();if(scalar('SELECT COUNT(*) FROM movements WHERE token=?',[$token]))return;
  $kind=input('kind');if(!in_array($kind,['income','expense','transfer'],true))throw new DomainException('invalid_input');$account=id('account_id');$dest=$kind==='transfer'?id('destination_id'):null;if($dest===$account)throw new DomainException('invalid_input');
  q('INSERT INTO movements(account_id,destination_id,kind,category,amount,date,reason,token) VALUES(?,?,?,?,?,?,?,?)',[$account,$dest,$kind,input('category',100),amount(),dateInput('date'),input('reason'),$token]);audit('create','movements',(int)db()->lastInsertId());
 }
 static function reverse(): void {
  $r=one('SELECT * FROM movements WHERE id=?',[id('movement_id')]);if(!$r||$r['reversal_of']||scalar('SELECT COUNT(*) FROM movements WHERE reversal_of=?',[$r['id']]))throw new DomainException('already_processed');
  if(dateInput('date')<$r['date'])throw new DomainException('invalid_date');
  q('INSERT INTO movements(account_id,destination_id,kind,category,amount,date,reason,reversal_of,token) VALUES(?,?,?,?,?,?,?,?,?)',[$r['account_id'],$r['destination_id'],$r['kind'],$r['category'],Domain::money(-Domain::cents($r['amount'])),dateInput('date'),input('reason'),$r['id'],requestToken()]);audit('reverse','movements',(int)$r['id']);
 }
 static function accountBalance(int $id,?string $until=null): int {
  $until=$until??date('Y-m-d');$balance=Domain::cents(scalar('SELECT opening_balance FROM accounts WHERE id=?',[$id]));
  foreach([['receipts',1],['refunds',-1],['payroll_payments',-1],['employee_payroll_payments',-1]] as [$table,$sign])$balance+=$sign*Domain::cents(scalar("SELECT COALESCE(SUM(amount),0) FROM $table WHERE account_id=? AND date<=?",[$id,$until]));
  $balance+=Domain::cents(scalar("SELECT COALESCE(SUM(CASE WHEN kind='income' THEN amount ELSE -amount END),0) FROM movements WHERE account_id=? AND date<=?",[$id,$until]));
  $balance+=Domain::cents(scalar("SELECT COALESCE(SUM(amount),0) FROM movements WHERE destination_id=? AND kind='transfer' AND date<=?",[$id,$until]));return $balance;
 }
 static function agreement(): void {
  $teacher=id('teacher_id');$batch=id('batch_id');$start=dateInput('start_date');$end=dateInput('end_date');if($end<$start)throw new DomainException('invalid_date');
  if(!scalar('SELECT COUNT(*) FROM class_subjects WHERE teacher_id=? AND batch_id=?',[$teacher,$batch]))throw new DomainException('invalid_assignment');
  if(scalar('SELECT COUNT(*) FROM agreements WHERE teacher_id=? AND batch_id=? AND start_date<=? AND end_date>=?',[$teacher,$batch,$end,$start]))throw new DomainException('overlap');
  $percent=amount('percent',false);if(Domain::cents($percent)>10000)throw new DomainException('invalid_input');
  $others=q('SELECT start_date,end_date,percent FROM agreements WHERE batch_id=? AND start_date<=? AND end_date>=?',[$batch,$end,$start])->fetchAll();
  $dates=[$start];foreach($others as $o)if($o['start_date']>$start)$dates[]=$o['start_date'];
  foreach($dates as $d){$sum=Domain::cents($percent);foreach($others as $o)if($o['start_date']<=$d&&$o['end_date']>=$d)$sum+=Domain::cents($o['percent']);if($sum>10000)throw new DomainException('percent_total');}
  // Do not introduce retroactive eligibility after a closed payroll.
  if(scalar('SELECT COUNT(*) FROM payroll WHERE teacher_id=? AND period>=?',[$teacher,substr($start,0,7)]))throw new DomainException('closed_period');
  q('INSERT INTO agreements(teacher_id,batch_id,start_date,end_date,fixed_amount,percent) VALUES(?,?,?,?,?,?)',[$teacher,$batch,$start,$end,amount('fixed_amount',false),$percent]);audit('create','agreements',(int)db()->lastInsertId());
 }
 static function calculate(int $teacher,string $period): array {
  if(!preg_match('/^20\d\d-(0[1-9]|1[0-2])$/D',$period))throw new DomainException('invalid_date');$start=$period.'-01';$end=date('Y-m-t',strtotime($start));$days=(int)date('t',strtotime($start));$fixed=0;$variable=0;$lines=[];$sources=[];
  foreach(q('SELECT * FROM agreements WHERE teacher_id=? ORDER BY id',[$teacher]) as $a){
   $s=max($start,$a['start_date']);$e=min($end,$a['end_date']);
   if($s<=$e){$covered=(int)(new DateTimeImmutable($s))->diff(new DateTimeImmutable($e))->days+1;$part=intdiv(Domain::cents($a['fixed_amount'])*$covered+intdiv($days,2),$days);$fixed+=$part;$lines[]=['agreement'=>$a['id'],'type'=>'fixed','days'=>$covered,'month_days'=>$days,'amount'=>Domain::money($part)];}
   $receipts=q("SELECT al.id,al.amount,r.date FROM allocations al JOIN receipts r ON r.id=al.receipt_id JOIN charges c ON c.id=al.charge_id JOIN enrollments en ON en.id=c.enrollment_id WHERE en.batch_id=? AND c.kind='tuition' AND r.date BETWEEN ? AND ? AND r.date<=? AND NOT EXISTS(SELECT 1 FROM payroll_sources ps WHERE ps.agreement_id=? AND ps.allocation_id=al.id)",[$a['batch_id'],$a['start_date'],$a['end_date'],$end,$a['id']])->fetchAll();
   foreach($receipts as $r){$part=Domain::percent(Domain::cents($r['amount']),Domain::cents($a['percent']));$variable+=$part;$sources[]=[$a['id'],$r['id'],null,Domain::money($part)];$lines[]=['agreement'=>$a['id'],'allocation'=>$r['id'],'collected'=>$r['amount'],'percent'=>$a['percent'],'amount'=>Domain::money($part)];}
   $refunds=q("SELECT rf.id,rf.amount,al.id allocation_id FROM refunds rf JOIN allocations al ON al.id=rf.allocation_id JOIN receipts r ON r.id=al.receipt_id JOIN charges c ON c.id=al.charge_id JOIN enrollments en ON en.id=c.enrollment_id WHERE en.batch_id=? AND c.kind='tuition' AND r.date BETWEEN ? AND ? AND rf.date<=? AND NOT EXISTS(SELECT 1 FROM payroll_sources ps WHERE ps.agreement_id=? AND ps.refund_id=rf.id)",[$a['batch_id'],$a['start_date'],$a['end_date'],$end,$a['id']])->fetchAll();
   foreach($refunds as $r){$prior=Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM refunds WHERE allocation_id=? AND id<?',[$r['allocation_id'],$r['id']]));$bps=Domain::cents($a['percent']);$part=-(Domain::percent($prior+Domain::cents($r['amount']),$bps)-Domain::percent($prior,$bps));$variable+=$part;$sources[]=[$a['id'],null,$r['id'],Domain::money($part)];$lines[]=['agreement'=>$a['id'],'refund'=>$r['id'],'amount'=>Domain::money($part)];}
  }
  return ['fixed'=>$fixed,'variable'=>$variable,'total'=>$fixed+$variable,'lines'=>$lines,'sources'=>$sources];
 }
 static function approve(): void {
  $teacher=id('teacher_id');$period=Domain::digits(input('period',7));
  if(scalar('SELECT COUNT(*) FROM payroll WHERE teacher_id=? AND period>=?',[$teacher,$period]))throw new DomainException('closed_period');
  $calc=self::calculate($teacher,$period);if(date('Y-m-t',strtotime($period.'-01'))>=date('Y-m-d'))throw new DomainException('month_not_closed');
  q('INSERT INTO payroll(teacher_id,period,fixed_amount,variable_amount,total,explanation) VALUES(?,?,?,?,?,?)',[$teacher,$period,Domain::money($calc['fixed']),Domain::money($calc['variable']),Domain::money($calc['total']),json_encode($calc['lines'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$pid=(int)db()->lastInsertId();
  foreach($calc['sources'] as $s)q('INSERT INTO payroll_sources(payroll_id,agreement_id,allocation_id,refund_id,amount) VALUES(?,?,?,?,?)',array_merge([$pid],$s));audit('approve','payroll',$pid);
 }
 static function pay(): void {
  $token=requestToken();if(scalar('SELECT COUNT(*) FROM payroll_payments WHERE token=?',[$token]))return;$pid=id('payroll_id');$pay=one('SELECT * FROM payroll WHERE id=?',[$pid]);if(!$pay)throw new DomainException('not_found');
  $balance=Domain::cents($pay['total'])-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM payroll_payments WHERE payroll_id=?',[$pid]));
  $teacherNet=Domain::cents(scalar('SELECT COALESCE(SUM(total),0) FROM payroll WHERE teacher_id=?',[$pay['teacher_id']]))-Domain::cents(scalar('SELECT COALESCE(SUM(pp.amount),0) FROM payroll_payments pp JOIN payroll p ON p.id=pp.payroll_id WHERE p.teacher_id=?',[$pay['teacher_id']]));
  $money=amount();if(Domain::cents($money)>min($balance,$teacherNet))throw new DomainException('exceeds_balance');
  q('INSERT INTO payroll_payments(payroll_id,account_id,amount,date,token) VALUES(?,?,?,?,?)',[$pid,id('account_id'),$money,dateInput('date'),$token]);audit('pay','payroll',$pid,['amount'=>$money]);
 }
}
