# Contributing

1. Work on a branch and use fictional data in a separate installation.
2. Keep real configuration, uploads, backups, logs and sessions out of commits.
3. Preserve Dari RTL, English LTR, local assets, light/dark themes, keyboard access and print-specific output.
4. Run PHP lint, tests/unit.php and tests/static_check.py on a clean source tree. Run the documented browser/database checks for affected workflows.
5. Financial changes must preserve cent-based arithmetic, database transactions, the write serialization lock and payment idempotency. Test negative cases such as duplicate submissions and overpayment.
6. Document schema changes and migration/backup implications. Never test reset/restore against real records.

Describe the user-visible behavior, test evidence and remaining limitations in your pull request. See docs/TESTING.md for test environments and SECURITY.md for private reporting.
