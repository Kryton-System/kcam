# Database

This page describes the MySQL schema as the migrations in `database/migrations` define it. It covers the table groups, how money, tenancy, soft deletes and unique keys work, how receipt numbers are issued, the factories and seeders, and how to add a migration. The column lists are the important ones, not every column. Check the migration file before you rely on a column.

## Engine and conventions

- MySQL 8 with `utf8mb4` and `utf8mb4_unicode_ci` (`config/database.php`, overridable with `DB_CHARSET` and `DB_COLLATION`). Strict mode is on.
- `DB_CONNECTION` defaults to `sqlite` in `config/database.php` when it is unset. `.env.example` sets `mysql`. Set it explicitly on every install.
- Sessions, cache and queues use the database (`SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` in `.env.example`). Their tables come from the framework migrations.
- Timestamps are `created_at` and `updated_at`. Primary keys are auto-increment `id` columns, except `receipt_sequences`, which has a composite key.
- Child tables that are tenant data carry their own `school_id`, not only the parent's. See [School tenancy](architecture.md#school-tenancy).

## Money

Money is a whole number of poisha in an `unsignedBigInteger` column (1 taka = 100 poisha). This was decided in [decision 0001](decisions/0001-money-as-integer-poisha.md). The migration `2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables.php` converted the old decimal values with `ROUND(col * 100)`.

| Table | Money columns | Note |
|---|---|---|
| `invoices` | `total`, `paid`, `due` | Default 0 |
| `invoice_items` | `amount` | |
| `payments` | `amount` | |
| `fee_structures` | `amount` | |
| `fee_discounts` | `value` | Poisha when `type` is fixed. Basis points when `type` is `percentage` (10% is 1000). |
| `staff` | `salary_basic` | |
| `sms_logs` | `cost` | |
| `payslips` | `basic`, `deductions`, `advances`, `net` | Created as poisha |
| `expenses` | `amount` | Created as poisha |
| `transport_assignments` | `monthly_fee` | `unsignedInteger` (created after the conversion). Its limit is 4,294,967,295 poisha. |

Parse input with `App\Support\Money::toPoisha()`. It accepts at most 9 taka digits and 2 decimals. Format output with `Money::toTaka()` or `LocaleFormat::taka()`. Percent discounts use `Money::percentOf($amount, $basisPoints)`, which rounds half up. The school's invoice generator applies a discount when `type` is `percentage`. Any other value is treated as a fixed amount in poisha.

## Soft deletes

These tables have a `deleted_at` column, and their models use the `SoftDeletes` trait:

| Table | Model |
|---|---|
| `schools` | `School` |
| `students` | `Student` |
| `staff` | `Staff` |
| `invoices` | `Invoice` |
| `payslips` | `Payslip` |

Money rows also use `RESTRICT` on delete. The migration `2026_11_22_043948` sets `restrictOnDelete()` on the foreign keys of `invoices` (`school_id`, `student_id`, `academic_year_id`), `invoice_items` (`invoice_id`, `fee_head_id`), `payments` (`school_id`, `invoice_id`) and `receipts` (`school_id`, `payment_id`). Deleting a parent that still has money rows fails instead of erasing them.

## Tenancy columns

Every domain table has a `school_id` foreign key to `schools`, with `cascadeOnDelete()` in most migrations. These are the exceptions:

| Table | Note |
|---|---|
| `users` | Added by `2026_11_19_150002`. Nullable, with `nullOnDelete()`. Login uses the global unique email and mobile. |
| `schools` | This is the root. |
| `invoice_items` | Added by `2026_11_22_043948` and backfilled from `invoices`. It is `NOT NULL` with a restrict foreign key and an index on `(school_id, invoice_id)`. |
| Framework and spatie tables | Not tenant data. |

## Table groups

