<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Domain.php';
$passed=0;
function check($actual,$expected,string $label): void {global $passed;if($actual!==$expected){fwrite(STDERR,"FAIL $label: ".var_export($actual,true)."\n");exit(1);}echo "PASS $label\n";$passed++;}
function rejects(callable $fn,string $label): void {try{$fn();}catch(DomainException $e){check(true,true,$label);return;}check(false,true,$label);}
check(Domain::cents('۱۲۳.۴۵'),12345,'Persian amount');check(Domain::cents('١٢.٣'),1230,'Arabic digits');check(Domain::money(-101),'-1.01','negative money');check(Domain::percent(10001,1250),1250,'percentage rounding');check(Domain::percent(5,1000),1,'half up');rejects(fn()=>Domain::cents('1e3'),'reject scientific notation');rejects(fn()=>Domain::cents('1.001'),'reject sub-cent');rejects(fn()=>Domain::cents('nan'),'reject NaN');
check(Domain::date('۱۴۰۳-۰۱-۰۱','j'),'2024-03-20','Nowruz 1403');check(Domain::date('1404-01-01','j'),'2025-03-21','Nowruz 1404');check(Domain::date('1399-12-30','j'),'2021-03-20','leap Esfand');rejects(fn()=>Domain::date('1400-12-30','j'),'non-leap Esfand');rejects(fn()=>Domain::date('2025-02-29','g'),'invalid Gregorian date');rejects(fn()=>Domain::date('1404-13-01','j'),'invalid month');check(count(Domain::month('1403-01','j')),31,'Farvardin days');check(count(Domain::month('1399-12','j')),30,'leap Esfand days');check(count(Domain::month('1400-12','j')),29,'ordinary Esfand days');check(count(Domain::month('2024-02','g')),29,'February leap');
for($y=1922;$y<=2120;$y++)for($m=1;$m<=12;$m++){[$jy,$jm,$jd]=Domain::g2j($y,$m,15);if(Domain::j2g($jy,$jm,$jd)!==[$y,$m,15])throw new RuntimeException('roundtrip');}check(true,true,'2388 date round trips');
check(Domain::overlaps('08:00','09:00','09:00','10:00'),false,'adjacent sessions allowed');check(Domain::overlaps('08:00','10:00','09:00','11:00'),true,'overlapping sessions rejected');check(Domain::allowed(['students.view'],'finance.create'),false,'deny unauthorized action');check(Domain::allowed(['*'],'finance.refund'),true,'administrator access');check(Domain::allowed(['attendance.create'],'attendance.create'),true,'explicit permission');check(Domain::csv('=1+1'),"'=1+1",'CSV formula neutralization');check(Domain::csv('  @SUM(A1)'),"'  @SUM(A1)",'CSV whitespace neutralization');
check(Domain::personCode('students',123,'2026-10-28'),'KDAS-05-000123','student identity uses Solar Hijri registration year');
check(Domain::personCode('teachers',999999,'2026-03-20'),'KDAT-04-999999','teacher identity before Nowruz');
check(Domain::personCode('teachers',1000000,'2026-03-21'),'KDAT-05-1000000','identity expands without truncation');
check(Domain::personCode('employees',12,'2026-10-28'),'KDAE-05-000012','administrative employee identity');
echo "ALL $passed CHECKS PASSED\n";
