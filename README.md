# Transolutions Vehicle Maintenance Management System

A complete Laravel 12 workshop application for **Transolutions Automatic Transmission Overhaul, Davao City**. Customers request check-ups; secretaries coordinate bookings and payments; mechanics repair assigned vehicles; purchasers receive stock; the owner controls prices, accounts, and reports.

The previous UI and database were replaced. A pre-rebuild source and SQL backup is stored outside the project at `C:\Users\Bochoy\Downloads\Translution_App_backup_20261007_221018`. Keep this backup private: it contains the old `.env` and customer records.

See [Secretary and mechanic workspaces](docs/ROLES.md) for permissions, the repair handoff, and migrating existing staff accounts. The public landing page is at `/`; sign-in is at `/login`.

## Assumptions

- Currency is PHP and local time is Asia/Manila. Maximum four confirmed bookings per day, with no identical start times.
- The booking becomes the repair job; staff do not re-enter vehicle/customer details. One primary service per job, with any number of parts used.
- Admin changes default service prices and can revise the job's service estimate during diagnosis before parts are used. Parts are billed at their recorded unit cost; no invented markup, tax, or discounts.
- Admins and purchasers can use **Inventory → Stock Out** beside a part to record non-repair removals (for example, damaged items or supplier returns). Enter the quantity and reason; the stock ledger records who removed it and the remaining balance. Stock cannot become negative, and stale forms require review. Record vehicle repair usage on the repair job so it is included in the invoice.
- The configured Singapore supplier name is a placeholder. Orders are marked after arranging purchases with the supplier; receiving confirms the whole delivery.
- Customer decline pauses work. Revising the diagnosis requests fresh approval. Secretaries may record a walk-in customer's decision only with a contact note.
- Payments are recorded in full after receipt outside the app. No external payment gateway is implied.

## 1. Plan and tables

`Pending → Scheduled → Processing → Completed → Paid / Released`

Modules: authentication and profile; role dashboards; bookings/daily schedule; customers/vehicles/history; services/prices; repairs/consent; inventory/stock ledger; weekly orders/receiving; invoices/payment/printing; reports/CSV/PDF; team accounts and activity history.

Tables: `users`, `customers`, `vehicles`, `services`, `parts`, `schedule_days`, `bookings`, `job_parts`, `parts_orders`, `order_items`, `stock_movements`, `invoices`, `invoice_items`, `activity_logs`, `notifications`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, and Laravel's `migrations` table.

```text
User ── Customer ── Vehicles ── Bookings ── Invoice ── Invoice items
                         Service ──┘ ├── Assigned Mechanic (User)
                                     └── Job parts ── Parts
User ── Parts orders ── Order items ──────────────────┘
Parts ── Stock movements ── Booking or Parts order ── User
User ── Notifications / Activity logs
```

See [Architecture and text ERD](docs/ARCHITECTURE.md) for all relationships, invariants, and a fault-finding map.

## 2. XAMPP setup (Windows 11 / VS Code)

Use XAMPP with **PHP 8.2.12** and **MariaDB 10.4.32**, plus Composer 2. Laravel is pinned to the 12.x line and Composer resolves dependencies for PHP 8.2.12. Node is not required to run the application.

1. Start **MySQL** in XAMPP. Apache is only required for phpMyAdmin or an Apache virtual host; `artisan serve` supplies the local web server.
2. Open this project folder in VS Code. In PowerShell, select XAMPP's PHP for this terminal:

   ```powershell
   $env:Path = "C:\xampp\php;" + $env:Path
   php -v
   ```

   Confirm `PHP 8.2.12`. Enable `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `bcmath`, `dom`, and `xml` in `C:\xampp\php\php.ini` if missing. Enable `pdo_sqlite` for tests.

3. In phpMyAdmin, create `transolutions_db` with `utf8mb4_unicode_ci`. If this is the already-built workspace, its rebuilt database and `.env` are ready; do not recreate or overwrite them.
4. For a fresh checkout only:

   ```powershell
   Copy-Item .env.example .env
   composer install
   php artisan key:generate
   ```

   If Composer is not on PATH, use `php composer.phar install` with a verified Composer PHAR. The existing workspace's corrupt local PHAR was replaced with the installed working Composer binary.

5. Set database host, port, name, username, and password in `.env`. A default local XAMPP database commonly uses `root` and an empty password; configure actual credentials on your computer. Never use those development credentials on Render.
6. For a new empty development database:

   ```powershell
   php artisan migrate --seed
   ```

   The seeder generates private, random passwords in `storage/app/private/demo-accounts.txt`. It does not overwrite existing accounts and refuses production environments. To intentionally reset a disposable development installation, first back it up, then use `php artisan migrate:fresh --seed` (this destroys that database's tables).

7. Run:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8080
   ```

   Or double-click `start-local.cmd`. Visit **http://127.0.0.1:8080**. Keep that terminal open. No `npm install`, Vite, or CSS compilation is needed.
