<?php
// Field type: text, textarea, date, money, int, choice:a|b, ref:table.
return [
 'employees'=>['permission'=>'employees','fields'=>['name'=>'text','father_name'=>'text','job_title'=>'text','department'=>'?text','phone'=>'?text','address'=>'?textarea','dob'=>'?date','gender'=>'?choice:female|male','joining_date'=>'date','end_date'=>'?date','monthly_salary'=>'money','status'=>'choice:active|archived','notes'=>'?textarea','tazkira_type'=>'choice:not_available|electronic|paper','tazkira_number'=>'?text','tazkira_volume'=>'?text','tazkira_page'=>'?text','tazkira_registration'=>'?text','tazkira_province'=>'?text','tazkira_issue_date'=>'?date']],
 'students'=>['permission'=>'students','fields'=>['name'=>'text','father_name'=>'text','dob'=>'?date','gender'=>'?choice:female|male','phone'=>'?text','address'=>'?textarea','guardian'=>'?text','guardian_relation'=>'?text','guardian_phone'=>'?text','registered_date'=>'date','status'=>'choice:active|archived','notes'=>'?textarea','tazkira_type'=>'choice:not_available|electronic|paper','tazkira_number'=>'?text','tazkira_volume'=>'?text','tazkira_page'=>'?text','tazkira_registration'=>'?text','tazkira_province'=>'?text','tazkira_issue_date'=>'?date']],
 'teachers'=>['permission'=>'academic','fields'=>['name'=>'text','phone'=>'?text','qualifications'=>'?textarea','subjects'=>'?text','joining_date'=>'date','status'=>'choice:active|archived','tazkira_type'=>'choice:not_available|electronic|paper','tazkira_number'=>'?text','tazkira_volume'=>'?text','tazkira_page'=>'?text','tazkira_registration'=>'?text','tazkira_province'=>'?text','tazkira_issue_date'=>'?date']],
 'programs'=>['permission'=>'academic','fields'=>['name'=>'text','description'=>'?textarea','status'=>'choice:active|archived']],
 'subjects'=>['permission'=>'academic','fields'=>['name'=>'text','program_id'=>'ref:programs','status'=>'choice:active|archived']],
 'rooms'=>['permission'=>'academic','fields'=>['name'=>'text','capacity'=>'int','status'=>'choice:active|archived']],
 'batches'=>['permission'=>'academic','fields'=>['name'=>'text','program_id'=>'ref:programs','level'=>'?text','start_date'=>'date','end_date'=>'date','capacity'=>'int','fee'=>'money','status'=>'choice:active|completed|archived']],
 'class_subjects'=>['permission'=>'academic','fields'=>['batch_id'=>'ref:batches','subject_id'=>'ref:subjects','teacher_id'=>'ref:teachers','required'=>'choice:1|0']],
 'holidays'=>['permission'=>'academic','fields'=>['name'=>'text','date'=>'date']],
 'assessments'=>['permission'=>'exams','fields'=>['class_subject_id'=>'ref:class_subjects','name'=>'text','date'=>'date','max_marks'=>'money','pass_marks'=>'money','weight'=>'money']],
];
