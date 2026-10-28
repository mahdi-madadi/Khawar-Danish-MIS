<?php
declare(strict_types=1);
final class Domain {
    public static function personCode(string $kind,int $id,string $date): string {
        $year=self::g2j(...array_map('intval',explode('-',substr($date,0,10))))[0];
        return (['students'=>'KDAS','teachers'=>'KDAT','employees'=>'KDAE'][$kind]).'-'.substr((string)$year,-2).'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);
    }
    public static function digits(string $v): string {return strtr($v,array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩',-1,PREG_SPLIT_NO_EMPTY),str_split('01234567890123456789')));}
    public static function cents($v): int {
        $v=trim(self::digits((string)$v));
        if(!preg_match('/^(-?)(\d{1,10})(?:\.(\d{1,2}))?$/D',$v,$m)) throw new DomainException('invalid_money');
        return ($m[1]==='-'?-1:1)*((int)$m[2]*100+(int)str_pad($m[3]??'',2,'0'));
    }
    public static function money(int $v): string {return ($v<0?'-':'').intdiv(abs($v),100).'.'.str_pad((string)(abs($v)%100),2,'0',STR_PAD_LEFT);}
    public static function percent(int $cents,int $basisPoints): int {return ($cents<0?-1:1)*intdiv(abs($cents)*$basisPoints+5000,10000);}
    public static function overlaps(string $a,string $b,string $c,string $d): bool {return $a<$d && $c<$b;}
    public static function allowed(array $permissions,string $action): bool {return in_array('*',$permissions,true)||in_array($action,$permissions,true);}
    public static function csv($v): string {$v=(string)$v; return preg_match('/^[\s]*[=+@\-\t\r]/u',$v)?"'".$v:$v;}
    public static function g2j(int $gy,int $gm,int $gd): array {
        $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2=$gm>2?$gy+1:$gy;
        $days=355666+365*$gy+intdiv($gy2+3,4)-intdiv($gy2+99,100)+intdiv($gy2+399,400)+$gd+$gdm[$gm-1];
        $jy=-1595+33*intdiv($days,12053);$days%=12053;$jy+=4*intdiv($days,1461);$days%=1461;
        if($days>365){$jy+=intdiv($days-1,365);$days=($days-1)%365;}
        return [$jy,$days<186?1+intdiv($days,31):7+intdiv($days-186,30),1+($days<186?$days%31:($days-186)%30)];
    }
    public static function j2g(int $jy,int $jm,int $jd): array {
        $jy+=1595;$days=-355668+365*$jy+intdiv($jy,33)*8+intdiv(($jy%33)+3,4)+$jd+($jm<7?($jm-1)*31:($jm-7)*30+186);
        $gy=400*intdiv($days,146097);$days%=146097;
        if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}
        $gy+=4*intdiv($days,1461);$days%=1461;
        if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}
        $gd=$days+1;$months=[0,31,($gy%4==0&&($gy%100!=0||$gy%400==0))?29:28,31,30,31,30,31,31,30,31,30,31];
        for($gm=1;$gm<=12&&$gd>$months[$gm];$gm++)$gd-=$months[$gm];
        return [$gy,$gm,$gd];
    }
    public static function date(string $v,string $calendar='g'): string {
        $v=self::digits(trim($v));if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m))throw new DomainException('invalid_date');
        $y=(int)$m[1];$mo=(int)$m[2];$d=(int)$m[3];
        if($calendar==='j'){
            if($y<1300||$y>1499||$mo<1||$mo>12||$d<1||$d>31)throw new DomainException('invalid_date');
            [$gy,$gm,$gd]=self::j2g($y,$mo,$d);if(self::g2j($gy,$gm,$gd)!==[$y,$mo,$d])throw new DomainException('invalid_date');
            return sprintf('%04d-%02d-%02d',$gy,$gm,$gd);
        }
        if($y<1921||$y>2121||!checkdate($mo,$d,$y))throw new DomainException('invalid_date');return $v;
    }
    public static function displayDate(string $v,string $calendar): string {
        if(!$v)return '';if($calendar!=='j')return substr($v,0,10);$p=array_map('intval',explode('-',substr($v,0,10)));return vsprintf('%04d-%02d-%02d',self::g2j(...$p));
    }
    public static function month(string $v,string $calendar): array {
        if(!preg_match('/^\d{4}-\d{2}$/D',self::digits($v)))throw new DomainException('invalid_date');
        $start=self::date($v.'-01',$calendar);$out=[];
        for($i=0;$i<32;$i++){$d=date('Y-m-d',strtotime($start." +$i days"));if(substr(self::displayDate($d,$calendar),0,7)!==self::digits($v))break;$out[]=$d;}return $out;
    }
}
