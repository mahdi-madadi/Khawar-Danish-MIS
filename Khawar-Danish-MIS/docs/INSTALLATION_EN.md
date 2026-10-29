# Installation — Khawar-Danish MIS

## Fresh Windows/Wamp installation

1. Install PHP 8.2+ (64-bit), Apache 2.4 and MySQL 8+/MariaDB 10.4+. Enable PDO MySQL, Zip, Fileinfo and sessions.
2. Put the project outside the web root, for example C:\Khawar-Danish. Its app/, public/ and README.md belong directly inside that directory.
3. Ensure storage/logs, storage/backups, storage/uploads and storage/sessions exist and PHP can write to them.
4. Create an empty utf8mb4 database and a dedicated database account. Replace the sample password before use:

```sql
CREATE DATABASE khawar_danish CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kd_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_YOUR_OWN_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON khawar_danish.* TO 'kd_app'@'localhost';
```

5. Copy config/config.example.php to config/config.php and enter your connection values. Use host=localhost in the DSN for the localhost account shown above. Never commit the real configuration.
6. Open PowerShell in the project directory. Adjust the executable path for your PHP installation:

```powershell
Set-Location 'C:\Khawar-Danish'
$phpExe = 'C:\wamp64\bin\php\php8.3.28\php.exe'
& $phpExe -d 'session.save_path=storage/sessions' bin/console.php install
& $phpExe -d 'session.save_path=storage/sessions' bin/console.php admin
```

Confirm INSTALL only for an empty database. Alternatively import database/001_schema.sql into the empty database, then run admin; do not also run install. The console creates your chosen administrator; no default credentials are shipped.
7. Configure Apache to expose only public/:

```apache
Alias /khawar-danish "C:/Khawar-Danish/public"
<Directory "C:/Khawar-Danish/public">
    Options -Indexes
    AllowOverride All
    DirectoryIndex index.php
    Require local
</Directory>
```

Restart Apache and open http://localhost/khawar-danish/. This example is local-only. LAN/public hosting requires separately reviewed access and HTTPS configuration.

## Existing installations

Back up the database, portraits and installed files first; stop other users. Preserve config/config.php and storage/. Update app/, public/, bin/, database/, docs/ and licenses/. Keep Apache stopped and MySQL available during schema changes.

For earlier schemas of this same project, run and test these additive upgrade scripts in order on a separate copy before operational use:

```powershell
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-people.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-teaching-groups.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-employees.php
& $phpExe -d 'session.save_path=storage/sessions' bin/upgrade-tazkira-fields.php
```

MySQL DDL is not fully transactional. These scripts are not a migration for unrelated databases. An administrator must link legacy non-administrator login accounts to administrative employment records; teacher profiles are not the login source anymore. Restart Apache and verify login, records, photos, finance and backup.

Do not run install, demo or reset for ordinary updates. Reset is deliberately destructive and documented separately. Backups are not automatically encrypted or scheduled. CLI ZIP restore has a 128 MiB uncompressed limit; preserve application/config files separately as well as database/photo backups.


Schedule, attendance, student activity and teacher salary update (29 October 2026)

Identical subject/time/room/date ranges display once with all teaching weekdays. Daily conflict validation is preserved. Monthly attendance and paper sheets show each weekday beside its day number.
Issued completion and appreciation certificates also include English recognition text. Names retain their stored spelling.
Dashboard active/inactive counts and expandable full lists are derived on every visit. Active means an administratively active student with an active enrollment in an active batch covering today; both enrollment and batch dates apply. Ended courses automatically cease qualifying; renewal in a current course restores activity. Payment alone does not establish activity. New students without a current enrollment and future-only enrollments are inactive for now.
Teacher fixed salary uses agreed amount / 30 × covered calendar days, rounded to cents. A 3000 AFN amount yields 2800 for 28 days and 3100 for 31 days. Partial contracts count only covered days. Previously approved payroll remains unchanged. Payroll periods use Gregorian months. Administrative employee payroll is unchanged.
