# Local acceptance checklist — NOT executed at build time

Use a disposable installation. Record date, PHP/database/browser versions, actual result, screenshot/receipt reference and pass/fail for every case. Do not turn these unchecked items into passed claims.

## Environment and first login

- [ ] PHP syntax lint passes for every file; required extensions load.
- [ ] Empty DB install succeeds; repeating install refuses without data loss.
- [ ] Admin creation succeeds with a chosen password; a second first-admin creation is refused.
- [ ] Password hashes are stored, never plaintext. Failed sign-in throttling activates; success and failure audit events exist.
- [ ] Default UI is Dari RTL; English switch and Gregorian/Shamsi switch work.
- [ ] Disconnect internet before the full scenario. Browser network panel shows only local application assets/requests.

## Academic and attendance

- [ ] Add program, subject, two teachers, room and class.
- [ ] Create assigned subject, then student and enrollment. Capacity is enforced.
- [ ] Duplicate student warning appears; duplicate student/class enrollment is refused.
- [ ] Register a second subject: no extra fee is automatically charged.
- [ ] Schedule 08:00–09:00, then 09:00–10:00: allowed. Schedule 08:30–09:30 for the same teacher, room or batch: rejected.
- [ ] Nonoverlapping date ranges do not conflict; partially overlapping ranges do.
- [ ] Two simultaneous conflicting schedule submissions leave one valid record.
- [ ] Record present/absent/late/excused, reload, correct one record, verify no duplicates.
- [ ] Withdraw at an inclusive end date. Earlier rosters retain the student; later rosters exclude the student.
- [ ] Transfer to another class preserves old enrollment/charges and adds only the explicitly entered new fee.
- [ ] Print Shamsi month 1403-01 (31 days), 1399-12 (30), 1400-12 (29) and Gregorian 2024-02 (29).
- [ ] Verify holidays, enrollment boundaries, nonmeeting days and totals in register; test a makeup day.
- [ ] Confirm same-subject multiple meetings on one date share daily attendance: record as a known unmet requirement.

## Finance and payroll

- [ ] Charge 1,500; discount 100; receipt 1,000 → due 400, cash +1,000.
- [ ] Repost same receipt token, including concurrent browser replay → one receipt and allocations only.
- [ ] Receipt exceeding charges creates advance; a new charge can consume advance using the allocation action.
- [ ] Receipt covers tuition and registration: teacher percentage excludes registration.
- [ ] Full-month agreement fixed 1,000 + 20% on 1,000 tuition → 1,200 earned.
- [ ] Approve a closed month; repeat fails without duplicate sources. Next month's calculation does not recount the same allocation.
- [ ] Refund 200 after approval → old payroll unchanged; next payroll percentage adjustment −40; unpaid charge increases 200; cash decreases 200.
- [ ] Test many fractional refunds; total percentage reversal does not exceed originally earned percentage.
- [ ] Excessive refund, discount or payroll payment is refused with no partial records.
- [ ] Same payroll payment token twice → one payment. Salary appears once in cash report, not in both general expenses and payroll.
- [ ] Two teachers share tuition; overlapping combined share >100% is refused.
- [ ] Fixed-salary partial month includes both boundary dates and rounds to hundredths.
- [ ] Record general expense; reverse it with a reason → original retained, opposite movement linked.
- [ ] Transfer cash to bank → total cash/bank unchanged, surplus unchanged.
- [ ] Reconciliation difference is recorded but does not alter the balance.
- [ ] CSV opens with Persian text and formula-like content neutralized.
- [ ] Identify required corrective workflows absent from version 0.1; do not mark them passed.

## Exams and documents

- [ ] Create assessment; score above maximum and duplicate impossible data are rejected.
- [ ] Enter absence and exemption. Verify weighted subject result and missing-data status.
- [ ] Complete enrollment and issue certificate; unique number persists; revoke and record reprint are audited.
- [ ] Print Dari and English registration, consent, ID, receipt, register, results, certificate, timetable and finance pages.
- [ ] A4 margins, long names, 60-student multipage tables, repeated headers, mixed Latin IDs and Persian names are readable without clipped columns.
- [ ] Record missing consolidated report card, enrollment confirmation and salary document templates as not implemented.

