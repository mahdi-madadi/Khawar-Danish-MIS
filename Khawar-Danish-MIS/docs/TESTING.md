# Verification and test environments

## Fast checks (no database)

```sh
php tests/unit.php
python tests/static_check.py
```

The domain suite has 30 checks, including date conversions, Solar Hijri leap years, money and generated IDs. Static checks inspect packaging and source patterns; they are not a runtime/security audit. Run them on the clean release directory. The operational installation intentionally has a real config.php and therefore fails the publication-config check.

GitHub Actions checks PHP 8.3 syntax, domain tests and the clean package. The hosted CI workflow has not run until the repository is uploaded and Actions completes.

## Disposable MySQL integration

Create a NEW empty database whose name ends in _test, using separate test credentials. Copy config/config.example.php to config/test.php and point its DSN only at that database. Do not reuse the operational database or commit test.php.

```sh
php tests/employees_mysql.php
```

This test refuses a nonempty database or a DSN without the _test suffix. It imports the schema and exercises administrative payroll proration, approvals, partial/idempotent payments, overpayment rejection, employee-account binding, reset preservation and backup/restore compatibility. It removes its test tables when finished. It writes and then removes a test ZIP under storage/backups; that directory must be writable.

The older general finance scenario is also available:

```sh
php tests/integration.php
```

Use a separate empty _test database for it. It leaves its schema/demo tables behind; read its output before reusing that database.

## Isolated browser tests

Prerequisites: Python with SQLite, PHP with PDO SQLite, Node.js, Playwright and Chrome. They are development tools only. The fixture generator copies app/public and transforms a disposable copy for SQLite; it never points at the real database. SQLite browser checks do not replace MySQL checks.

Example in PowerShell from the release root; change executable and tool paths:

```powershell
$phpExe = 'C:\wamp64\bin\php\php8.3.28\php.exe'
python tests/prepare_ui_fixture.py --php $phpExe --out C:\Temp\kd-ui-fixture
$env:KD_FIXTURE = 'C:\Temp\kd-ui-fixture'
$env:KD_PHP = $phpExe
$env:KD_NODE_MODULES = 'C:\path\to\node_modules'
node tests/ui.cjs
```

The test runner expects Playwright under KD_NODE_MODULES. Chrome defaults to its standard Windows installation path. The full UI runner also supports KD_BROWSER and KD_UI_PORT. Some focused runners use the documented Wamp/Chrome paths directly; adapt them for another development environment.

Use a FRESH fixture directory for each focused test (their cases insert fictional records):

- tests/employees.cjs — hiring, identity, employee accounts, payroll, cash reports, archived-account revocation.
- tests/tazkira_fields.cjs — conditional paper fields, electronic data normalization and absence of issuing district.
- tests/visual_refresh.cjs — local fonts, light/dark, RTL/LTR, mobile, reduced motion and isolated print.

Set KD_FIXTURE to that new fixture before running the focused script. Screenshots/PDFs and fixture databases are developer artifacts; do not publish them as operational data.

## Evidence and limits

Before packaging, PHP lint, all 30 domain checks and isolated UI workflows were run locally. Dedicated real MySQL tests exercised employee payroll, cash effects, reset and ZIP backup/restore. Print PDFs were rendered and inspected. This is not independent penetration testing, physical-printer certification, a large-load benchmark or proof that every server environment is configured correctly. Fonts/licenses and source-only packaging are checked separately.


Schedule, attendance, student activity and teacher salary update (29 October 2026)

Identical subject/time/room/date ranges display once with all teaching weekdays. Daily conflict validation is preserved. Monthly attendance and paper sheets show each weekday beside its day number.
Issued completion and appreciation certificates also include English recognition text. Names retain their stored spelling.
Dashboard active/inactive counts and expandable full lists are derived on every visit. Active means an administratively active student with an active enrollment in an active batch covering today; both enrollment and batch dates apply. Ended courses automatically cease qualifying; renewal in a current course restores activity. Payment alone does not establish activity. New students without a current enrollment and future-only enrollments are inactive for now.
Teacher fixed salary uses agreed amount / 30 × covered calendar days, rounded to cents. A 3000 AFN amount yields 2800 for 28 days and 3100 for 31 days. Partial contracts count only covered days. Previously approved payroll remains unchanged. Payroll periods use Gregorian months. Administrative employee payroll is unchanged.
