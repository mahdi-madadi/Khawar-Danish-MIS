Forms and teacher attendance sheets
==================================

Open Forms in the sidebar for the old project's student registration, teacher
registration, guardian consent and bilingual appreciation templates. These blank
templates retain the old layout and use the configured center name. Forms also
links to attendance sheets, recorded payment receipts and results when permitted.

To prepare a teacher's attendance sheet:
1. Open Forms > Teacher attendance sheet (also available under Attendance).
2. Select the class/subject and enter YYYY-MM in the selected calendar.
3. Click Load, choose a teaching time, then click Load again. A single available
   time is selected automatically. Timetable entries must exist for that month.
4. Click Print / Download PDF. Select Save as PDF in the browser print dialog
   to download, or select a printer. Use A4 landscape.

The sheet contains student names, fathers' names, student IDs and one column per
calendar day (31/30 days, or 28/29 where applicable). Twenty students print per
sheet, with repeated class/subject/teacher/time/room headings and signature lines.
Days without lessons, closure days, holidays and dates outside a student's
enrollment are marked with a dash. All other cells and totals remain blank for
the teacher to fill by hand. The sheet does not change recorded attendance.
The existing daily attendance editor and recorded monthly register are retained.

No schema change or database migration is required. Students belong to batches;
selecting a time filters the timetable for that batch/subject, rather than
creating separate student groups within a batch. Create separate batches when
different time groups contain different students.

Validation: PHP syntax checks; all 26 existing unit checks; isolated SQLite/HTTP
checks for all four templates, catalog, Gregorian 31/30/28 and Shamsi 31/30-day
sheets, timetable time selection, enrollment overlap and 20-row pagination.
These HTTP checks do not substitute for a visual print review or MySQL tests.


به‌روزرسانی تقسیم اوقات، حاضری و شاگردان — ۷ عقرب ۱۴۰۵

تقسیم اوقات: روزهای یک مضمون با ساعت، اتاق و تاریخ یکسان در یک سطر نمایش داده می‌شود. روزهای هفته در همان سطر نوشته می‌شود؛ بررسی تداخل هر روز همچنان جداگانه صورت می‌گیرد.
حاضری: در حاضری ماهانه و ورق چاپی، زیر شماره هر روز نام روز هفته نیز درج می‌شود. تعداد روزها مطابق ماه انتخاب‌شده است.
تقدیرنامه و تصدیق‌نامه تکمیل دوره: متن تقدیر یا تکمیل دوره انگلیسی نیز همراه متن فارسی چاپ می‌شود. برای نام انگلیسی شاگرد و دوره، املای لاتین را هنگام ثبت معلومات وارد کنید؛ سیستم نام اشخاص را ترجمه نمی‌کند.
شاگرد فعال: حداقل یک ثبت‌نام فعال دارد که تاریخ امروز در محدوده ثبت‌نام و دوره فعال باشد. پس از ختم آخرین دوره یا قطع ثبت‌نام، خودکار در فهرست غیر فعال قرار می‌گیرد. ثبت‌نام مجدد در یک دوره جاری او را دوباره فعال می‌سازد. پرداخت پول به تنهایی وضعیت فعالیت را تعیین نمی‌کند. شاگرد تازه که هنوز شامل دوره نشده یا فقط دوره آینده دارد، فعلاً غیر فعال است. تعداد و فهرست هر دو گروه در صفحه اول، از «نمایش فهرست»، قابل مشاهده است. وضعیت اداری آرشیف‌شده مانع فعالیت است.
معاش ثابت استاد: مبلغ ثابت قرارداد مبنای ۳۰ روز دارد. مبلغ قابل پرداخت = مبلغ ثابت ÷ ۳۰ × روزهای تقویمی تحت پوشش قرارداد در ماه. مثال: معاش ۳۰۰۰ افغانی برای ۲۸ روز = ۲۸۰۰، برای ۳۰ روز = ۳۰۰۰ و برای ۳۱ روز = ۳۱۰۰ افغانی. قرارداد نیمه‌ماه فقط به تعداد روزهای شامل قرارداد محاسبه می‌شود. مبالغ قبلاً تأییدشده تغییر نمی‌کند. دوره محاسبه معاش مطابق بخش معاش، ماه میلادی است. این تغییر مربوط معاش اساتید است.
