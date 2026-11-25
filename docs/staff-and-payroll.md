# Staff and payroll

This page covers the staff register, monthly payslip generation, payslip edits and marking payslips as paid. It describes what the code does today, checked against the working tree on 2026-11-26. Money is stored as whole poisha (1 taka = 100 poisha) in integer columns. Access is limited to the owner and super admin roles.

## Code map

| Concern | File |
|---|---|
| Staff model | `app/Domain/Staff/Models/Staff.php` |
| Staff screens | `app/Http/Controllers/StaffController.php` |
| Staff validation | `app/Http/Requests/StoreStaffRequest.php`, `app/Http/Requests/UpdateStaffRequest.php` (extends the store request) |
| Payslip model | `app/Domain/Payroll/Models/Payslip.php` |
| Payslip generation | `app/Domain/Payroll/Actions/GeneratePayslipsAction.php` |
| Payroll screens | `app/Http/Controllers/PayrollController.php` |
| Payroll validation | `app/Http/Requests/GeneratePayrollRequest.php`, `app/Http/Requests/IndexPayrollRequest.php`, `app/Http/Requests/UpdatePayslipRequest.php` |
| Money helpers | `app/Support/Money.php` |
| Views | `resources/views/staff/`, `resources/views/payroll/` |
| Tests | `tests/Feature/StaffManagementTest.php`, `tests/Feature/PayrollTest.php` |

## Access and module switch

| Area | Route group | Module flag | Permission | Granted to |
|---|---|---|---|---|
| Staff register | `/staff` | `payroll` | `manage-staff` | owner, super_admin |
| Payroll | `/payroll` | `payroll` | `manage-payroll` | owner, super_admin |

- Both groups sit behind the `module:payroll` middleware. The switch is `modules.payroll` in `config/kcam.php`, set with the `KCAM_MODULE_PAYROLL` environment variable. When it is off, the routes return 404 and the menu entry disappears.
- Permissions come from `database/seeders/RoleAndPermissionSeeder.php`. Teacher, accountant and parent hold neither permission. The route file comments say "owner only", but super_admin also receives both permissions from the seeder.
- Every lookup is scoped to the current school. A payslip or staff record from another school returns 404 (covered by tests).

See [Roles and permissions](roles-and-permissions.md) for the full role matrix.

## Staff register

### Fields

Table `staff` (model `Staff`, table name set to `staff`):

| Column | Stored as | Input rule | Notes |
|---|---|---|---|
| `name` | string | required, max 255 | Free text. Control characters removed, Unicode NFC, trimmed. |
| `name_bn` | string, nullable | nullable, max 255 | Bangla name, shown in preference to `name` on payslips when present. |
| `designation` | string(100) | required, max 100 | Job title. |
| `phone` | string(20), nullable | nullable, `01[3-9]` followed by 8 digits | Normalised to the canonical 11-digit form before validation. |
| `joined_on` | date, nullable | nullable, format `Y-m-d` | |
| `salary_basic` | unsigned big integer (poisha), default 0 | form field `salary` is required, `^\d{1,9}(\.\d{1,2})?$` | Converted to poisha by `Money::toPoisha`. Edit form shows it with `Money::toTaka`. |
| `status` | string(20), default `active` | required, one of `active`, `inactive` | See statuses below. |
| `user_id` | FK to `users`, nullable | not accepted from the form | Never set by the staff screens. Only the factory sets it. |
| `school_id` | FK to `schools` | set by the tenancy trait | Cannot be changed through the form (mass assignment test). |
| `deleted_at` | soft delete column | none | No route deletes staff. |

Search on the staff list matches `name`, `name_bn`, `designation` and `phone`. The search string is cut to 100 characters.

### Statuses

| Value | Meaning in code |
|---|---|
| `active` | Included when a payroll month is generated. Default for new staff. |
| `inactive` | Excluded from new payroll runs. Existing payslips are kept. |

The owner dashboard shows active and total staff counts (`staffActive`, `staffTotal` in `DashboardController`).

### Screens and routes

| Screen | Method and path | Route name | Notes |
|---|---|---|---|
| Staff list | GET `/staff` | `staff.index` | Searchable, 15 per page, ordered by name. |
| Add form | GET `/staff/create` | `staff.create` | Status defaults to `active`. |
| Save new | POST `/staff` | `staff.store` | Calls `Staff::create` with validated data only. |
| Edit form | GET `/staff/{staff}/edit` | `staff.edit` | Salary shown as a taka string. |
| Save changes | PUT `/staff/{staff}` | `staff.update` | Same rules as create. |

There is no staff detail page, no delete action and no salary history. Changing a salary overwrites `salary_basic`.

## Payroll components as stored

Table `payslips` (model `Payslip`, soft deletes enabled):

| Column | Type | Meaning |
|---|---|---|
| `school_id` | FK, restrict on delete | Tenant key. |
| `staff_id` | FK to `staff`, restrict on delete | A staff member with payslips cannot be hard deleted. |
| `month` | string(7) | `YYYY-MM`. |
| `basic` | unsigned big integer, poisha | Copy of `staff.salary_basic` at generation time. |
| `deductions` | unsigned big integer, poisha, default 0 | Editable while pending. |
| `advances` | unsigned big integer, poisha, default 0 | Editable while pending. |
| `net` | unsigned big integer, poisha, default 0 | `basic - deductions - advances`. At generation, equal to `basic`. |
| `status` | string(20), default `pending` | `pending` or `paid`. Constants `Payslip::PENDING` and `Payslip::PAID`. |
| `paid_at` | timestamp, nullable | Set when marked paid. |
| `paid_by` | FK to `users`, nullable | Set when marked paid. |
| `notes` | string, nullable, max 255 | Free text on the payslip. |

