# Secretary and mechanic workspaces

The public home page is `/`. It introduces the workshop, services, and booking process. Visitors can sign in or create a customer account. Signing in opens the workspace for the account's role; there is no public role selector.

| Role | Workspace and responsibilities |
|---|---|
| Secretary | Front Desk: customers, vehicles, booking requests, scheduling, mechanic assignment, customer approval with a contact note, invoices, payment, and release |
| Mechanic | My Workbench: assigned jobs only, diagnosis, approved parts usage, and repair completion |
| Admin | All workshop operations, account management, pricing, inventory, reports, and reversals |
| Purchaser | Parts orders and receiving inventory |
| Customer | Own vehicles, bookings, repair approval, invoices, and service history |

Mechanics cannot access customer management, vehicle management, invoices, payments, scheduling, or other mechanics' jobs. Secretaries cannot change diagnoses, record used parts, or complete repairs. Routes and policies enforce these restrictions independently of the UI. Admin retains oversight of every job.

## Existing installations

Run `php artisan migrate` to apply `2026_10_08_000000_split_staff_roles`. Existing `staff` accounts become `mechanic` accounts. Their email addresses, passwords, account IDs, history, and repair assignments stay intact. Existing sessions are revoked so users sign in again. The existing `assigned_staff_id` database field and `staff` model relationship are retained for compatibility; they now refer to a mechanic.

The admin creates secretary accounts under **Users → New account**, or changes the role of an existing team member there. Existing staff email addresses are not renamed. For example, an existing `staff@transolutions.test` still signs in with the same email, now as a mechanic. Complete active jobs or arrange admin coverage before changing an assigned mechanic's role or deactivating their account.

Fresh demo seeding creates five accounts: admin, secretary, mechanic, purchaser, and customer. Passwords are generated privately as before. Do not reseed an existing database to add the new roles.

## Repair handoff

1. Customer or secretary creates the booking.
2. Secretary confirms the schedule and assigns an active mechanic to start the job.
3. Assigned mechanic adds a diagnosis.
4. Customer approves in the portal, or secretary records the customer's decision with a contact note.
5. Assigned mechanic records parts and completes the repair, generating the invoice.
6. Secretary records payment and releases the vehicle.
