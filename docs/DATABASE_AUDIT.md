# Database migration audit — October 8, 2026

## Result

The current schema migrations are complete, but the pre-rebuild business data was **not fully migrated**. The current database contains the rebuilt application's sample records and the subsequently created accounts. Matching schema migration status does not demonstrate that historical records were imported.

This audit compared the configured local `transolutions_db` database with `C:/Users/Bochoy/Downloads/Translution_App_backup_20261007_221018/database.sql`. The SQL backup was parsed as text, without executing or importing it. Database checks were read-only. This does not verify any hosted database or records that were never included in that backup.

## Business records

| Entity | Backup records | Current records | Comparison |
|---|---:|---:|---|
| Users | 6 | 6 | 5 matching emails; an email match alone does not establish preservation of account details, passwords, or relationships |
| Customers | 3 | 1 | No matches by normalized full name, email, or phone |
| Vehicles | 3 in `customer_vehicles` | 5 in `vehicles` | No matching normalized plates; the old `vehicles` table was empty |
| Bookings | 3 | 5 | Current customer/vehicle records do not match the backup's customers/vehicles; these counts do not represent successful migration |
| Parts | 4 | 15 | No matching normalized part names |
| Services | 8 | 9 | 5 matching normalized names; prices and other fields are not established as preserved |
| Invoices | 1 | 2 | No matching invoice number |
| Purchase orders | 1 in `purchase_orders` | 2 in `parts_orders` | Different schema; no complete historical import verified |
| Purchase order items | 1 | 18 | Different schema; counts alone do not establish correspondence |
| Notifications | 4 | 2 | Full preservation not established |

The backup also has one affiliate, one inquiry, one job card, four part categories, four settings, and one supplier. Those tables have no same-named counterparts in the new schema. Their contents need explicit mapping or archival preservation during a historical import. Old mechanic assignments and booking services also use separate tables, while the rebuilt schema stores a primary mechanic and service on each booking.

## Current database checks

- All three application migrations have run: workshop tables, cache tables, and the staff-role split.
- Roles: 1 admin, 1 secretary, 2 mechanics, 1 purchaser, and 1 customer. No `staff` role remains.
- The newly created `secretary@transolutions.test` and `mechanic@transolutions.test` accounts are present.
- All 21 declared foreign-key relationships were checked: no orphan references were found.
- No booking is assigned to a user with a non-mechanic role.
- These checks establish current referential consistency, not full historical completeness or field-by-field equality.

## Next step

A dedicated historical data import is needed. Preserve the current database and existing accounts, map old identifiers to new records, and reconcile customers, vehicles, bookings/job cards, services, stock, orders, invoices, and legacy-only fields. Account emails that already exist require reconciliation rather than replacement. The backup remains available at the path above; this audit did not restore, delete, reseed, or update any database records.

Aggregate machine-readable results and the read-only comparison script are stored privately under `storage/app/private/database-migration-audit.json` and `storage/app/private/check-database-migration.mjs`. The report excludes passwords and customer contact details.
