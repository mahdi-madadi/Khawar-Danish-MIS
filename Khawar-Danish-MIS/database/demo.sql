-- FICTIONAL ONLY. Import through `php bin/console.php demo` into empty academic tables.
INSERT INTO programs(id,name,description) VALUES(1,'English · انگلیسی','Fictional demo'),(2,'Kankor · کانکور','Fictional demo');
INSERT INTO subjects(id,name,program_id) VALUES(1,'Grammar · گرامر',1),(2,'Mathematics · ریاضی',2);
INSERT INTO teachers(id,name,phone,qualifications,joining_date) VALUES(1,'استاد نمونه الف','0000000000','Demo only','2026-01-01'),(2,'استاد نمونه ب','0000000000','Demo only','2026-01-01');
INSERT INTO rooms(id,name,capacity) VALUES(1,'Room A · اتاق الف',30),(2,'Room B · اتاق ب',30);
INSERT INTO batches(id,name,program_id,level,start_date,end_date,capacity,fee) VALUES(1,'English A · انگلیسی الف',1,'A1','2026-01-01','2026-12-31',25,1500),(2,'Kankor A · کانکور الف',2,'1','2026-01-01','2026-12-31',25,2000);
INSERT INTO class_subjects(id,batch_id,subject_id,teacher_id) VALUES(1,1,1,1),(2,2,2,2);
INSERT INTO students(id,name,father_name,registered_date,guardian,phone) VALUES(1,'شاگرد نمونه الف','پدر نمونه الف','2026-01-01','سرپرست نمونه','0000000000'),(2,'شاگرد نمونه ب','پدر نمونه ب','2026-01-01','سرپرست نمونه','0000000000');
INSERT INTO enrollments(id,student_id,batch_id,start_date) VALUES(1,1,1,'2026-01-01'),(2,2,1,'2026-01-01');
INSERT INTO charges(enrollment_id,kind,description,amount,due_date) VALUES(1,'tuition','Demo tuition · فیس نمونه',1500,'2026-01-01'),(2,'tuition','Demo tuition · فیس نمونه',1500,'2026-01-01');
INSERT INTO timetable(class_subject_id,room_id,weekday,start_time,end_time,start_date,end_date) VALUES(1,1,6,'08:00','09:00','2026-01-01','2026-12-31'),(1,1,1,'08:00','09:00','2026-01-01','2026-12-31'),(2,2,6,'08:00','09:00','2026-01-01','2026-12-31');
INSERT INTO agreements(teacher_id,batch_id,start_date,end_date,fixed_amount,percent) VALUES(1,1,'2026-01-01','2026-12-31',1000,20),(2,2,'2026-01-01','2026-12-31',2000,0);
INSERT INTO assessments(class_subject_id,name,date,max_marks,pass_marks,weight) VALUES(1,'Demo final · امتحان نمونه','2026-10-20',100,50,1);
