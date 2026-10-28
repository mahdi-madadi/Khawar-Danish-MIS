# Dependencies and licenses

- Application PHP, JavaScript, CSS, SQL and documentation: MIT; see LICENSE.
- Vazirmatn (Dari/Persian) and Inter (Latin): bundled variable TTF and locally converted WOFF2; SIL Open Font License. See Vazirmatn-OFL.txt, Inter-OFL.txt and FONT_SOURCES.txt.
- DejaVu Sans (legacy local fallback): see DejaVu.txt.
- SVG interface icons are local project assets; there is no remote icon library.
- No Composer, Node package, remote font or CDN is required at application runtime.
- PHP, Apache, MySQL/MariaDB, WAMP/XAMPP and browsers are separate prerequisites and are not redistributed.
- Python, Node and Playwright are optional development/test tools, not installation requirements.
- GitHub Actions uses actions/checkout and shivammathur/setup-php only for repository checks. The hosted workflow requires network access; the running local application does not.
