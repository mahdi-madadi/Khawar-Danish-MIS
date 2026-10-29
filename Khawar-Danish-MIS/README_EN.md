# Khawar-Danish MIS

Offline-first educational-center management in plain PHP/PDO and MySQL, with Dari RTL and English interfaces, Solar Hijri/Gregorian calendars, local fonts, light/dark themes and responsive layouts.

[README دری](README.md) · [English installation](docs/INSTALLATION_EN.md) · [Testing](docs/TESTING.md)

## Features

- Student/teacher profiles, portraits and tazkira details. Volume, page and registration fields apply only to paper documents.
- Administrative employment records, salary, employment dates and linked login accounts. One account per employee; the original administrator is preserved separately. Teacher profiles do not create login accounts.
- Courses, subjects, rooms, teaching groups, timetable conflict checks, capacity and enrollment history.
- Tuition charges, installment and advance receipts, allocations, credits, refunds and cash reconciliation.
- Teacher compensation agreements and payroll, plus separate administrative payroll with employment-day proration, immutable approvals and partial payments.
- Attendance and paper registers, assessments, weighted subject results, printable documents and certificates.
- Section-specific printing, CSV summaries, ZIP backups, local CLI restore, role permissions and audit records.

![Dashboard — fictional test data](docs/images/dashboard.png)

## Runtime requirements

PHP 8.2+ (64-bit), PDO MySQL, Zip, Fileinfo and sessions; MySQL 8+ or MariaDB 10.4+ with InnoDB/utf8mb4; Apache 2.4 and a modern browser. Windows/Wamp installation is documented. Dependencies must be installed separately.

No Composer, Node, CDN or internet connection is needed during normal local use. Vazirmatn and Inter fonts, SVG icons, styles and scripts are bundled. Python/Node/Playwright are optional developer-test tools.

## Install

1. Place the project outside the web root, for example `C:\Khawar-Danish`.
2. Create an empty database and a dedicated database account.
3. Copy `config/config.example.php` to `config/config.php` and set your own connection values.
4. Ensure `storage/logs`, `backups`, `uploads` and `sessions` exist and are writable by PHP.
5. Run `php bin/console.php install`, confirm `INSTALL`, then `php bin/console.php admin` to create your administrator. There is no default login.
6. Configure Apache to expose **only `public/`**. With the documented alias, open `http://localhost/khawar-danish/`.

See [full installation and upgrade instructions](docs/INSTALLATION_EN.md). Do not run install/demo/reset commands against existing operational data unless explicitly intended.

## Repository contents and operations

The repository contains code, schema, optional fictional demo data and documentation. It does not contain real credentials, user portraits, application data, sessions or backups. `.gitignore` protects normal Git staging; it does not filter manual browser uploads.

The default document root must never expose the whole project. Backups contain private data and are not automatically encrypted or scheduled. Restore is a local CLI operation. Reset is optional and destructive; it is not part of normal installation or publishing.

Group fees are references, not automatically copied into enrollments. Monthly tuition is not automatically generated. Payroll periods are Gregorian months; approval and cash payment are separate steps.

## Verification

PHP syntax checks, 30 domain/date checks, isolated browser workflows and dedicated MySQL employee/payroll/reset/backup scenarios have been exercised locally. CI runs PHP lint, domain tests and static clean-package checks; browser/MySQL checks require their documented environments. No independent security audit, physical-printer certification or broad deployment benchmark is claimed.

```sh
php tests/unit.php
python tests/static_check.py
```

Read [docs/TESTING.md](docs/TESTING.md) before integration tests; they require disposable databases. See [SECURITY.md](SECURITY.md) for reporting guidance.

## Handbooks and licenses

Download and open [Dari user handbook](docs/USER_GUIDE_DARI.html) and [administrator handbook](docs/ADMIN_GUIDE_DARI.html) locally. GitHub stores the source; GitHub Pages is static hosting and does not run this PHP/MySQL application.

Application: [MIT](LICENSE). Fonts and third-party notices: [licenses/DEPENDENCIES.md](licenses/DEPENDENCIES.md).


Schedule, attendance, student activity and teacher salary update (29 October 2026)

Identical subject/time/room/date ranges display once with all teaching weekdays. Daily conflict validation is preserved. Monthly attendance and paper sheets show each weekday beside its day number.
Issued completion and appreciation certificates also include English recognition text. Names retain their stored spelling.
Dashboard active/inactive counts and expandable full lists are derived on every visit. Active means an administratively active student with an active enrollment in an active batch covering today; both enrollment and batch dates apply. Ended courses automatically cease qualifying; renewal in a current course restores activity. Payment alone does not establish activity. New students without a current enrollment and future-only enrollments are inactive for now.
Teacher fixed salary uses agreed amount / 30 × covered calendar days, rounded to cents. A 3000 AFN amount yields 2800 for 28 days and 3100 for 31 days. Partial contracts count only covered days. Previously approved payroll remains unchanged. Payroll periods use Gregorian months. Administrative employee payroll is unchanged.
