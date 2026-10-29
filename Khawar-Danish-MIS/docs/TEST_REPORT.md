# Test report — 25 October 2026

## Summary

**No claim of successful PHP/MySQL execution is made.** This package is an implementation baseline requiring local acceptance, not completed production software.

| Check | Result | Evidence / limit |
|---|---|---|
| Static packaging checks | 17 / 17 passed | `python3 tests/static_check.py`; full output below |
| JavaScript syntax | Passed | `node --check public/assets/app.js`, exit code 0 |
| Static class-method call cross-reference | Passed | Calls to Domain, Academic, Finance, Admin and Backup matched defined method names; not PHP type checking |
| PHP delimiter/comment/string lexical scan | Passed | Included in static checker; **not PHP syntax lint** |
| PHP `-l` lint | Not run | No PHP executable available |
| PHP unit suite | Written, not run | `tests/unit.php` covers money, dates, overlaps, permissions and CSV protection |
| MySQL financial integration suite | Written, not run | `tests/integration.php` covers charges, discount, payment idempotency, payroll, refund and expense reversal in a dedicated test DB |
| Windows WAMP/XAMPP install | Not run | Build host is not Windows; no PHP/MySQL runtime |
| Browser UI and responsive behavior | Not run | Attempted static style preview with Playwright; Chromium executable missing |
| RTL/A4 print/PDF visual QA | Not run | No browser executable; no rendered layout verification |
| Live authorization / CSRF / session tests | Not run | Require PHP/database/server/browser |
| Timetable concurrency, teacher scoping | Not run | Source logic and static checks only |
| Backup + restore round-trip | Not run | Requires PHP Zip and live DB |
| Full internet-disconnected scenario | Not run | Assets checked for remote dependency references; app itself not executed |
| Performance on 4 GB Windows PC | Not run | No benchmark hardware |

An attempted environment package update failed due to host process/user restrictions. No runtime was installed. No external service or old project database was modified.

## Actual static checker output

```text
PASS PHP files start with opening tag
PASS PHP lexical quotes/comments/delimiters balanced (not syntax lint)
PASS No remote asset dependencies in public HTML/CSS/JS or view source
PASS Local Persian-capable font bundled
PASS Font license bundled
PASS No production config or credentials packaged
PASS Private directories have Apache deny rules
PASS Single public front controller
PASS All literal translation lookups exist
PASS Every literal POST form has an action handler
PASS Money schema uses DECIMAL, not FLOAT/DOUBLE
PASS Idempotency constraints for cash writes
PASS POST CSRF validation is present
PASS Write serialization lock present
PASS CSV UTF-8 BOM and formula neutralization present
PASS PHP unit and DB integration tests bundled
PASS Schema, demo, CLI installer exist

17/17 static checks passed. PHP runtime NOT tested.
```

These checks establish packaging/source properties only. They do not prove correct SQL, secure authorization, monetary correctness, date-conversion accuracy or successful rendering. Run the bundled suites and `ACCEPTANCE.md` in the target environment and preserve actual results before approval.


به‌روزرسانی تقسیم اوقات، حاضری و شاگردان — ۷ عقرب ۱۴۰۵

تقسیم اوقات: روزهای یک مضمون با ساعت، اتاق و تاریخ یکسان در یک سطر نمایش داده می‌شود. روزهای هفته در همان سطر نوشته می‌شود؛ بررسی تداخل هر روز همچنان جداگانه صورت می‌گیرد.
حاضری: در حاضری ماهانه و ورق چاپی، زیر شماره هر روز نام روز هفته نیز درج می‌شود. تعداد روزها مطابق ماه انتخاب‌شده است.
تقدیرنامه و تصدیق‌نامه تکمیل دوره: متن تقدیر یا تکمیل دوره انگلیسی نیز همراه متن فارسی چاپ می‌شود. برای نام انگلیسی شاگرد و دوره، املای لاتین را هنگام ثبت معلومات وارد کنید؛ سیستم نام اشخاص را ترجمه نمی‌کند.
شاگرد فعال: حداقل یک ثبت‌نام فعال دارد که تاریخ امروز در محدوده ثبت‌نام و دوره فعال باشد. پس از ختم آخرین دوره یا قطع ثبت‌نام، خودکار در فهرست غیر فعال قرار می‌گیرد. ثبت‌نام مجدد در یک دوره جاری او را دوباره فعال می‌سازد. پرداخت پول به تنهایی وضعیت فعالیت را تعیین نمی‌کند. شاگرد تازه که هنوز شامل دوره نشده یا فقط دوره آینده دارد، فعلاً غیر فعال است. تعداد و فهرست هر دو گروه در صفحه اول، از «نمایش فهرست»، قابل مشاهده است. وضعیت اداری آرشیف‌شده مانع فعالیت است.
معاش ثابت استاد: مبلغ ثابت قرارداد مبنای ۳۰ روز دارد. مبلغ قابل پرداخت = مبلغ ثابت ÷ ۳۰ × روزهای تقویمی تحت پوشش قرارداد در ماه. مثال: معاش ۳۰۰۰ افغانی برای ۲۸ روز = ۲۸۰۰، برای ۳۰ روز = ۳۰۰۰ و برای ۳۱ روز = ۳۱۰۰ افغانی. قرارداد نیمه‌ماه فقط به تعداد روزهای شامل قرارداد محاسبه می‌شود. مبالغ قبلاً تأییدشده تغییر نمی‌کند. دوره محاسبه معاش مطابق بخش معاش، ماه میلادی است. این تغییر مربوط معاش اساتید است.
