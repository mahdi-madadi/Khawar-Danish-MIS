<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/Backup.php';
try {
 $exists=(int)scalar("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subjects' AND COLUMN_NAME='program_id'");
 if($exists){echo "Teaching group schema already installed.\n";exit;}
 $backup=tx(fn()=>Backup::create(true));echo 'Pre-upgrade database backup: '.$backup."\n";
 // MySQL DDL commits independently; this additive statement preserves every row.
 db()->exec('ALTER TABLE subjects ADD COLUMN program_id INT NULL, ADD CONSTRAINT fk_subject_course FOREIGN KEY(program_id) REFERENCES programs(id)');
 tx(function(){
  foreach(q('SELECT cs.subject_id,MIN(b.program_id) program_id FROM class_subjects cs JOIN batches b ON b.id=cs.batch_id GROUP BY cs.subject_id HAVING COUNT(DISTINCT b.program_id)=1') as $row)q('UPDATE subjects SET program_id=? WHERE id=? AND program_id IS NULL',[$row['program_id'],$row['subject_id']]);
  audit('upgrade_teaching_groups','subjects');
 });
 echo 'Upgrade complete. Subjects needing course selection: '.scalar('SELECT COUNT(*) FROM subjects WHERE program_id IS NULL')."\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
