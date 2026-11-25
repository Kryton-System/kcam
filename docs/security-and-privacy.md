# Security and privacy

This page covers how KCAM keeps one school's data apart from another's, how users sign in, how uploads and personal data are stored and shown, how payments are protected from duplicates and rounding errors, and what a reviewer should check before merging. Roles and gates are described in [Roles and permissions](roles-and-permissions.md). Versions: `composer.json` requires PHP ^8.3 and `laravel/framework` ^13.17 (`composer.lock` pins v13.43.0). The local CLI used during this review is PHP 8.4.27.

## What is protected

| Asset | Where it lives | Who can reach it |
|---|---|---|
| Student profile, class, status, date of birth, religion, address | `students` table | Owner, teacher, accountant (`view-students`) |
| Guardian name, mobile, relation | `guardians` table | Owner, teacher, accountant; the parent for linked children only |
| Guardian NID number (`guardians.nid`) and NID scans | `guardians` table and private disk | Owner, teacher, accountant (`view-students`) |
| BDRIS birth registration number (`students.birth_reg_no`) | `students` table | Owner, teacher, accountant (`view-students`) |
| Student photo | private disk | Owner, teacher, accountant (`view-students`) |
| Invoices, payments, receipts | `invoices`, `payments`, `receipts` | Owner and accountant; the parent for linked children |
| Login credentials | `users` table (hashed password) | The account holder only |

## How denied requests are answered

| Situation | Response | Where it is decided |
|---|---|---|
| Module switched off | 404 | `App\Http\Middleware\EnsureModuleEnabled` |
| Role lacks the permission | 403 | `can:` route middleware, or FormRequest `authorize()` |
| Record belongs to another school, or the id does not exist | 404 | Global `school` scope applied during route model binding |
| Parent opens a child who is not linked to them | 404 | `PortalController::show` |
| Public registration in single mode | 404 | `RegisteredSchoolController::ensureOpen()` |
| Parent opens the dashboard with the portal off | 403 | `DashboardController` |
| Wrong login or too many attempts | Validation error on the login field | `AuthenticatedSessionController::store()` |
| Collect amount above the invoice due | Validation error on the amount field | `FeeController::collect()` (converts `InvalidArgumentException`) |

Laravel resolves route model binding before route middleware, so a foreign or missing id returns 404 even for a role that would otherwise get 403 on that route. This follows from the framework's middleware order. It was not run as a separate test.

