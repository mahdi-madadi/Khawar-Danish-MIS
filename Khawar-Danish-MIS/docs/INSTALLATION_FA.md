# نصب دری — خاور دانش

## نصب تازه روی Windows / Wampserver

این مراحل برای دیتابیس خالی است. برای نصب موجود، بخش به‌روزرسانی را ببینید.

۱. PHP 8.2+، Apache و MySQL/MariaDB را نصب و در Wamp فعال کنید. PDO MySQL، Zip و Fileinfo باید فعال باشند.
۲. محتویات پروژه را به C:\Khawar-Danish منتقل کنید؛ app، public و README.md باید مستقیم داخل همین پوشه باشند.
۳. پوشه‌های storage/logs، storage/backups، storage/uploads و storage/sessions را بررسی کنید؛ PHP باید اجازهٔ نوشتن داشته باشد.
۴. در phpMyAdmin با حساب مجاز دیتابیس بسازید. مقدار نمونهٔ رمز را با رمز واقعی و قوی خودتان جایگزین کنید:

```sql
CREATE DATABASE khawar_danish CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kd_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_YOUR_OWN_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON khawar_danish.* TO 'kd_app'@'localhost';
```

۵. config/config.example.php را به config/config.php کاپی کنید و DSN، نام حساب و رمز را تنظیم کنید. برای حساب localhost مثال بالا، DSN را چنین بگذارید:

```php
'dsn' => 'mysql:host=localhost;port=3306;dbname=khawar_danish;charset=utf8mb4',
```

این فایل واقعی باید روی کمپیوتر نصب بماند و به GitHub آپلود نشود.

۶. PowerShell را در پوشهٔ پروژه باز کنید. مسیر PHP را مطابق نسخهٔ نصب‌شدهٔ خود عوض کنید:

```powershell
Set-Location 'C:\Khawar-Danish'
$phpExe = 'C:\wamp64\bin\php\php8.3.28\php.exe'
& $phpExe -d 'session.save_path=storage/sessions' bin/console.php install
& $phpExe -d 'session.save_path=storage/sessions' bin/console.php admin
```

در دستور اول INSTALL را تأیید کنید. در دستور دوم نام، نام کاربری و رمز تازهٔ مدیر را تعیین کنید. رمز در کنسول دیده می‌شود؛ خصوصی انجام دهید. به‌جای دستور install می‌توانید 001_schema.sql را در دیتابیس خالی Import کنید؛ هردو روش را پشت سر هم اجرا نکنید. هیچ رمز یا حساب پیش‌فرض وجود ندارد.

۷. در تنظیم Apache یک Alias بسازید که فقط public را منتشر کند:

```apache
Alias /khawar-danish "C:/Khawar-Danish/public"
<Directory "C:/Khawar-Danish/public">
    Options -Indexes
    AllowOverride All
    DirectoryIndex index.php
    Require local
</Directory>
```

Apache را راه‌اندازی مجدد و http://localhost/khawar-danish/ را باز کنید. برای شبکه یا سرور آنلاین، تنظیم دسترسی و HTTPS باید جدا توسط مسئول تخنیکی بررسی شود؛ این مثال دسترسی محلی دارد.

۸. ترتیب تنظیمات، استخدام کارمند، ساخت کاربر و شروع کار را در USER_GUIDE_DARI.html و ADMIN_GUIDE_DARI.html بخوانید.

## به‌روزرسانی نصب قدیمی

- ابتدا بکاپ قابل دانلود و یک کاپی از فایل‌های نصب بگیرید و کار کاربران را متوقف کنید.
- config/config.php و storage را با نسخهٔ مخزن جایگزین نکنید؛ آن‌ها معلومات همین نصب‌اند.
- app، public، bin، database، docs و licenses را از نسخهٔ تازه منتقل کنید. تا تکمیل ارتقای ساختار، Apache متوقف و MySQL روشن بماند.
- برای نسخه‌های قبلی همین پروژه، مسئول تخنیکی اسکریپت‌های زیر را به ترتیب و پس از آزمایش روی کاپی جدا اجرا کند:

```powershell
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-people.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-teaching-groups.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-employees.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-tazkira-fields.php
```

این‌ها برای ساختار همین پروژه‌اند، نه هر سیستم دیگری با نام مشابه. تغییرات DDL در MySQL الزاماً با rollback برنمی‌گردند؛ بکاپ را حفظ کنید. حساب‌های قدیمیِ غیرمدیر که به کارمند اداری وصل نیستند، باید مدیر پس از ثبت استخدام بازبینی و وصل کند. ورود از پروفایل استاد دیگر روش ساخت حساب نیست.

پس از تکمیل، Apache را فعال، با Ctrl+F5 صفحه را تازه و ورود، عکس‌ها، ثبت‌ها، فیس و بکاپ را بررسی کنید. برای به‌روزرسانی عادی install، demo یا reset-data.php را اجرا نکنید.

## بکاپ و ریست

بکاپ از صفحهٔ نسخهٔ پشتیبان ساخته و دانلود می‌شود؛ یک کاپی جدا نگه دارید. بکاپ‌ها خودکار رمزگذاری یا زمان‌بندی نمی‌شوند. بازیابی محلی ZIP به ۱۲۸ مگابایت حجم غیرفشرده محدود است. بازیابی و ریست باهم فرق دارند.

ریست اختیاری تمام دیتای کاری را با حفظ قدیمی‌ترین مدیر فعال پاک می‌کند. دستور آن فقط در RESET_DATABASE_DARI.md است و برای نصب یا انتشار در GitHub لازم نیست.
