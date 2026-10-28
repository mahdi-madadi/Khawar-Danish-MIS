<?php
declare(strict_types=1);

function validateTazkira(array &$values): void {
 if(($values['tazkira_type']??'not_available')!=='paper')foreach(['tazkira_volume','tazkira_page','tazkira_registration'] as $key)$values[$key]=null;
 if(($values['tazkira_type']??'not_available')!=='not_available'&&empty($values['tazkira_number']))throw new DomainException('tazkira_number_required');
 if(($values['tazkira_type']??'not_available')==='not_available'&&!empty($values['tazkira_number']))throw new DomainException('choose_tazkira_type');
}
function tazkiraFields(array $fields,array $record): void {
 echo '<fieldset class="wide tazkira-fields"><legend>'.e(t('tazkira_details')).'</legend><p class="muted">'.e(t('tazkira_hint')).'</p><div class="form-grid">';
 $paper=['tazkira_volume','tazkira_page','tazkira_registration'];
 foreach($fields as $key=>$type)if(str_starts_with($key,'tazkira_')&&$key!=='tazkira_district'&&!in_array($key,$paper,true))field($key,$type,$record[$key]??($key==='tazkira_type'?'not_available':''));
 echo '<div class="conditional-fields wide" data-when="tazkira_type:paper"'.(formValue('tazkira_type',$record['tazkira_type']??'not_available')==='paper'?'':' hidden').'>';
 foreach($paper as $key)field($key,$fields[$key],$record[$key]??'');echo '</div>';
 echo '</div></fieldset>';
}
function showPersonField(string $key,array $record): bool {
 if($key==='tazkira_district')return false;
 return !in_array($key,['tazkira_volume','tazkira_page','tazkira_registration'],true)||($record['tazkira_type']??'not_available')==='paper';
}
function employeePage(): void {
 need('employees.view');$id=id('id');$row=one('SELECT * FROM employees WHERE id=?',[$id]);if(!$row)throw new DomainException('not_found');
 panel('employee_profile');echo '<div class="student-heading">'.personPhoto('employees',$id).'<div><h2>'.e($row['name']).'</h2><bdi>'.e(personCode('employees',$id)).'</bdi><span class="badge">'.e(t($row['status'])).'</span></div></div><div class="actions no-print">';
 if(can('employees.update'))echo linkTo('edit','employees',['id'=>$id,'edit'=>1]);if(can('payroll.view'))echo linkTo('employee_payroll','employee_payroll',['employee_id'=>$id]);echo '</div>';
 $rows=[];$modules=require __DIR__.'/modules.php';
 foreach($modules['employees']['fields'] as $key=>$type)if(showPersonField($key,$row))$rows[]=[t($key),str_contains($type,'date')?fdate($row[$key]):(str_contains($type,'choice:')?t((string)$row[$key]):$row[$key])];
 table(['field','value'],$rows);endPanel();
}
final class EmployeePayroll {
 static function calculate(int $employee,string $period): array {
  if(!preg_match('/^20\d\d-(0[1-9]|1[0-2])$/D',$period))throw new DomainException('invalid_date');
  $row=one('SELECT * FROM employees WHERE id=?',[$employee]);if(!$row)throw new DomainException('not_found');
  if($row['status']==='archived'&&!$row['end_date'])throw new DomainException('employment_end_required');
  $start=$period.'-01';$end=date('Y-m-t',strtotime($start));$coveredStart=max($start,$row['joining_date']);$coveredEnd=min($end,$row['end_date']?:$end);
  if($coveredStart>$coveredEnd)throw new DomainException('outside_employment');
  $days=(int)date('t',strtotime($start));$covered=(int)round((strtotime($coveredEnd)-strtotime($coveredStart))/86400)+1;
  $monthly=Domain::cents($row['monthly_salary']);$total=intdiv($monthly*$covered+intdiv($days,2),$days);
  return ['employee_id'=>$employee,'name'=>$row['name'],'period'=>$period,'monthly_salary'=>Domain::money($monthly),'covered_days'=>$covered,'month_days'=>$days,'total'=>Domain::money($total)];
 }
 static function approve(): int {
  $employee=id('employee_id');$period=Domain::digits(input('period',7));$calc=self::calculate($employee,$period);
  if(date('Y-m-t',strtotime($period.'-01'))>=date('Y-m-d'))throw new DomainException('month_not_closed');
  if(scalar('SELECT COUNT(*) FROM employee_payroll WHERE employee_id=? AND period>=?',[$employee,$period]))throw new DomainException('closed_period');
  q('INSERT INTO employee_payroll(employee_id,period,monthly_salary,covered_days,month_days,total) VALUES(?,?,?,?,?,?)',[$employee,$period,$calc['monthly_salary'],$calc['covered_days'],$calc['month_days'],$calc['total']]);
  $id=(int)db()->lastInsertId();audit('approve','employee_payroll',$id);return $id;
 }
 static function balance(int $id): int {
  $row=one('SELECT total FROM employee_payroll WHERE id=?',[$id]);if(!$row)throw new DomainException('not_found');
  return Domain::cents($row['total'])-Domain::cents(scalar('SELECT COALESCE(SUM(amount),0) FROM employee_payroll_payments WHERE employee_payroll_id=?',[$id]));
 }
 static function pay(): void {
  $token=requestToken();if(scalar('SELECT COUNT(*) FROM employee_payroll_payments WHERE token=?',[$token]))return;
  $id=id('employee_payroll_id');$money=amount();if(Domain::cents($money)>self::balance($id))throw new DomainException('exceeds_balance');
  $period=(string)scalar('SELECT period FROM employee_payroll WHERE id=?',[$id]);$date=dateInput('date');if($date<$period.'-01')throw new DomainException('invalid_date');
  q('INSERT INTO employee_payroll_payments(employee_payroll_id,account_id,amount,date,reference,token) VALUES(?,?,?,?,?,?)',[$id,id('account_id'),$money,$date,input('reference',160,false),$token]);audit('pay','employee_payroll',$id,['amount'=>$money]);
 }
}
function payrollEmployeeField(int $selected): void {
 echo '<div class="field"><label for="payroll-employee">'.e(t('employee_id')).' •</label><select id="payroll-employee" name="employee_id" required><option value="">—</option>';
 $selected=(int)formValue('employee_id',$selected);
 foreach(q('SELECT id,name,job_title,status FROM employees ORDER BY name') as $row)echo '<option value="'.e($row['id']).'"'.($selected===(int)$row['id']?' selected':'').'>'.e($row['name'].' / '.$row['job_title'].' / '.t($row['status'])).'</option>';echo '</select></div>';
}
function employeePayrollPage(): void {
 need('payroll.view');$employee=(int)($_GET['employee_id']??0);$period=(string)($_GET['period']??date('Y-m',strtotime('first day of last month')));$selectedPay=(int)($_GET['employee_payroll_id']??0);
 panel('employee_payroll');echo '<p class="muted">'.e(t('employee_payroll_hint')).'</p><form class="form-grid no-print"><input type="hidden" name="r" value="employee_payroll">';payrollEmployeeField($employee);field('period','month',$period);formEnd('preview');
 $approved=null;
 if($employee){$period=Domain::digits($period);$approved=one('SELECT * FROM employee_payroll WHERE employee_id=? AND period=?',[$employee,$period]);$calc=$approved?:EmployeePayroll::calculate($employee,$period);echo '<h3>'.e(t($approved?'approved_snapshot':'preview')).' · <bdi>'.e($period).'</bdi></h3>';table(['monthly_salary','covered_days','month_days','total'],[[$calc['monthly_salary'],$calc['covered_days'],$calc['month_days'],$calc['total']]]);
  if(!$approved&&can('payroll.create')){formStart('approve_employee_payroll',['employee_id'=>$employee,'period'=>$period]);formEnd('approve');}
 }
 endPanel();panel('approved_employee_payroll');$rows=[];
 foreach(q('SELECT p.*,e.name FROM employee_payroll p JOIN employees e ON e.id=p.employee_id ORDER BY p.period DESC,p.id DESC') as $row){$balance=EmployeePayroll::balance((int)$row['id']);$links=[];if(can('payroll.create')&&$balance>0)$links[]=['pay','employee_payroll',['employee_payroll_id'=>$row['id']]];if(can('print'))$links[]=['employee_payslip','employee_payslip',['id'=>$row['id']]];$rows[]=[[$row['id'],$row['name'],$row['period'],$row['total'],Domain::money(Domain::cents($row['total'])-$balance),Domain::money($balance)],$links];}
 actionTable(['id','employees','period','total','paid','balance'],$rows);endPanel();
 $selectedPay=$selectedPay?:($approved['id']??0);
 if(can('payroll.create')&&$selectedPay){$pay=one('SELECT p.*,e.name FROM employee_payroll p JOIN employees e ON e.id=p.employee_id WHERE p.id=?',[$selectedPay]);if(!$pay)throw new DomainException('not_found');panel('pay_employee_salary');echo '<p>'.e($pay['name']).' · <bdi>'.e($pay['period']).'</bdi> · '.e(t('balance')).': '.e(Domain::money(EmployeePayroll::balance((int)$selectedPay))).' AFN</p>';formStart('employee_pay',['employee_payroll_id'=>$selectedPay]);field('account_id','ref:accounts');field('amount','money',Domain::money(EmployeePayroll::balance((int)$selectedPay)));field('date','date');field('reference','?text');formEnd('pay');endPanel();}
 panel('employee_salary_payments');$payments=q('SELECT pp.id,e.name,p.period,a.name account,pp.amount,pp.date,pp.reference FROM employee_payroll_payments pp JOIN employee_payroll p ON p.id=pp.employee_payroll_id JOIN employees e ON e.id=p.employee_id JOIN accounts a ON a.id=pp.account_id ORDER BY pp.id DESC')->fetchAll();
 foreach($payments as &$row)$row['date']=fdate($row['date']);unset($row);table(['id','employees','period','account_id','amount','date','reference'],$payments);endPanel();
}
function employeePayslipPage(): void {
 need('payroll.view');need('print');$id=id('id');$pay=one('SELECT p.*,e.name,e.father_name,e.job_title,e.public_code FROM employee_payroll p JOIN employees e ON e.id=p.employee_id WHERE p.id=?',[$id]);if(!$pay)throw new DomainException('not_found');
 $balance=EmployeePayroll::balance($id);echo '<article class="panel employee-payslip print-document"><header class="result-card-header"><img class="kda-document-logo" src="assets/kda-logo.svg?v=1" alt="KDA" width="64" height="64"><div><h2>'.e(setting(lang()==='en'?'center_name_en':'center_name')).'</h2><h3>'.e(t('employee_payslip')).'</h3></div></header>';
 table(['field','value'],[[t('employees'),$pay['name']],[t('father_name'),$pay['father_name']],[t('job_title'),$pay['job_title']],[t('employee_id'),$pay['public_code']],[t('period'),$pay['period'].' — '.t('gregorian')],[t('monthly_salary'),$pay['monthly_salary']],[t('covered_days'),$pay['covered_days'].' / '.$pay['month_days']],[t('total'),$pay['total']],[t('paid'),Domain::money(Domain::cents($pay['total'])-$balance)],[t('balance'),Domain::money($balance)]]);
 $rows=q('SELECT pp.date,a.name,pp.amount,pp.reference FROM employee_payroll_payments pp JOIN accounts a ON a.id=pp.account_id WHERE pp.employee_payroll_id=? ORDER BY pp.id',[$id])->fetchAll();foreach($rows as &$row)$row['date']=fdate($row['date']);unset($row);table(['date','account_id','amount','reference'],$rows);echo '<div class="signature"><span>'.e(t('employee_signature')).' __________________</span><span>'.e(t('signature')).' __________________</span></div></article>';
}
