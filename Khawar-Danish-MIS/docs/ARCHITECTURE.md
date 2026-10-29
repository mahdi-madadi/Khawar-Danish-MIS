# Architecture and schema overview

## Request flow

`public/index.php` is the only web entry. It loads bootstrap (PDO, sessions, translation, validation), validates CSRF on POST, checks action permission, serializes writes using the schema_versions row in a transaction, delegates to Academic/Finance/Admin services, audits changes and redirects after success. View helpers and page functions render server-side HTML. All assets are local.

This is a compact MVC-style organization, not a framework: front controller = controller/router; Domain/Academic/Finance/Admin/Backup = model/service layer; View/pages = views. Some SQL remains in report views. A stricter repository layer and separate controllers are future refactoring opportunities.

## Core relationships

| Parent | Child / relationship | Cardinality and policy |
|---|---|---|
| roles | users.role_id | One role per user; role 1 protected |
| teachers | users.teacher_id | Optional assignment restriction |
| programs | batches.program_id | Program has running classes |
| batches + subjects + teachers | class_subjects | One subject assignment per class; immutable in 0.1 |
| students + batches | enrollments | Unique student/class pair; dates retain history |
| class_subjects + rooms | timetable | Recurring weekday, time interval, date interval |
| enrollments + class_subjects | attendance | Unique enrollment/subject/date; daily model |
| enrollments | charges | Separate registration/tuition demands |
| charges | credits | Immutable reductions of unpaid charges |
| students + accounts | receipts | Cash received with unique request token |
| receipts + charges | allocations | Immutable mapping; one pair per receipt/charge |
| receipts + allocations | refunds | Allocation nullable for an advance refund |
| teachers + batches | agreements | Effective date range, fixed and percentage amounts |
| teachers | payroll | Unique teacher/Gregorian month, approved snapshot |
| payroll + agreements | payroll_sources | Unique agreement/allocation or agreement/refund |
| payroll + accounts | payroll_payments | Separate cash event with unique request token |
| accounts | movements | Income, expense, transfer, linked reversal |
| accounts | reconciliations | Expected and actual balance observation |
| class_subjects | assessments | Maximum, pass mark, weight and date |
| assessments + enrollments | marks | Unique pair; graded/absent/exempt |
| enrollments | certificates | Issue number, text, revocation and recorded print count |

FKs use restrictive default behavior. There is no general record deletion UI. Master statuses allow archiving where applicable. Financial rows are never edited through generic CRUD. The database application account can still modify records directly; SQL-level immutability/audit tamper resistance is not provided.

## Dates and identifiers

Gregorian DATE columns, Asia/Kabul application timezone; Shamsi conversion and input validation in Domain. Supported display/input years are documented. Student ID displays as `KD-000001` from the database auto-increment ID. Receipt `RC-00000001`; certificate `CT-00000001`. There are no year resets or public verification service.

## Concurrency and transactions

All front-controller domain writes acquire `SELECT ... schema_versions ... FOR UPDATE` before making multi-step changes. InnoDB handles rollback. This simple global serialization avoids capacity/overlap/payment races at small scale. It is not a high-throughput design. The central dispatcher's permissions are required even if UI controls are hidden.

Unique request tokens protect receipts, refunds, payroll payments and general movements. Other creation forms are not universally idempotent. A newly loaded form intentionally has a new token; staff must check history before intentionally resubmitting after a connection failure.

## Data protection

Only public is served. Config, logs, backups, CLI utilities and app source are outside the web alias and additionally have deny rules. Cookies are HttpOnly and SameSite Strict; Secure is enabled for HTTPS. Passwords use PHP's current PASSWORD_DEFAULT. Session identity regenerates at login; role/user changes invalidate old sessions through auth_version. No secrets are stored in audit details.

## Schema updates

Initial numbered SQL file = version 1. The installer refuses an existing users table. Future migrations must be numbered, reviewed, tested on a clone, and applied after a verified backup, updating schema_versions only on success. MySQL DDL can auto-commit; do not assume a transaction can roll back an entire schema migration. No previous application schema compatibility is claimed.


Schedule, attendance, student activity and teacher salary update (29 October 2026)

Identical subject/time/room/date ranges display once with all teaching weekdays. Daily conflict validation is preserved. Monthly attendance and paper sheets show each weekday beside its day number.
Issued completion and appreciation certificates also include English recognition text. Names retain their stored spelling.
Dashboard active/inactive counts and expandable full lists are derived on every visit. Active means an administratively active student with an active enrollment in an active batch covering today; both enrollment and batch dates apply. Ended courses automatically cease qualifying; renewal in a current course restores activity. Payment alone does not establish activity. New students without a current enrollment and future-only enrollments are inactive for now.
Teacher fixed salary uses agreed amount / 30 × covered calendar days, rounded to cents. A 3000 AFN amount yields 2800 for 28 days and 3100 for 31 days. Partial contracts count only covered days. Previously approved payroll remains unchanged. Payroll periods use Gregorian months. Administrative employee payroll is unchanged.
