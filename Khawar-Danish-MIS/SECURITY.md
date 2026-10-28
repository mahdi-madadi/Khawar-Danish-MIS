# Security and private data

Do not put real database credentials, access tokens, identity documents, student/employee information, private photos, sessions, logs or backups in this repository or public issues.

The release folder is a code-only distribution. config/config.php and config/test.php are local files. The repository contains config/config.example.php with a placeholder only. Runtime files under storage/ are ignored by Git; manual browser uploads do not enforce .gitignore rules.

If a real secret was already published, revoke/rotate it and contact the repository owner about history cleanup; deleting it in a later commit is not enough.

For a suspected vulnerability, use private reporting if the repository owner has enabled it, or a private channel published by the owner. Do not post student records or credentials in an issue. Include the affected code path and a reproduction with fictional data.

Only public/ should be web-accessible. Backups contain confidential information and are not encrypted by the application. Local deployment checks are not an independent penetration test. The bundled MIT license and warranty terms remain applicable.