### Platform and framework

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `users` | Login accounts | `school_id`, `name`, `email` (unique), `mobile` (unique, nullable), `role` (default `parent`), `password`, `email_verified_at` |
| `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Framework tables for reset, sessions, cache, queues | Standard Laravel columns |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | spatie/laravel-permission, names set in `config/permission.php` | `unique(name, guard_name)`, teams disabled |
| `activity_log` | spatie/laravel-activitylog | `log_name`, `description`, `subject` and `causer` morphs, `event`, `properties`. No screen reads it. |

### School setup

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `schools` | One row per school (soft deletes) | `name`, `name_bn`, `eiin`, `phone`, `email`, `address`, `plan_id`, `status`, `settings` (JSON) |
| `academic_years` | Academic years | `name`, `starts_on`, `ends_on`, `is_current`. `unique(school_id, name)` |
| `classes` | Classes | `name`, `name_bn`, `numeric_name`, `order`. `unique(school_id, name)` |
| `sections` | Sections within a class | `name`, `name_bn`. `unique(class_id, name)` |
| `subjects` | Subjects per class | `code`, `full_marks`, `pass_marks`, `is_optional`. `unique(school_id, class_id, name)` |

### Students

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `students` | Student profile (soft deletes) | `student_code`, `name`, `name_bn`, `dob`, `gender`, `birth_reg_no`, `photo` (path), `blood_group`, `address`, `status`, `religion`. `unique(school_id, student_code)` |
| `guardians` | Guardians (cascade on student delete) | `student_id`, `name`, `relation`, `mobile`, `nid` (plain text), `email`, `is_primary`, `nid_front_path`, `nid_back_path` |
| `enrollments` | Student in a class and section for a year | `student_id`, `academic_year_id`, `class_id`, `section_id`, `roll_no`, `status`. `unique(student_id, academic_year_id)` |

### Attendance

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `attendance_records` | One mark per enrollment per day | `enrollment_id`, `date`, `status`, `marked_by`, `client_uuid` (nullable, no unique index), `synced_at`. `unique(enrollment_id, date)` |

### Exams

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `exams` | Exams per academic year | `name`, `name_bn`, `starts_on`, `ends_on`, `published_at` |
| `marks` | Marks per exam, enrollment and subject | `marks` and `gp` (decimal, not money), `grade`. `unique(exam_id, enrollment_id, subject_id)` |

### Fees, invoices and payments

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `fee_heads` | Fee types | `name`, `name_bn`, `type` (`monthly`, `one_time`, `yearly`), `is_active`. `unique(school_id, name)` |
| `fee_structures` | Amount per class, year and fee head | `academic_year_id`, `class_id`, `fee_head_id`, `amount`. `unique(academic_year_id, class_id, fee_head_id)` (named `fee_struct_unique`) |
| `fee_discounts` | Discount per student and fee head | `student_id`, `fee_head_id`, `type` (`percentage` or fixed), `value`, `reason` |
| `invoices` | Monthly invoice per student (soft deletes) | `student_id`, `academic_year_id`, `month`, `total`, `paid`, `due`, `status` (`unpaid`, `partial`, `paid`), `due_date`. `unique(school_id, student_id, academic_year_id, month)` |
| `invoice_items` | Lines of an invoice | `invoice_id`, `fee_head_id`, `amount` |
| `payments` | Money received against an invoice | `invoice_id`, `method` (`cash`, `bkash`, `nagad`, `rocket`, `card`, `bank_transfer`), `amount`, `gateway_txn_id` (unique across the install), `client_uuid`, `status`, `paid_at`, `collected_by`, `notes`. `unique(school_id, client_uuid)` |
| `receipts` | Printable receipt per payment | `payment_id`, `serial_no`, `pdf_path` (never set). `unique(school_id, serial_no)` |
| `receipt_sequences` | Gapless counter per school and year | `school_id`, `year`, `next_no`. Primary key `(school_id, year)`. See below. |

### Notices

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `notices` | Notices to an audience | `title`, `title_bn`, `body`, `body_bn`, `audience` (`all`, `class`, `section`, `parents`, `teachers`), `target_class_id`, `channel`, `sent_at`, `created_by` |
| `sms_logs` | SMS records | `to`, `body`, `provider_ref`, `status`, `cost`, `sent_at`. Nothing writes to it yet. |

### Accounts

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `expense_categories` | Expense types | `name`, `name_bn`. `unique(school_id, name)` |
| `expenses` | Expenses (voided, not deleted) | `expense_category_id`, `spent_on`, `amount`, `payee`, `note`, `recorded_by`, `voided_at`, `voided_by`, `void_reason` |

### Inventory

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `inventory_items` | Stock items | `name`, `unit`, `low_stock_threshold`. `unique(school_id, name)` |
| `stock_movements` | Stock in, out and adjustments | `inventory_item_id`, `type` (`in`, `out`, `adjustment`), `quantity`, `reason`, `note`, `user_id` |

### Staff and payroll

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `staff` | Staff profile (soft deletes) | `user_id`, `name`, `name_bn`, `designation`, `phone`, `joined_on`, `salary_basic`, `status` |
| `payslips` | Monthly payslip (soft deletes) | `staff_id`, `month` (string of 7), `basic`, `deductions`, `advances`, `net`, `status` (`pending`, `paid`), `paid_at`, `paid_by`, `notes`. `unique(school_id, staff_id, month)` |

### Transport

| Table | Purpose | Key columns and constraints |
|---|---|---|
| `transport_vehicles` | Vans | `van_number`, `driver_name`, `driver_mobile`, `capacity`. `unique(school_id, van_number)` |
| `transport_routes` | Routes on a vehicle | `vehicle_id`, `name`, `name_bn`, `stops` (text) |
| `transport_assignments` | Student on a route | `route_id`, `enrollment_id`, `stop`, `monthly_fee` (poisha). `unique(enrollment_id)`: one route per enrollment. |

## Receipt numbering

Receipt numbers are gapless per school and per calendar year. They are issued in `CollectFeeAction` (`app/Domain/Fees/Actions/CollectFeeAction.php`), inside the same transaction that writes the payment.

1. `CollectFeeAction::execute()` runs its private `collect()`, which opens `DB::transaction` and locks the invoice row with `lockForUpdate()`. Two collections on one invoice run one after the other.
2. After the lock, a client UUID (when given) is checked. If a payment with that UUID already exists for the school, its receipt is returned and nothing is collected again.
3. `nextSerial()` reads the year from `date('Y')`. It runs `insertOrIgnore` to create the `receipt_sequences` row with `next_no = 1` if needed.
4. It reads `next_no` with `lockForUpdate()`, writes `next_no + 1`, and returns `sprintf('REC-%d-%05d', $year, $number)`. The format is `REC-2026-00001`.
5. The receipt row is written with that serial. `unique(school_id, serial_no)` is the backstop.
6. Because the counter is in the same transaction, a rolled back payment also rolls back its number, so there are no gaps.

Never derive a number by counting receipts. That is why the table exists.

Idempotency: `payments.client_uuid` is unique per school, and a repeated collection returns the first receipt. Attendance works differently. `RecordAttendanceAction` looks up the row by school, enrollment and date, then updates it or creates it. It does not look up `client_uuid`, and that column has no unique index.

## Unique keys at a glance

| Scope | Columns | Protects |
|---|---|---|
| Install-wide | `users.email`, `users.mobile`, `payments.gateway_txn_id`, spatie `roles(name, guard_name)` | Login identity, gateway references |
| Per school | `academic_years(school_id, name)`, `classes(school_id, name)`, `subjects(school_id, class_id, name)`, `fee_heads(school_id, name)`, `students(school_id, student_code)`, `invoices(school_id, student_id, academic_year_id, month)`, `payments(school_id, client_uuid)`, `receipts(school_id, serial_no)`, `payslips(school_id, staff_id, month)`, `expense_categories(school_id, name)`, `transport_vehicles(school_id, van_number)`, `inventory_items(school_id, name)` | Business identifiers and duplicates |
| Per class or enrollment | `sections(class_id, name)`, `enrollments(student_id, academic_year_id)`, `attendance_records(enrollment_id, date)`, `marks(exam_id, enrollment_id, subject_id)`, `transport_assignments(enrollment_id)` | One row per child, class or period |
| Per year | `fee_structures(academic_year_id, class_id, fee_head_id)` | One fee per class and head |

`users.mobile` is unique across the install, not per school. Two schools cannot share a mobile number for their login accounts.

## Factories and seeders

- `database/factories` holds 30 factories, one per model. `AppServiceProvider` maps `App\...\Models\X` to `Database\Factories\XFactory`. Tests use them through `School::factory()`, `User::factory()` and similar calls.
- `DatabaseSeeder` calls `RoleAndPermissionSeeder`, then `DemoSchoolSeeder`.
- `RoleAndPermissionSeeder` is safe to run again. It uses `firstOrCreate` and `syncPermissions`.
- `DemoSchoolSeeder` runs only in `local` and `testing` and inside `CurrentSchool::runAsPlatform()`. It is not safe to run twice (see [Getting started](getting-started.md#demo-data-local-and-testing-only)).
- `kcam:install` runs `RoleAndPermissionSeeder` and then `SetupSchoolAction`. It creates no demo rows.

## Adding a migration

Create a migration with the Artisan generator, then edit the file:

```bash
php artisan make:migration add_example_column_to_students_table --no-interaction
php artisan migrate
php artisan test --compact
```

Rules for the file:

- Never edit a migration that has run on any install. Add a new one. Do not delete existing migrations.
- A tenant table gets `$table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();` and an index that starts with `school_id`.
- A money column is `unsignedBigInteger` in poisha with `default(0)`. Money foreign keys use `restrictOnDelete()`.
- Put `school_id` first in every unique key that identifies a business record (see the table above).
- Add the model with the `BelongsToSchool` trait, a factory in `database/factories`, and a Pest feature test written first.
- Give each change a working `down()`. The money migration shows the pattern for reversing a conversion.
- Back up the database before running `php artisan migrate --force` on a live install. See [Deployment](deployment.md#database-backups).

## Limitations

- Receipt demo collision (reasoned from code, not run): `DemoSchoolSeeder` writes receipts with serials `REC-2026-00001` to `REC-2026-00003` (`database/seeders/DemoSchoolSeeder.php`, line 349) and does not create a `receipt_sequences` row. The first real collection in 2026 on the demo school would also take `REC-2026-00001`, and `unique(school_id, serial_no)` would reject it. Fix it by hand (insert the sequence row for that school and 2026 with `next_no = 4`) or recreate the local database.
- `receipts.pdf_path` is never set, and no PDF generator exists.
- `guardians.nid` and `guardians.nid_front_path`, `nid_back_path` are plain text. No model uses an encrypted cast.
- `attendance_records.client_uuid` has no unique index, so the column does not by itself make a sync idempotent.
- `payments.gateway_txn_id` is unique across the whole install, not per school. A future gateway must produce globally unique references.
- `fee_discounts.type` is free text. Any value other than `percentage` is treated as a fixed amount, so a typo changes the meaning silently. No screen creates discount rows yet.
- `invoices.month` is a free string. Uniqueness depends on every writer using the same format.
- `expenses` are voided (`voided_at`, `voided_by`, `void_reason`), not deleted. There is no soft delete on that table.
- Dates and timestamps use the application timezone, which is `UTC` in `config/app.php`. The school's `Asia/Dhaka` setting is stored but not read. Receipt serials take their year from `date('Y')` in that timezone, so receipts issued between 00:00 and 05:59 on 1 January Bangladesh time get the previous year's prefix.
