# Architecture and data map

The system keeps booking, customer, vehicle, stock, and invoice records connected. The same booking remains the job order through all five stages. Service history and sales reports read those records directly; they are not manually copied into separate tables.

## Boundaries

| Layer | Location | Responsibility |
|---|---|---|
| Domain | `app/Domain/Bookings/BookingStatus.php` | Valid booking stages, labels, and their order; no Laravel dependency |
| Application | `app/Application/BookingWorkflow.php` | Creation, scheduling, assignment, diagnosis, consent, consumption, completion, payment, reversal, and archival |
| Application | `app/Application/InventoryWorkflow.php` | Draft → ordered → received; atomic inventory receipts |
| Application | `app/Application/ReportBuilder.php` | Reusable report queries for screen, CSV, and PDF |
| Application | `app/Application/DashboardSalesReport.php` | Calendar-based paid-sales buckets and exact-cent summary totals for Admin graphs |
| Application | `app/Application/Audit.php` | Activity records without passwords or tokens |
| Persistence | `app/Models`, `database/migrations` | Eloquent relationships, casts, database constraints, soft deletion |
| HTTP boundary | `app/Http/Requests` | Allowlisted, server-validated input; unexpected fields are discarded |
| Access boundary | `app/Http/Middleware`, `app/Policies` | Roles, active accounts, forced password changes, ownership checks, security headers |
| Delivery | `app/Http/Controllers`, `routes/web.php` | Thin, module-specific request handlers |
| Presentation | `resources/views`, `public/css`, `public/js` | Blade pages, shared components, plain CSS, small progressive enhancements |

This is a pragmatic layered Laravel architecture. The application layer deliberately uses Eloquent and transactions; it does not pretend to be a framework-independent hexagonal implementation. Avoid introducing generic repositories that merely repeat Eloquent operations. Extract an interface when a second implementation is actually needed.

## Text ERD

```text
users 1 ── 0..1 customers (customers.user_id nullable, unique)
customers 1 ── * vehicles
customers 1 ── * bookings
vehicles 1 ── * bookings
services 1 ── * bookings (service price copied at booking creation)
users 1 ── * bookings (created_by, assigned_staff_id, consent_recorded_by)
bookings 1 ── * job_parts * ── 1 parts
bookings 1 ── 0..1 invoices (unique booking_id)
invoices 1 ── * invoice_items (description and price snapshots)
users 1 ── * invoices (paid_by)
users 1 ── * parts_orders (created_by)
parts_orders 1 ── * order_items * ── 1 parts
parts 1 ── * stock_movements
bookings / parts_orders 1 ── * stock_movements (nullable source references)
users 1 ── * stock_movements
users 1 ── * activity_logs (subject_type + subject_id identify the changed record)
users 1 ── * notifications (Laravel database notification morph)
schedule_days (date primary key; scheduling mutex per day)
password_reset_tokens (Laravel broker)
sessions, cache, cache_locks (Laravel infrastructure)
```

Roles are a validated string on `users`: admin, secretary, mechanic, purchaser, customer. Admin manages team roles through Users; public registration always creates a customer. See [role permissions and migration](ROLES.md). A walk-in customer can exist without an account. Secretaries record that customer's approval with a contact note; registered customers can decide in their portal. Contact email edits do not modify sign-in emails. Existing walk-in records are not automatically linked to later registrations based only on an unverified email address.

## Workflow invariants

1. Every state mutation re-reads the booking inside a database transaction with `lockForUpdate`. A hidden expected status detects stale forms; it does not authorize anything.
2. Scheduling locks a row for the selected date, then checks the four-booking capacity and exact time collision. Customers can request dates; only the shop confirms slots.
3. Processing requires an active assigned Mechanic user. Parts usage and completion require diagnosis and an agreed customer decision. Updating diagnosis clears approval. Diagnosis is locked after parts have been consumed.
4. Part consumption locks the booking first and the part second. It rejects insufficient stock before writing job parts or stock movement records.
5. Receipt locks the order and then parts in ascending ID order. A received order cannot be received twice. This version receives whole orders; partial deliveries should remain unreceived until complete.
6. Completion snapshots service and part lines, calculates money using BCMath, and writes a database notification. A unique booking reference prevents duplicate invoices.
7. Payment locks booking then invoice, accepts only full payment, and updates both records atomically. Repeated payment attempts fail. Reports count paid invoices by `paid_at` in Asia/Manila time.
8. Admin reversals move exactly one step backward and require a reason. Reversing Paid/Released clears the recorded payment after preserving it in the audit log. Reversing Completed soft-deletes the unpaid invoice, regenerated on the next completion. A job with consumed parts cannot reverse to Scheduled. Reversals never silently restock physically consumed parts.
9. Customers, bookings, and invoices use soft deletion. Only pending bookings can be archived. Customer archival requires no open bookings and disables a linked portal account.

## Where to make changes

| Need | Start here |
|---|---|
| Scheduling capacity | `config/workshop.php`, `BookingWorkflow::advance` |
| Wrong totals or payment behavior | `BookingWorkflow::invoice` and `pay`, then workflow tests |
| Incorrect inventory | `InventoryWorkflow`, `BookingWorkflow::usePart`, stock movement ledger |
| Missing or excessive access | Role groups in `routes/web.php`, model policies, `EnsureActive` |
| Validation wording | Corresponding Form Request and field component |
| Prices | Services screen for defaults; Admin diagnosis form for per-job estimate |
| Layout, mobile behavior, colors | `layouts/app`, CSS variables and responsive sections in `public/css/app.css` |
| Password/reset behavior | `AuthController`, `config/auth.php`, `config/mail.php` |
| Reports | `ReportBuilder`; reuse the same dataset for all exports |
| Admin dashboard graph | `DashboardSalesReport`, `DashboardRequest`, `views/dashboard/sales-chart.blade.php`, sales chart CSS/JS sections |
| Troubleshooting | `storage/logs/laravel.log`; owner Reports → Recent activity |

For a new feature: add a migration if required, model relationships/casts, application action, request validation and authorization, controller/route, then a Blade screen. Test financial, ownership, and concurrency-sensitive invariants before wiring the UI.
