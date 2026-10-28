# آپلود پروژه به GitHub — برای صاحب حساب موجود

نام پیشنهادی مخزن: **Khawar-Danish-MIS**

توضیح پیشنهادی:

> Offline-first Dari/English educational-center MIS built with PHP and MySQL.

فقط از پوشهٔ پاک **Khawar-Danish-MIS** در بستهٔ GitHub-Ready استفاده کنید. پوشهٔ نصب C:\Khawar-Danish یا پوشهٔ بزرگِ محیط کار که بکاپ و تست دارد، برای آپلود نیست.

## روش ساده با مرورگر

۱. وارد حساب خود شوید و https://github.com/new را باز کنید.
۲. نام مخزن را Khawar-Danish-MIS بگذارید و توضیح بالا را وارد کنید.
۳. برای آغاز **Private** را انتخاب کنید؛ اگر تصمیم دارید کد برای همه قابل مشاهده باشد، Public را انتخاب کنید. نسخهٔ آماده، دیتای واقعی ندارد.
۴. گزینه‌های اضافه‌کردن README، .gitignore و License را خاموش/خالی بگذارید؛ این فایل‌ها در بسته موجودند. Create repository را بزنید.
۵. در مخزن خالی روی **uploading an existing file** بزنید. در مخزن دارای فایل، **Add file → Upload files** را بگیرید.
۶. ZIP آماده را اول در کمپیوتر Extract کنید. پوشهٔ Khawar-Danish-MIS را باز و **محتویات داخل آن** را به صفحه بکشید؛ نه خود ZIP و نه پوشهٔ بیرونی. README.md باید در ریشهٔ مخزن باشد.
۷. نام Commit را مثلاً **Initial source upload** بگذارید و Commit changes / Propose changes را طبق صفحه تأیید کنید.
۸. صفحهٔ اصلی را باز کنید؛ README، پوشه‌های app و public و فایل config/config.example.php باید دیده شوند. config/config.php، بکاپ ZIP، عکس اشخاص و فایل دیتابیس نباید وجود داشته باشند.
۹. بخش **Actions** را باز کنید و نتیجهٔ PHP checks را ببینید. CI پس از آپلود اجرا می‌شود؛ آماده‌بودن فایل workflow به معنی اجرای قبلی روی GitHub نیست.

GitHub در آپلود مرورگری حداکثر ۱۰۰ فایل در یک نوبت و ۲۵ مگابایت برای هر فایل می‌پذیرد. اگر فایل‌ها از ۱۰۰ بیشتر شدند، در دو نوبت آپلود کنید: نخست READMEها و app/public؛ سپس بقیهٔ پوشه‌ها و فایل‌های نقطه‌دار. ساختار پوشه‌ها را حفظ کنید. [راهنمای رسمی آپلود](https://docs.github.com/en/repositories/working-with-files/managing-files/adding-a-file-to-a-repository)

در Windows نام‌هایی مانند .gitignore و .github باید عیناً حفظ شوند. فایل .gitignore فقط در کار با Git مؤثر است؛ مرورگر فایل‌های اشتباه انتخاب‌شده را خودکار فیلتر نمی‌کند.

## روش جایگزین با Git نصب‌شده

اگر خط فرمان را ترجیح می‌دهید، در PowerShell وارد همان پوشهٔ پاک شوید. YOUR_NAME، YOUR_EMAIL و YOUR_USERNAME را با معلومات خود جایگزین کنید؛ ایمیل می‌تواند ایمیل noreply حساب GitHub باشد. رمز یا توکن را داخل فرمان remote نگذارید.

```powershell
Set-Location 'C:\path\to\Khawar-Danish-MIS'
git init -b main
git config user.name 'YOUR_NAME'
git config user.email 'YOUR_EMAIL'
git add .
git diff --cached --name-only
git commit -m 'Initial source upload'
git remote add origin https://github.com/YOUR_USERNAME/Khawar-Danish-MIS.git
git push -u origin main
```

این config فقط برای همین مخزن است و تنظیم عمومی Git را تغییر نمی‌دهد. مخزن GitHub باید از قبل و بدون README خودکار ساخته شده باشد. هنگام درخواست ورود از روش احراز هویت Git/GitHub استفاده کنید؛ رمز واقعی را داخل کد، README یا URL ننویسید. اگر یک‌بار init و remote ساخته‌اید، برای آپدیت بعدی فقط بررسی تغییرات، add، commit و push لازم است.

## آیا با این کار سیستم آنلاین اجرا می‌شود؟

خیر؛ مخزن GitHub محل نگهداری و نسخه‌بندی کد است. GitHub Pages سرویس استاتیک HTML/CSS/JavaScript است و این برنامهٔ PHP/MySQL را اجرا نمی‌کند. برای استفادهٔ آنلاین باید یک سرور مناسب PHP/MySQL با تنظیم جداگانه داشته باشید. نصب محلی فعلی با آپلود کد تغییر نمی‌کند. [توضیح رسمی GitHub Pages](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages)

## فایل‌های همراه

- README.md: معرفی اصلی دری با پیش‌نمایش و مراحل شروع.
- README_EN.md: معرفی انگلیسی.
- docs/INSTALLATION_FA.md و INSTALLATION_EN.md: نصب و به‌روزرسانی.
- docs/USER_GUIDE_DARI.html و ADMIN_GUIDE_DARI.html: راهنماهای آفلاین.
- .gitignore و .gitattributes: فایل‌های محلی و قالب متن.
- .github/workflows/checks.yml: بررسی‌های اولیهٔ CI.
- LICENSE و licenses/: مجوز MIT و مجوز فونت‌ها.
- SHA256SUMS.txt: هش فایل‌های همین نسخهٔ پاک.

اگر می‌خواهید افراد خاصی به مخزن Private دسترسی داشته باشند، پس از ساخت مخزن دسترسی‌شان را از تنظیمات مخزن مدیریت کنید. برای آموزش کار با سیستم، فایل HTML راهنما را جدا دانلود و در مرورگر باز کنند.

مراحل ساخت مخزن بر اساس [راهنمای رسمی GitHub](https://docs.github.com/en/repositories/creating-and-managing-repositories/quickstart-for-repositories) نوشته شده است.