8. Open `storage/app/private/demo-accounts.txt` locally, sign in, and change the temporary password. Accounts: `admin@transolutions.test`, `secretary@transolutions.test`, `mechanic@transolutions.test`, `purchaser@transolutions.test`, and `customer@transolutions.test`.

To serve through XAMPP Apache, create a virtual host whose `DocumentRoot` and `<Directory>` point to this project's **public** directory. Set `AllowOverride All`, enable `mod_rewrite`, add the hostname to the Windows hosts file, and update `APP_URL`. Do not expose the project root as the web document root.

### Extra sample customers and workshop history

On an existing local demo database, run `php artisan db:seed --class=WorkshopDemoSeeder` to load the expanded pack of 48 fictional customers and vehicles, 120 paid service visits spread across recent months, and 16 bookings in pending, scheduled, processing, and completed stages. If the original 24-customer pack is already loaded, this adds only the remaining 24 customers, 72 paid visits, and 8 active bookings. Payment methods vary across visits to populate the layered chart. The admin graph opens on Weekly; choose Monthly to explore the full history.

This additive sample pack keeps existing records and login credentials, skips previously imported demo customers on repeat runs, and refuses production environments. Sample names include `(Demo)`, plates use `DEMO`, and emails use `.test`. It follows the booking/invoice workflow without consuming existing parts stock. Run this command separately from the fresh-database seeder; do not reset the database to add samples.

To give those sample customers demo logins, run `php artisan db:seed --class=WorkshopDemoAccountsSeeder` after loading the sample pack. This links each existing customer to a customer-role account and appends generated temporary passwords to `storage/app/private/demo-accounts.txt`. Existing credentials are preserved, and repeat runs skip linked accounts. On first login, change the password and enter a valid mobile number. Each account sees only its own vehicles and bookings; the original staff demo accounts retain their role-based access.

### Password reset email

Local `.env` uses `MAIL_MAILER=log`: reset links are written to `storage/logs/mail.log`, not sent over the internet. Open that private log during local testing. To send real messages, configure SMTP through `.env` using the fields in `.env.example`, set `MAIL_MAILER=smtp`, and run `php artisan config:clear`. Tokens expire after 15 minutes and can be used once. No queue worker is required.

## 3. Code, module by module

All code is saved directly in this workspace, rather than truncated into chat snippets. [FILES.md](docs/FILES.md) contains the complete application folder/file inventory.

Read or extend modules in this order:

| Order | Module | Files to start with |
|---|---|---|
| 1 | Migrations | `database/migrations/2026_10_07_000000_create_workshop_tables.php`, cache migration |
| 2 | Models and status | `app/Models/*`, `app/Domain/Bookings/BookingStatus.php` |
| 3 | Middleware/policies | `app/Http/Middleware/*`, `app/Policies/*`, `bootstrap/app.php` |
| 4 | Authentication | `AuthController`, Login/Register/Reset/Email requests, `views/auth/*`, `config/auth.php` |
| 5 | Dashboard | `DashboardController`, `views/dashboard/index.blade.php` |
| 6 | Bookings and repairs | `BookingWorkflow`, `BookingController`, booking/diagnosis/consent requests, `views/bookings/*` |
| 7 | Customers and vehicles | `CustomerController`, `VehicleController`, corresponding requests/views |
| 8 | Services | `ServiceController`, `ServiceRequest`, `views/services/*` |
| 9 | Inventory and orders | `InventoryWorkflow`, Inventory/Order controllers and requests, corresponding views |
| 10 | Sales and invoices | `BookingWorkflow::pay`, `InvoiceController`, `PaymentRequest`, `views/invoices/*` |
| 11 | Reports | `ReportBuilder`, `ReportController`, `ReportRequest`, `views/reports/*` |
| 12 | Users/profile | User/Profile controllers and requests, corresponding views |
| 13 | Seeds and tests | `database/seeders/DatabaseSeeder.php`, `tests/Feature/*`, `tests/browser/*` |

The application layer centralizes multi-record business changes. Controllers do not independently deduct stock or calculate invoices. Thin HTTP controllers, dedicated requests, policies, Eloquent models, and reusable Blade components make each fault's location explicit.

## 4. Design system