Tests that pin this behaviour: `StudentProfileTest` (another school's student is not found on show and edit), `StaffManagementTest` (edit and update return 404 for a foreign staff record), `InventoryTest` (show and movements return 404 for a foreign item), `TransportTest` (show, print and edit return 404 for a foreign route), `ParentPortalTest` (unlinked and foreign children return 404, staff get 403).

## School isolation

Multi-school data sits in one database. Isolation depends on the code below, so review any change that touches it.

- Tenant models use `App\Support\Traits\BelongsToSchool`. The trait adds a global scope named `school` that filters `<table>.school_id` to the current school. On create it fills `school_id` when it is empty.
- The scope fails closed. With no school context and not in platform mode, a query throws `App\Support\Tenancy\MissingSchoolContext` instead of returning every school's rows.
- The school context comes from `App\Support\Tenancy\CurrentSchool::id()`: the logged-in user's `school_id`. In single mode (`config/kcam.php`, the default), the only school in the install is used when the user has no `school_id`.
- `CurrentSchool::runAs($schoolId, fn)` sets a school for jobs, actions and seeders. The actions that do this include `CollectFeeAction`, `GenerateMonthlyInvoicesAction`, `AdmitStudentAction`, `RecordAttendanceAction`, `GeneratePayslipsAction`, `RecordStockMovementAction` and `SetupSchoolAction`.
- `CurrentSchool::runAsPlatform(fn)` is the only cross-school escape. It is greppable on purpose. In the code it is used only by `DemoSchoolSeeder`.
- `TenantContextTest` scans the model files under `app/Domain/*/Models` and asserts that every model with a `school_id` column uses `BelongsToSchool`. `User` is excluded on purpose, because it is the table that identifies the school. A new tenant model without the trait fails that test.
- Code that bypasses the global scope with `withoutGlobalScope('school')` must sit inside `runAs`. Current uses are in `CollectFeeAction` (invoice lock by id), `GenerateMonthlyInvoicesAction`, `AdmitStudentAction` (student code sequence) and `RecordAttendanceAction`.
- Form requests add school checks to foreign keys: `Rule::exists('classes', 'id')->where('school_id', ...)` in `IndexStudentsRequest`, `IndexAttendanceRequest` and `StoreNoticeRequest`, and the same pattern for sections in `IndexAttendanceRequest`.

Super admin: the `super_admin` role has no `school_id`. On a school route in multi mode, the first scoped query throws `MissingSchoolContext`, which becomes a server error. In single mode with exactly one school, the super admin is scoped to that school through the fallback in `CurrentSchool::id()`. No test covers a school route for super admin. `TenantContextTest` checks only that a query with no school throws `MissingSchoolContext`. There is no super admin screen. See [Limitations](#limitations-and-open-questions).

## Authentication

Source: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `RegisteredSchoolController.php`, `routes/web.php`.

- Routes: `GET /login`, `POST /login` (guest middleware), `POST /logout` (auth middleware). There is no `GET /logout`.
- The login field accepts an email address or a Bangladesh mobile number. The lookup is `email = login OR mobile = canonicalMobile(login)`.
- For an unknown login, the controller still runs one password check against a dummy hash, so response time does not reveal whether the account exists. Both failure cases return the same `auth.failed` message.
- Throttling has two layers:
  - Route: `POST /login` is limited to 20 requests per minute (`throttle:20,1`).
  - Application: the key is the lowercased login plus the client IP. After 5 failed attempts the key is refused until the 60-second `RateLimiter` window ends. A successful login clears the key. A failed login adds one attempt.
  - Limit of this design: an attacker who rotates login names is held to the route limit (20 per minute per IP), not to a per-account lockout.
- Registration: `POST /register` is limited to 5 requests per minute. The phone must match `01[3-9]\d{8}`, must not already be a user's mobile (`unique:users,mobile`), and the owner email must be unique in `users`. The password uses `Password::defaults()` with confirmation.
- On success, the session ID is regenerated (`$request->session()->regenerate()`). Logout invalidates the session and regenerates the CSRF token.
- Password rules (`AppServiceProvider`): minimum 8 characters everywhere. In production the rule also requires `uncompromised()`, which rejects passwords found in known breach data.
- Passwords are stored through the `password => hashed` cast on `User`.
- Session defaults (`config/session.php`): lifetime 120 minutes, `expire_on_close` false, `http_only` true, `same_site` lax, `encrypt` false. The secure cookie flag comes from `SESSION_SECURE_COOKIE`, which is unset by default, so it follows the request scheme. Set the production values in the deployment environment (see [Deployment](deployment.md)).
- Not present: email verification enforcement (the owner account is created with `email_verified_at` null, and no route uses the `verified` middleware), password reset routes, two-factor authentication, and a change-password screen.
- Demo accounts: the login page shows demo shortcuts only when `app()->environment('local')`. `DemoSchoolSeeder` runs only in the `local` and `testing` environments and logs a warning otherwise. A test checks that the shortcuts do not appear in other environments.

## CSRF

- Web routes run through Laravel's default web middleware group, which validates CSRF tokens. `bootstrap/app.php` adds no exceptions.
- 18 of the 22 Blade views that contain a `<form>` include `@csrf`. The other four are `GET` filter forms (students index, accounts index, cash book, staff index), which change no data.
- The `POST /logout` form is protected the same way, and logout regenerates the token.

## Validation layer

- The app has 23 Form Request classes under `app/Http/Requests`. Each write goes through a FormRequest with `authorize()` and `rules()`, so invalid or unauthorised input never reaches an action.
- The `NormalizesInput` trait (`app/Http/Requests/Concerns/NormalizesInput.php`) runs before validation:
  - Text fields lose control characters, are NFC-normalised and trimmed. Bangla conjunct joiners are kept.
  - Digit fields turn Bangla digits into ASCII digits.
  - Mobile fields are canonicalised to `01XXXXXXXXX`.
- Output escaping: Blade `{{ }}` escapes by default. `resources/views` contains no `{!! !!}` output at the time of writing. Keep it that way for user-entered text.
- SQL: queries use Eloquent or the query builder with bindings. The raw expressions in the code use fixed column names. The name-search helper `App\Support\Helpers\Search` escapes `%` and `_` in user input with `!` as the escape character, which works the same on MySQL and SQLite.
- Size and format limits in use include: student address 500, notice body 5000, expense payee 150, search term 100, sort and status restricted to fixed lists in `IndexStudentsRequest`, and the student list `sort` limited to `name`, `code` or `newest`.
- Error output: `config/app.php` sets `debug` from `APP_DEBUG`, which defaults to false. Exceptions on JSON requests render JSON (`bootstrap/app.php`).
- `GET /health` returns only the status, the app name, the database connection state and a timestamp (`HealthCheckController`). It exposes no environment values.

## Security headers and CSP

Source: `app/Http/Middleware/SecurityHeaders.php`, appended to the web group in `bootstrap/app.php`.

- On every response: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` only when the request is secure.
- The policy is sent as `Content-Security-Policy` in every environment except `local`, where it is `Content-Security-Policy-Report-Only`. The policy is `default-src 'self'`, `script-src 'self' 'unsafe-inline' 'unsafe-eval'`, `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com`, `font-src 'self' https://fonts.gstatic.com`, `img-src 'self' data: blob:`, `connect-src 'self'`, `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'none'`.
- `script-src` allows inline script and `eval`. The code comment says Livewire and Alpine need this. The comment says the move to nonces is tracked in `docs/dev-plan.md` (item 5.4).

## Uploads and private storage

Source: `StudentController::store()`, `update()`, `photo()`, `guardianNid()`; `StoreStudentRequest`, `UpdateStudentRequest`.

| Upload | Form field | Validation | Stored at | Served by |
|---|---|---|---|---|
| Student photo | `photo` | `image`, `mimes:jpg,jpeg,png,webp`, `max:2048` (KB) | `students/{school_id}/photo/` on the `local` disk | `GET /students/{student}/photo` (`view-students`) |
| Guardian NID front | `guardian_nid_front` | `file`, `mimes:jpg,jpeg,png,pdf`, `max:2048` | `students/{school_id}/guardian-nid/` | `GET /students/{student}/guardian-nid/front` (`view-students`) |
| Guardian NID back | `guardian_nid_back` | same as front | same folder | `GET /students/{student}/guardian-nid/back` (`view-students`) |

- The `local` disk root is `storage/app/private` (`config/filesystems.php`) and it is not linked to the web root. The `public` disk is a separate disk and is not used for these files.
- Files are written with Laravel's `store()`, which generates the file name. The client's file name never becomes part of the path.
- Files are returned with `Storage::disk('local')->response()`, and only after the route's permission check. A missing photo or scan returns 404.
- On update, the replaced files are deleted after the database save succeeds (`StudentController::update`). A failed save keeps the old file. No student delete route exists, so student files are not removed by deletion.
- `mimes` checks the file type. It does not scan for malware, re-encode images or strip metadata.
- Viewing or downloading a scan writes no activity log row. `StudentController` does not call `activity()`.
- Any role with `view-students` can open every scan in its school. There is no per-student or per-purpose check beyond the permission.

## BDRIS and NID numbers

- BDRIS birth registration number (`students.birth_reg_no`, string 50). The UI label is "BDRIS birth registration number (BRN)" (`lang/en/students.php`). On admission the value is optional. When given it must be exactly 17 digits (`digits:17`), after Bangla digits are converted, and it must be unique among the school's non-deleted students. The update rules apply the same 17-digit check and uniqueness.
- Guardian NID number (`guardians.nid`, string 50). When given it must match `^(?:\d{10}|\d{13}|\d{17})$` after digit conversion (`StoreStudentRequest`, `UpdateStudentRequest`).
- Storage: both values are plain text columns. The models have no `encrypted` cast. `Student` casts `dob`, `religion` and `status`, and `Guardian` casts only `is_primary`. The `DB_ENCRYPT` option in `config/database.php` is commented out.
- Display: the student profile (`resources/views/students/show.blade.php`) prints the full birth registration number, the full NID number and the guardian mobile to every role with `view-students`. There is no masking helper in the app or the views.
- Search: the student list search matches `name`, `name_bn`, `student_code` and `birth_reg_no` (`StudentController::index`). Typing part of a birth registration number finds the student.
- Logs: no activity log row holds an NID or BDRIS value. The rows hold amounts in poisha, ids, receipt numbers, notice audience and channel, and expense payees. Keep both values out of URLs and query strings. Routes use record ids only.

## Parent portal linking

The portal's access rule is the mobile match described in [Roles and permissions](roles-and-permissions.md#parent-linking). Security-relevant points:

- The link has no ownership check. KCAM does not send a code to the mobile number and does not check that the account holder controls it.
- A guardian record with the wrong mobile gives the wrong family access to that child's invoices, receipts, attendance summary, published marks and notices. Treat guardian mobile entry as a data-entry control.
- The portal is read-only. Parents cannot change records or pay through it.

## Duplicate payment protection

Source: `app/Domain/Fees/Actions/CollectFeeAction.php`, `CollectFeeRequest`, `resources/views/fees/index.blade.php`, migration `2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables`.

- The collect form includes a hidden `client_uuid` generated with `Str::uuid()` when the page renders. A new page load gets a new value. A double submit of the same form sends the same value.
- `CollectFeeRequest` accepts `client_uuid` as nullable and checks that it is a UUID.
- `CollectFeeAction::collect()` runs in a transaction and locks the invoice row (`lockForUpdate`). If a payment with the same `school_id` and `client_uuid` exists, it returns that payment's receipt and collects nothing more.
- The database enforces this with a unique key on `payments (school_id, client_uuid)`.
- Limitation: the lookup does not compare `invoice_id`. Reusing a `client_uuid` for a different invoice returns the first receipt without error and records nothing for the second invoice. Each new payment needs a new UUID.
- `AttendanceRecord` also has a `client_uuid` column that `RecordAttendanceAction` writes. This page does not cover its semantics. See [Attendance](attendance.md).

## Money integrity

- Money is whole poisha (1 taka = 100 poisha) held in `unsignedBigInteger` columns. The migration `2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables` multiplied existing values by 100 and changed these columns: `invoices` (`total`, `paid`, `due`), `invoice_items.amount`, `payments.amount`, `fee_structures.amount`, `fee_discounts.value`, `staff.salary_basic`, `sms_logs.cost`.
- Input: `CollectFeeRequest` accepts `^\d{1,9}(\.\d{1,2})?$`. `App\Support\Money::toPoisha()` converts it to an integer. No floats are used for stored amounts. `Money::toTaka()` formats output, and `Money::percentOf()` applies basis-point rates with half-up rounding.
- Overpayment is refused: `CollectFeeAction` throws when the amount is above the locked invoice `due`. Zero or negative amounts are also refused.
- Invoice totals are recalculated inside the same transaction. `due` is `max(0, total - newPaid)`, and status becomes `paid` or `partial`.
- Receipt numbers (`REC-YYYY-NNNNN`) come from a `receipt_sequences` row per school and year. The row is locked inside the payment transaction, so a rollback also rolls back the number and leaves no gaps. Receipts are never numbered by counting rows.
- Invoices are unique on `(school_id, student_id, academic_year_id, month)`.
- An audit row is written for each collection (`fee_collected`, with the amount in poisha, method, invoice id and receipt number). `expense_recorded`, `expense_voided`, `invoices_generated`, `notice_published` and `inventory_item_created` are written the same way. No screen reads these rows (see below).
- Refund and cancel: `PaymentStatus` has `refunded` and `cancelled` cases, but no action in the code read sets them. The `reverse-receipts` permission is seeded and unused.

## Logging and audit

- `spatie/laravel-activitylog` is installed. Code writes rows through the `activity()` helper in the actions and controllers listed above. No model uses the `LogsActivity` trait.
- No screen, API or command reads the activity log. The `view-audit-logs` permission is seeded and unused.
- Not logged: sign-ins and failed sign-ins, student record views, NID scan views, student edits, role changes.

## Secrets and configuration

- Secrets (`APP_KEY`, database and mail credentials) come from the environment through `env()` in `config/`. Keep real values only in the deployment `.env` or a secrets manager, and never in code, seeders, tests or docs. The demo seeder uses fixed demo passwords, which is why it is restricted to `local` and `testing`.
- Keep `APP_DEBUG=false` and `APP_ENV=production` in production. The defaults in `config/app.php` already match this, but check the deployed `.env`.
- Dependencies are pinned in `composer.lock`. Run `composer audit` and `npm audit` as part of release checks. This page did not run either.

## Reviewer checklist

- [ ] A new tenant model has a `school_id` column and uses `BelongsToSchool`. `TenantContextTest` passes.
- [ ] A new route sits inside its module group, has a `can:` middleware, and the matching FormRequest `authorize()` checks the same permission.
- [ ] Records are looked up by route model binding or by a school-scoped query. Any `withoutGlobalScope` or `runAsPlatform` is justified and runs inside `runAs`.
- [ ] Foreign keys in requests use `Rule::exists(...)->where('school_id', ...)`.
- [ ] No `{!! !!}` output of user-entered text.
- [ ] Uploads have `mimes` and `max` rules, are stored on the `local` disk and are served only through a permission-checked route.
- [ ] No NID, BDRIS or mobile values in logs, URLs, query strings or error messages. Routes use record ids.
- [ ] Money is stored as integer poisha. No float arithmetic on amounts. Any new payment-like action has an idempotency key.
- [ ] Tests cover 403 for a wrong role, 404 for another school's record, and the success path.
- [ ] Seeders, fixtures and docs use placeholders (`example@school.test`, `01XXXXXXXXX`). No real names, numbers or credentials.
- [ ] Any new view that shows NID, BDRIS or mobile values is needed for the role that can see it.

## Limitations and open questions

- Super admin on school routes: multi mode gives a server error, single mode with one school gives access to that school. Neither route-level case is tested. Decide the behaviour and add a test.
- NID scans and NID numbers are visible to teachers and accountants through `view-students`. Confirm this is intended. The same applies to the BDRIS number.
- Parents can list all notices at `/notices`, including notices for teachers (see [Notices](notices.md)).
- No email verification enforcement, no password reset, no two-factor authentication, and no in-app password change.
- The parent link rests on a mobile number that is never verified (see [Parent portal linking](#parent-portal-linking)).
- Login throttling limits each login and IP pair, and each IP to 20 attempts per minute. There is no per-account lockout and no alert.
- Session encryption is off by default (`SESSION_ENCRYPT`).
- NID and BDRIS values are stored and shown in plain text. No masking in views, no encryption at rest in the app.
- Viewing NID scans and student records is not logged.
- `script-src` allows `unsafe-inline` and `unsafe-eval`. Tracked in `docs/dev-plan.md` per the code comment.
- The `client_uuid` lookup is not bound to the invoice (see [Duplicate payment protection](#duplicate-payment-protection)).
- No refund or reversal action exists, so `refunded` and `cancelled` payment states cannot be reached from the app.
- Activity log rows are written but never read.
- `composer audit` and `npm audit` were not run for this document.
