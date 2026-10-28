<?php
// Photos live outside the public directory and are served after authorization.
function personCode(string $kind,int $id): string {
 static $cache=[];$key=$kind.':'.$id;if(isset($cache[$key]))return $cache[$key];
 if(!in_array($kind,['students','teachers','employees'],true))throw new LogicException('Invalid person');
 $row=one("SELECT * FROM `$kind` WHERE id=?",[$id]);
 return $cache[$key]=$row['public_code']??Domain::personCode($kind,$id,$row[$kind==='students'?'registered_date':'joining_date']??date('Y-m-d'));
}
function ensurePersonCode(string $kind,int $id): void {
 $code=personCode($kind,$id);q("UPDATE `$kind` SET public_code=? WHERE id=? AND public_code IS NULL",[$code,$id]);
}
function personPhoto(string $kind,int $id): string {
 static $cache=[];if(!$id)return personPlaceholder();
 if(!in_array($kind,['students','teachers','employees','users'],true))throw new LogicException('Invalid person');
 $key=$kind.':'.$id;if(!array_key_exists($key,$cache))$cache[$key]=$kind==='users'?one('SELECT CASE WHEN u.employee_id IS NOT NULL THEN em.photo_path ELSE u.photo_path END photo_path,COALESCE(em.name,u.name) name FROM users u LEFT JOIN employees em ON em.id=u.employee_id WHERE u.id=?',[$id]):one("SELECT photo_path,name FROM `$kind` WHERE id=?",[$id]);$row=$cache[$key];
 return !empty($row['photo_path'])?'<img class="person-photo" src="'.e(url('photo',['kind'=>$kind,'id'=>$id,'v'=>substr(hash('sha256',$row['photo_path']),0,12)])).'" alt="'.e($row['name']).'" loading="lazy">':personPlaceholder();
}
function personPlaceholder(): string {
 return '<img class="person-photo avatar-placeholder" src="assets/person-placeholder.svg?v=1" alt="'.e(t('no_photo')).'" width="48" height="48">';
}
function photoField(string $kind,int $id): void {
 if($id&&in_array($kind,['students','teachers','employees'],true))echo '<div class="wide"><bdi>'.e(personCode($kind,$id)).'</bdi></div>';
 echo '<div class="field photo-editor">'.str_replace('<img ','<img data-photo-preview ',personPhoto($kind,$id)).'<label for="person-photo">'.e(t('photo')).'</label><input id="person-photo" type="file" name="photo" accept="image/jpeg,image/png"><small>'.e(t('photo_hint')).'</small><label><input type="checkbox" name="remove_photo" value="1"> '.e(t('remove_photo')).'</label></div>';
}
function savePhoto(string $kind,int $id): void {
 if(!in_array($kind,['students','teachers','employees','users'],true))throw new LogicException('Invalid person');
 $file=$_FILES['photo']??null;
 if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){if(isset($_POST['remove_photo']))q("UPDATE `$kind` SET photo_path=NULL WHERE id=?",[$id]);return;}
 if(!is_scalar($file['error'])||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'])||$file['size']>2*1024*1024)throw new DomainException('invalid_photo');
 $info=@getimagesize($file['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
 if(!$info||!in_array($mime,['image/jpeg','image/png'],true)||$info['mime']!==$mime||$info[0]>6000||$info[1]>6000)throw new DomainException('invalid_photo');
 $name=bin2hex(random_bytes(16)).($mime==='image/png'?'.png':'.jpg');$dir=dirname(__DIR__).'/storage/uploads';
 if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Photo directory');
 if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Photo write');
 q("UPDATE `$kind` SET photo_path=? WHERE id=?",[$name,$id]);
}
function servePersonPhoto(): never {
 $kind=(string)($_GET['kind']??'');$id=id('id');
 if($kind==='students'){need('students.view');if(user()['teacher_id']&&!can('*'))throw new DomainException('forbidden');}
 elseif($kind==='employees'){need('employees.view');}elseif($kind==='teachers'){need('academic.view');if(user()['teacher_id']&&!can('*')&&$id!==(int)user()['teacher_id'])throw new DomainException('forbidden');}elseif($kind==='users'){if($id!==(int)user()['id'])need('users.manage');}else throw new DomainException('not_found');
 $name=$kind==='users'?scalar('SELECT CASE WHEN u.employee_id IS NOT NULL THEN em.photo_path ELSE u.photo_path END FROM users u LEFT JOIN employees em ON em.id=u.employee_id WHERE u.id=?',[$id]):scalar("SELECT photo_path FROM `$kind` WHERE id=?",[$id]);
 if(!is_string($name)||!preg_match('/^[a-f0-9]{32}\.(jpg|png)$/D',$name))throw new DomainException('not_found');
 $path=dirname(__DIR__).'/storage/uploads/'.$name;if(!is_file($path))throw new DomainException('not_found');
 header('Content-Type: '.(str_ends_with($name,'.png')?'image/png':'image/jpeg'));header('Content-Length: '.filesize($path));readfile($path);exit;
}
function dashboardCalendar(): void {
 $calendar=($_GET['dash_cal']??cal())==='g'?'g':'j';$today=date('Y-m-d');$month=(string)($_GET['dash_month']??substr(Domain::displayDate($today,$calendar),0,7));
 try{$days=Domain::month($month,$calendar);}catch(DomainException $e){$month=substr(Domain::displayDate($today,$calendar),0,7);$days=Domain::month($month,$calendar);}
 $first=$days[0];$last=end($days);$prev=substr(Domain::displayDate(date('Y-m-d',strtotime($first.' -1 day')),$calendar),0,7);$next=substr(Domain::displayDate(date('Y-m-d',strtotime($last.' +1 day')),$calendar),0,7);
 $names=$calendar==='j'?['حمل','ثور','جوزا','سرطان','اسد','سنبله','میزان','عقرب','قوس','جدی','دلو','حوت']:['January','February','March','April','May','June','July','August','September','October','November','December'];
 panel('calendar');echo '<div class="calendar-toolbar"><strong>'.e($names[(int)substr($month,5,2)-1].' '.substr($month,0,4)).'</strong><div class="actions">';
 foreach(['j'=>'shamsi','g'=>'gregorian'] as $key=>$label)echo linkTo($label,'dashboard',['dash_cal'=>$key],'button '.($calendar===$key?'':'secondary'));
 echo linkTo('previous','dashboard',['dash_cal'=>$calendar,'dash_month'=>$prev]).linkTo('today','dashboard',['dash_cal'=>$calendar]).linkTo('next','dashboard',['dash_cal'=>$calendar,'dash_month'=>$next]).'</div></div><div class="calendar-grid">';
 foreach([6,0,1,2,3,4,5] as $w)echo '<strong>'.e(t('day'.$w)).'</strong>';
 $offset=((int)date('w',strtotime($first))+1)%7;for($i=0;$i<$offset;$i++)echo '<span></span>';
 foreach($days as $day){$display=Domain::displayDate($day,$calendar);echo '<span class="calendar-day'.($day===$today?' is-today':'').'"'.($day===$today?' aria-current="date"':'').'><bdi>'.e((int)substr($display,8,2)).'</bdi><small title="'.e(Domain::displayDate($day,$calendar==='j'?'g':'j')).'"><bdi class="calendar-secondary-full">'.e(Domain::displayDate($day,$calendar==='j'?'g':'j')).'</bdi><bdi class="calendar-secondary-compact" aria-hidden="true">'.e(substr(Domain::displayDate($day,$calendar==='j'?'g':'j'),5)).'</bdi></small></span>';}
 echo '</div>';endPanel();
}
function moduleSortLink(string $module,string $field,string $label,string $sort,string $dir,string $search): string {
 return '<a href="'.e(url($module,['q'=>$search,'sort'=>$field,'dir'=>$sort===$field&&$dir==='asc'?'desc':'asc'])).'">'.e($label).($sort===$field?($dir==='asc'?' ↑':' ↓'):' ↕').'</a>';
}