All application styling lives in [public/css/app.css](public/css/app.css); enhancements live in [public/js/app.js](public/js/app.js). Palette tokens are defined in `:root`: logo-inspired deep navy (`#090735`), crimson (`#AD1732`), white cards, and a soft off-white canvas (`#F5F6FA`). Body and input text use 16px at the default browser size, most supporting text uses 14–15px, and headings use a larger hierarchy. Font sizes use rem units to respect browser preferences. Shared inputs, status badges, icons, alerts, and progress steppers are in `resources/views/components`. Login and workspace layouts are separate.

The Admin dashboard includes a Sales overview graph with Daily (hourly), Weekly (Monday–Sunday), and Monthly (calendar day) views. Choose a Report date to review an earlier period, hover/tap for exact amounts, or use the arrow keys while the graph is focused. Only recorded payments count; unpaid, archived, and reversed payments are excluded. Each view links to its matching full report and includes an accessible amounts table. The graph uses CSS columns and plain JavaScript. Each column stacks actual payments by method (Cash, GCash, Bank Transfer, PayMaya, and Other for legacy records), with system palette colors and totals beneath the day labels. Weekly is the default view.

Pending = peach; Scheduled = lavender; Processing = powder blue; Completed = butter yellow; Paid/Released = pastel green. Labels accompany every color. System fonts and local assets keep the interface usable without a third-party CDN.

## 5. Render

Follow [the short deployment guide](docs/DEPLOYMENT.md). The supplied Blueprint provisions a Docker PHP/Apache web service and a private MariaDB service with persistent storage. Configure real SMTP and a production app key. Demo seeding is disabled in production; use `php artisan workshop:create-admin` to create the owner account.

## 6. Verification and checklist

```powershell
php vendor/bin/phpunit
php vendor/bin/pint --test app bootstrap config database routes tests
php artisan view:cache
composer audit
```

PHPUnit uses an isolated in-memory SQLite database, never the configured workshop database. It tests the complete workflow, stock/payments, data ownership, role access, first-login password enforcement, resets, scheduling capacity, all module views, reports, and seed consistency. Local migrations and seeds were also executed against XAMPP MariaDB 10.4.32.

Optional browser checks (Node required only for testing):

```powershell
npm.cmd ci
npm.cmd run test:browser
```

The script uses installed Microsoft Edge, XAMPP PHP, a disposable `test-results/browser.sqlite` database, and port 8091. It tests the full paid repair flow through real forms, all role dashboards, CSRF rejection, PDF download, and desktop/mobile overflow. Screenshots and the downloaded PDF are saved in `test-results`. Override `TEST_PHP` / `TEST_BROWSER` as needed. It never changes the workshop's MariaDB demo passwords.

See [UI/UX and security checklist](docs/CHECKLIST.md) for implemented safeguards and deployment tasks that require real hosting/mail credentials. Browser tests do not replace a complete accessibility audit or external penetration test.

Latest local result: **30 tests / 262 assertions passed**, plus the real-browser checks above. Run `npm.cmd run test:sales-chart` for the chart-specific desktop/mobile, tooltip, date, and keyboard checks. See [verification record](docs/VERIFICATION.md) for evidence and limits.

## 7. Demo

Follow [the step-by-step vehicle walkthrough](docs/DEMO.md). DAV 2105 is already fully paid and released; the other seeded vehicles demonstrate Pending, Scheduled, Processing, and Completed. All seeded stock is received through orders, and consumed parts are deducted through the repair workflow.

## Troubleshooting

- Wrong PHP version: use `C:\xampp\php\php.exe` explicitly or prepend XAMPP to PATH.
- Database connection refused: start XAMPP MySQL and check `.env` host/port/credentials, then `php artisan config:clear`.
- Changes not showing: `php artisan optimize:clear`, then reload. CSS is served directly from `public/css/app.css`.
- Sign-in redirects to Profile: change the generated temporary password and enter a valid mobile number.
- Reset email missing locally: open `storage/logs/mail.log`; no SMTP delivery occurs in log mode.
- Slot cannot be scheduled: the date already has four jobs or the exact time is reserved. Choose a different day/time.
- Parts cannot be used: ensure the job is Processing, diagnosis is approved, and sufficient stock has been received.
- Production error: inspect application/Render logs privately. Keep debug disabled for users.

Upstream references: [Laravel 12 release compatibility](https://laravel.com/docs/12.x/releases), [Laravel password broker](https://api.laravel.com/docs/12.x/Illuminate/Auth/Passwords/PasswordBroker.html), [Render Docker](https://render.com/docs/docker), [Render Blueprint configuration](https://render.com/docs/blueprint-spec).