Indexes: unique on `(school_id, staff_id, month)`, and an index on `(school_id, month, status)`.

Not stored anywhere: allowances, tax, provident fund, overtime, bonuses, bank account details, payment method, payment reference or payment amount. The word "allowance" does not appear in the application code, migrations or views.

## Generating a month

Route: POST `/payroll/generate` (`payroll.generate`), field `month`. The request accepts `YYYY-MM` with month 01 to 12 and converts Bangla digits first. There is no check against today's date, so past and future months are accepted.

`GeneratePayslipsAction::execute(int $schoolId, string $month): int` runs as follows:

1. Switches the school context with `CurrentSchool::runAs`, then wraps the whole batch in one database transaction.
2. Reads the `staff_id` values of every payslip already present for the month. The read includes soft-deleted payslips (`withTrashed`).
3. For each staff row with `status = active` that is not in that list, creates a payslip with `basic` and `net` set to `salary_basic`, deductions and advances at 0, and status `pending`.
4. If another run inserts the same slip first, the unique key raises `UniqueConstraintViolationException`. That row is skipped and the batch carries on.
5. Returns the number created. The flash message reads "Payslips created: N." (`payroll.flash.generated`).

Re-running is safe:

- Staff who already have a payslip for the month are skipped. Edited, paid and soft-deleted slips are never touched.
- A salary change after generation does not change an existing slip (covered by a test).
- A staff member hired after the first run gets a slip on the second run.
- A staff member set to inactive after generation keeps the slip from the first run. No new slip is created for them.
- A staff member with a salary of 0 gets a 0 payslip, because the form accepts 0.
- If the batch fails part way, the transaction rolls back and no half-generated month remains (covered by a test).

## Viewing and editing payslips

Index: GET `/payroll?month=YYYY-MM` (`payroll.index`). Without a month it uses the server's current month. The list is sorted by staff name. Totals are the sum of `net` for all slips in the month and the sum of `net` for paid slips.

Edit: PUT `/payroll/{payslip}` (`payroll.update`). Fields:

| Field | Rule | Notes |
|---|---|---|
| `deductions` | required, `^\d{1,9}(\.\d{1,2})?$` | Converted to poisha. |
| `advances` | required, `^\d{1,9}(\.\d{1,2})?$` | Converted to poisha. |
| `notes` | nullable, max 255 | |

Rules enforced in `UpdatePayslipRequest` and `PayrollController::update`:

- A paid slip cannot be changed. The request adds a deductions error, and the update query also requires `status = pending`, so a slip paid in the meantime is not changed.
- Deductions plus advances cannot exceed `basic`.
- `net` is recalculated on the server as `basic - deductions - advances`. The client never sends `net`.
- `basic` cannot be edited from any screen.

Print: GET `/payroll/{payslip}` (`payroll.show`) renders a payslip with a receiver and authority signature line. Printing uses the browser's `window.print()`. There is no PDF file.

## Marking as paid

Route: POST `/payroll/{payslip}/pay` (`payroll.pay`). It runs one conditional update:

```sql
UPDATE payslips SET status = 'paid', paid_at = NOW(), paid_by = :user
WHERE id = :id AND status = 'pending';
```

- A second press returns the flash "Already paid." and leaves `paid_at` and `paid_by` unchanged (covered by a test).
- There is no unpay action.
- Marking a slip paid records only the time and the user. No amount, method or reference is captured.
- Nothing in `app/Domain/Accounts`, `AccountsController`, the cash book or the expense views references payroll or salary. A paid payslip creates no expense and no cash book line.

## Money rules

- Every amount is an integer in poisha. Columns are unsigned big integers, models cast them to `integer`, and no float is used.
- Input parsing goes through `Money::toPoisha`, which accepts at most 9 digits before the decimal point and at most 2 after it, and throws on anything else.
- Display goes through `LocaleFormat::taka(poisha, true)`, which prefixes the taka sign.
- Net pay is computed in integer arithmetic on the server.
- The money migration `2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables.php` converted `staff.salary_basic` from decimal taka to poisha.

## Commands

```bash
php artisan test --compact --filter=PayrollTest
php artisan test --compact --filter=StaffManagementTest
```

See [Testing](testing.md) for the full suite and coverage targets.

## Limitations

- Basic salary is a snapshot. Changing a staff salary does not update existing pending payslips, and no screen can correct the `basic` of a pending slip. Fixing one needs a database change.
- Payment is a status flag. There is no paid amount, payment method, reference, undo, or link to Accounts or the cash book.
- No allowances, tax, provident fund, overtime or bonus components exist. Attendance-linked deductions are planned in `docs/roadmap.md` (item 2.6) and are not built.
- Payroll month input is not checked against dates. Any `YYYY-MM` value is accepted, including future months.
- Two payroll validation messages are hard-coded English strings in `UpdatePayslipRequest` ("A paid payslip cannot be changed." and "Deductions plus advances cannot exceed the basic salary."). They are not in the language files.
- The staff `user_id` column is never set by the UI, so a staff member cannot be linked to a login account from the app.
- Staff status has only two values. There is a joined date (`joined_on`) but no left date, no leave records and no salary history with effective dates.
- No staff delete or archive action exists. Staff who leave are set to `inactive`.
- Exports (CSV or PDF) for payroll are planned in `docs/roadmap.md` (item 3.4) and are not built. The payslip is printed from the browser only.
- Route comments in `routes/web.php` say "owner only", but super_admin also has both permissions.

## Open questions

- Should a paid payslip post an expense to Accounts? The repository does not say.
- Should a pending slip refresh `basic` when the staff salary changes, or stay frozen as now? The tests assert the frozen behaviour, but no document records it as a decision.