## Authorization and recovery

- [ ] Test each default role in a separate browser session. Try forbidden GET and POST routes directly, with valid CSRF; expect no unauthorized data or write.
- [ ] Assigned teacher cannot load another teacher's class by changing class_subject_id, assessment ID or POST body.
- [ ] Missing/wrong CSRF fails; GET does not mutate domain records (audit downloads excepted).
- [ ] Role/user changes invalidate sessions. Last active administrator cannot be demoted or deactivated.
- [ ] Idle timeout expires the session; logout prevents subsequent protected access.
- [ ] Local reset-admin succeeds only via server-side CLI access; no web recovery backdoor exists.
- [ ] Try HTTP access to configuration, logs, backups, SQL files, tests and CLI utilities: not accessible.

## Backup/restore

- [ ] Create and download backup as admin; restricted user cannot create/download it.
- [ ] Save counts and representative balances. Stop Apache; restore into a spare same-version installation.
- [ ] Pre-restore ZIP exists; counts, balances, IDs, hashes and configuration behavior match expectations.
- [ ] Corrupted manifest, missing table, invalid column, unknown archive path and oversize backup are refused.
- [ ] Restore failure rolls back DB changes; old information remains usable.
- [ ] Successful restore invalidates prior sessions and records restore audit.
- [ ] Rerun disconnected-network workflow after restoration.

Acceptance requires executed evidence and resolution of material gaps, not only the presence of source code or a checked box.


به‌روزرسانی تقسیم اوقات، حاضری و شاگردان — ۷ عقرب ۱۴۰۵

تقسیم اوقات: روزهای یک مضمون با ساعت، اتاق و تاریخ یکسان در یک سطر نمایش داده می‌شود. روزهای هفته در همان سطر نوشته می‌شود؛ بررسی تداخل هر روز همچنان جداگانه صورت می‌گیرد.
حاضری: در حاضری ماهانه و ورق چاپی، زیر شماره هر روز نام روز هفته نیز درج می‌شود. تعداد روزها مطابق ماه انتخاب‌شده است.
تقدیرنامه و تصدیق‌نامه تکمیل دوره: متن تقدیر یا تکمیل دوره انگلیسی نیز همراه متن فارسی چاپ می‌شود. برای نام انگلیسی شاگرد و دوره، املای لاتین را هنگام ثبت معلومات وارد کنید؛ سیستم نام اشخاص را ترجمه نمی‌کند.
شاگرد فعال: حداقل یک ثبت‌نام فعال دارد که تاریخ امروز در محدوده ثبت‌نام و دوره فعال باشد. پس از ختم آخرین دوره یا قطع ثبت‌نام، خودکار در فهرست غیر فعال قرار می‌گیرد. ثبت‌نام مجدد در یک دوره جاری او را دوباره فعال می‌سازد. پرداخت پول به تنهایی وضعیت فعالیت را تعیین نمی‌کند. شاگرد تازه که هنوز شامل دوره نشده یا فقط دوره آینده دارد، فعلاً غیر فعال است. تعداد و فهرست هر دو گروه در صفحه اول، از «نمایش فهرست»، قابل مشاهده است. وضعیت اداری آرشیف‌شده مانع فعالیت است.
معاش ثابت استاد: مبلغ ثابت قرارداد مبنای ۳۰ روز دارد. مبلغ قابل پرداخت = مبلغ ثابت ÷ ۳۰ × روزهای تقویمی تحت پوشش قرارداد در ماه. مثال: معاش ۳۰۰۰ افغانی برای ۲۸ روز = ۲۸۰۰، برای ۳۰ روز = ۳۰۰۰ و برای ۳۱ روز = ۳۱۰۰ افغانی. قرارداد نیمه‌ماه فقط به تعداد روزهای شامل قرارداد محاسبه می‌شود. مبالغ قبلاً تأییدشده تغییر نمی‌کند. دوره محاسبه معاش مطابق بخش معاش، ماه میلادی است. این تغییر مربوط معاش اساتید است.
