# Default role matrix

| Capability | Administrator | Registrar | Accountant | Teacher/operator | Read-only |
|---|---|---|---|---|---|
| View student records | All | Yes | Yes | No | Yes |
| Create/update students and enrollments | All | Yes | No | No | No |
| View academic structure | All | Yes | Yes | Assigned classes only in usable views | Yes |
| Create/update academic master data | All | Yes | No | No | No |
| View attendance | All | Yes | No | Assigned class subjects | Yes |
| Record attendance | All | No | No | Assigned class subjects | No |
| View exams/results | All | Yes | No | Assigned class subjects | Yes |
| Create assessments / enter marks | All | No | No | Assigned class subjects | No |
| Certificates | All | View | No | No | View |
| View/create finance | All | No | Yes | No | No |
| Refund/discount | All | No | Yes | No | No |
| View/create/approve/pay payroll | All | No | Yes | No | No |
| Users/roles/settings/backups/audit | All | No | No | No | No |
| App print control | Yes | Yes | Yes | Yes | Yes |
| CSV export | Yes | Yes, but no financial route | Yes | No | No |

- Role 1 contains `*` and cannot be edited. The last active role-1 user cannot be deactivated or demoted through the UI.
- No general “delete” permission exists because deletion is not offered; students/classes are archived and finance corrections are separate transactions where implemented.
- Teacher role 4 requires a linked teacher profile. Other custom roles can be scoped by a linked teacher profile; a teacher-like custom role without that link is not automatically scoped. Test custom roles before use.
- Permission keys are action-specific: view/create/update, finance.discount, finance.refund, and management permissions. Printing/export controls do not grant access to the underlying data by themselves.
- Front-controller POST dispatch checks permissions, not merely the sidebar. Academic class operations also check the linked teacher.
- Role editor replaces the entire permission list. Use the displayed existing record as a reference, enter its ID, and check every desired permission. This editor needs usability improvement.
- This matrix documents source intent. Live authorization tests have not been executed.


به‌روزرسانی تقسیم اوقات، حاضری و شاگردان — ۷ عقرب ۱۴۰۵

تقسیم اوقات: روزهای یک مضمون با ساعت، اتاق و تاریخ یکسان در یک سطر نمایش داده می‌شود. روزهای هفته در همان سطر نوشته می‌شود؛ بررسی تداخل هر روز همچنان جداگانه صورت می‌گیرد.
حاضری: در حاضری ماهانه و ورق چاپی، زیر شماره هر روز نام روز هفته نیز درج می‌شود. تعداد روزها مطابق ماه انتخاب‌شده است.
تقدیرنامه و تصدیق‌نامه تکمیل دوره: متن تقدیر یا تکمیل دوره انگلیسی نیز همراه متن فارسی چاپ می‌شود. برای نام انگلیسی شاگرد و دوره، املای لاتین را هنگام ثبت معلومات وارد کنید؛ سیستم نام اشخاص را ترجمه نمی‌کند.
شاگرد فعال: حداقل یک ثبت‌نام فعال دارد که تاریخ امروز در محدوده ثبت‌نام و دوره فعال باشد. پس از ختم آخرین دوره یا قطع ثبت‌نام، خودکار در فهرست غیر فعال قرار می‌گیرد. ثبت‌نام مجدد در یک دوره جاری او را دوباره فعال می‌سازد. پرداخت پول به تنهایی وضعیت فعالیت را تعیین نمی‌کند. شاگرد تازه که هنوز شامل دوره نشده یا فقط دوره آینده دارد، فعلاً غیر فعال است. تعداد و فهرست هر دو گروه در صفحه اول، از «نمایش فهرست»، قابل مشاهده است. وضعیت اداری آرشیف‌شده مانع فعالیت است.
معاش ثابت استاد: مبلغ ثابت قرارداد مبنای ۳۰ روز دارد. مبلغ قابل پرداخت = مبلغ ثابت ÷ ۳۰ × روزهای تقویمی تحت پوشش قرارداد در ماه. مثال: معاش ۳۰۰۰ افغانی برای ۲۸ روز = ۲۸۰۰، برای ۳۰ روز = ۳۰۰۰ و برای ۳۱ روز = ۳۱۰۰ افغانی. قرارداد نیمه‌ماه فقط به تعداد روزهای شامل قرارداد محاسبه می‌شود. مبالغ قبلاً تأییدشده تغییر نمی‌کند. دوره محاسبه معاش مطابق بخش معاش، ماه میلادی است. این تغییر مربوط معاش اساتید است.
