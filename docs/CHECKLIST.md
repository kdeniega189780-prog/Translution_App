# Delivery and verification checklist

## Interface

- [x] Plain Blade, CSS, and minimal JavaScript; no front-end framework or asset build required.
- [x] Pastel palette variables, soft raised cards, inset fields, rounded corners, active sidebar pills, ring and chart visuals.
- [x] Dark text on pastel surfaces, visible keyboard focus, borders in addition to shadows, 44px controls.
- [x] Named fields, icon labels, skip link, status text, accessible stepper, password visibility and strength feedback.
- [x] Separate role menus and dashboards, daily schedule, simple forms, success/error and empty states.
- [x] Responsive sidebar, phone layouts, scrollable tables, print invoice, downloadable PDF/CSV reports.
- [x] Confirmation for archival, payment, reversals, receipt, password reset request, and removing an order line.
- [x] Submit busy state and duplicate-submit prevention; financial invariants are independently enforced on the server.

## Authentication and authorization

- [x] Laravel Auth, `hashed` model cast, and password broker; no custom hash algorithm.
- [x] Server-side minimum length, uppercase, number, and symbol rules, confirmation, generic login/reset messages.
- [x] Five credential failures per email/IP cause a one-minute lockout, with an additional endpoint throttle.
- [x] Sessions regenerated at authentication and profile/password changes; logout invalidates session and CSRF token.
- [x] Password reset tokens expire in 15 minutes and are single-use.
- [x] Customer registration ignores supplied role; only Admin creates Secretary/Mechanic/Purchaser accounts.
- [x] Deactivation and password/role changes revoke previous sessions through an account session version.
- [x] All operational routes require authentication, active account checks, and role middleware; record policies enforce customer ownership.
- [x] All user-supplied mutations go through allowlisted Form Requests. Every state-changing HTML form has CSRF protection.
- [x] Eloquent/query builder persistence, escaped Blade output, and CSV formula neutralization.
- [x] No public file uploads; no upload attack surface. Private credential files are not served.

## Records and workflow

- [x] Forward-only status sequence; stale submissions rejected; audited, reasoned Admin reversal.
- [x] Row locks and transactions around scheduling, parts usage, receipts, completion, and payment.
- [x] Insufficient stock rejected; duplicate receipt/payment rejected; every receipt/consumption has a ledger entry.
- [x] Customer repair consent required; revised diagnosis requires new consent; used parts lock the approved diagnosis.
- [x] Invoice automatically generated from price snapshots and parts; BCMath used for invoice arithmetic.
- [x] Paid invoices cannot be edited. Admin must explicitly reverse a payment, which is recorded in activity history.
- [x] Customer, booking, invoice soft deletion and preserved vehicle service records.
- [x] In-app completion notification; paid invoices feed the sales report without data re-entry.
- [x] Five sample stages, four generated-password accounts, nine services, fifteen parts, and a fully paid example.

## Operations and practical limits

- [x] `.env` secrets excluded; generated demo credentials private and ignored; all demo accounts must change passwords.
- [x] Production debug disabled, HTTPS generated URLs, secure cookie settings, HSTS, CSP, and generic error pages.
- [x] XAMPP instructions, Docker/Render configuration, deployment guide, database ERD, module map, and demo script.
- [x] Automated HTTP/workflow suite and real-browser desktop/mobile checks provided; see README for commands and results.
- [ ] Live Render deployment: configuration supplied, account deployment not performed.
- [ ] External email delivery: SMTP credentials must be supplied and verified by the shop. Local reset messages go to `storage/logs/mail.log`.
- [ ] Production security review of requested historical runtime versions and actual hosting configuration before public rollout.

Automated tests and local review support these checks. This is not an independent penetration test or a certification of regulatory compliance. Partial deliveries, partial payments/refunds, payment gateway collection, tax accounting, and email-based automatic customer merging are outside this deliberately simple first version.
