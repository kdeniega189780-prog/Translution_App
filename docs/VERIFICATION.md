# Local verification — October 7, 2026

## Secretary/mechanic split and public landing page - October 8, 2026

- PHPUnit: **30 tests / 262 assertions**, passed using isolated SQLite. Covers distinct role interfaces, permission boundaries, mechanic job ownership, scheduling/assignment, customer consent, account creation, and preservation of credentials and assignments during migration.
- Headless Edge browser checks passed for all five roles, initial password change, full paid repair flow, PDF download, CSRF rejection, and desktop/mobile layout.
- Landing page checked at 1440px, 390px, and 320px; role dashboards and repair action visibility checked at desktop and phone widths. Screenshots: `test-results/landing-1440.png`, `landing-390.png`, `landing-320.png`, `secretary-desktop.png`, `secretary-mobile.png`, `mechanic-desktop.png`, and `mechanic-mobile.png`.
- Blade compilation passed. The additive role migration was applied to local MariaDB; existing staff users became mechanics without changing credentials or assignments. No reseeding was performed on the workshop database.

## Admin dashboard sales graph — October 8, 2026

- Full PHPUnit suite: **24 tests, 184 assertions**, passed. New checks cover hourly/day buckets, week/month boundaries, exact cents, leap-year month length, zero-filled periods, unpaid/deleted/reversed payment exclusion, filter validation, and Admin-only data access.
- Headless Edge chart checks passed at desktop 1440px and phone widths 390px/320px: Daily/Weekly/Monthly switching, payment totals, tooltip, keyboard navigation, matching report links, report date selection, and empty periods.
- Chart screenshots: `test-results/sales-daily-desktop.png`, `sales-weekly-desktop.png`, `sales-monthly-desktop.png`, `sales-monthly-390.png`, `sales-monthly-320.png`.
- These checks use disposable SQLite fixtures; the application's XAMPP database remains configured as before.

## Original application verification

- Runtime: XAMPP PHP **8.2.12**, MariaDB **10.4.32**, Laravel **12.69.3**.
- Existing source and database exported before replacing the application and schema.
- New migrations and full workflow seed executed successfully on the local MariaDB database.
- PHPUnit: **20 tests, 138 assertions**, all passed on isolated in-memory SQLite.
- Browser: headless Microsoft Edge at **1440px desktop** and **390px phone** widths. All four roles signed in, changed their initial passwords, and rendered their own dashboard.
- Browser workflow: Pending → Scheduled → Processing → diagnosis → approval → parts deduction → Completed → generated invoice → Cash payment → Paid / Released, all through actual forms.
- Browser checks: PDF downloaded; missing CSRF token rejected with HTTP 419; no uncaught page JavaScript errors; no document overflow on the tested desktop/module/mobile pages; mobile menu opens.
- Laravel route and Blade view compilation passed.
- Composer PHP platform checks passed. Composer audit and npm audit reported no known advisories in installed dependencies.

Tests run against disposable databases. The final MariaDB demo accounts retain their generated passwords and mandatory first-login password change.

Evidence: `test-results/dashboard-desktop.png`, `dashboard-mobile.png`, `login-desktop.png`, `invoice-desktop.png`, role-specific phone screenshots, and `report.pdf`. These generated artifacts are ignored by Git.

Not exercised here: building the Docker image (Docker is unavailable on this computer), a live Render deployment, external SMTP delivery, simultaneous multi-process load testing, and an independent accessibility or penetration audit. Row-locking code paths were executed during MariaDB seeding; automated race-related assertions verify stale/duplicate submissions and transaction behavior, not a multi-host load test.
