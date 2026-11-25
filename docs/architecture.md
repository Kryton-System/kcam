# Architecture

KCAM is one Laravel application that renders Blade pages on the server. Business logic lives in modules under `app/Domain`. Controllers stay thin: a Form Request validates and normalizes the input, an Action does the work inside a database transaction, and the controller returns a view or a redirect. Tenant isolation is enforced in the models, not in each controller. For the table design, read [Database](database.md). For setup, read [Getting started](getting-started.md). For the reasons behind the main choices, read the [decision records](decisions/).

## Stack

| Layer | Tool | Installed version or setting |
|---|---|---|
| Framework | Laravel | `laravel/framework` v13.43.0 (`composer.lock`) |
| Language | PHP | `^8.3` in `composer.json`, 8.4 is the target |
| Views | Blade components, Livewire scripts | `livewire/livewire` v3.8.11. Layouts load `@livewireStyles` and `@livewireScripts`. No Livewire component class exists under `app/`. |
| Front end | Alpine.js | Bundled inside Livewire's script. Not listed in `package.json`. |
| Styling | Tailwind CSS 4 | `tailwindcss` and `@tailwindcss/vite` `^4.0.0` |
| Build | Vite 8 with `laravel-vite-plugin` | `vite` `^8.0.0`, plugin `^3.1`. Entry points `resources/css/app.css` and `resources/js/app.js`. |
| Roles and permissions | spatie/laravel-permission | 8.5.0 |
| Activity log | spatie/laravel-activitylog | 5.1.5, used only through the `activity()` helper |
| Tests | Pest | v4.7.8 |
| Style and analysis | Laravel Pint, Larastan | Pint `^1.27`. Larastan is installed, but the repository has no `phpstan.neon`. |

## Directory layout

```text
app/
  Console/Commands/InstallKcam.php      kcam:install, the only custom Artisan command
  Domain/<Module>/                      business logic per module (see the next section)
  Http/Controllers/                     one controller per screen group, plus Auth/
  Http/Middleware/                      EnsureModuleEnabled, SecurityHeaders, SetLocale
  Http/Requests/                        Form Requests (23 files), Concerns/NormalizesInput.php
  Models/User.php                       the login user (not tenant scoped by trait)
  Providers/AppServiceProvider.php      lazy loading guard, password rule, factory names
  Support/Enums/                        8 enums with label()
  Support/Helpers/                      BanglaHelper, LocaleFormat, NormalizesInputHelper, Search
  Support/Tenancy/                      CurrentSchool, MissingSchoolContext
  Support/Traits/BelongsToSchool.php    the school scope trait
  Support/Money.php                     integer poisha helpers
bootstrap/app.php                       routing, middleware aliases and order, exception rendering
config/kcam.php                         KCAM_MODE and module switches
database/migrations, factories, seeders
lang/en, lang/bn                        translations (enums.php holds enum labels)
resources/views/                        components/, partials/, one folder per module, dashboards
resources/js/app.js, resources/css/app.css
routes/web.php                          every web route
routes/console.php                      only the default inspire command
tests/Feature, tests/Unit               Pest tests
public/                                 index.php, robots.txt, favicon.ico, sw.js, manifest.json, fonts/, build/ after a build
```

`resources/views/layouts/` exists but is empty. The shared layouts are `components/app-layout.blade.php` and `components/guest-layout.blade.php`.

## Domain modules

Each module holds `Models/` and `Actions/` folders, and some Actions folders are empty. Eight modules also have a `Policies/` folder, and all of them are empty (see [Limitations](#limitations)).

| Module (`app/Domain/`) | Models | Actions | Notes |
|---|---|---|---|
| `School` | `School`, `AcademicYear`, `SchoolClass`, `Section`, `Subject` | `SetupSchoolAction` | Creates the school, owner, year, classes, sections and fee heads in one transaction. |
| `Student` | `Student`, `Guardian`, `Enrollment` | `AdmitStudentAction` | `Student` uses soft deletes. |
| `Attendance` | `AttendanceRecord` | `RecordAttendanceAction` | Accepts a `client_uuid` from the client. |
| `Exams` | `Exam`, `Mark` | `GradeMarks` | |
| `Fees` | `FeeHead`, `FeeStructure`, `FeeDiscount`, `Invoice`, `InvoiceItem` | `GenerateMonthlyInvoicesAction`, `CollectFeeAction` | `Invoice` uses soft deletes. |
| `Payments` | `Payment`, `Receipt` | none | Receipts are numbered in `CollectFeeAction`. No gateway driver exists. |
| `Notices` | `Notice`, `SmsLog` | none | Notices are stored. SMS is not sent. |
| `Accounts` | `Expense`, `ExpenseCategory` | `RecordExpense`, `VoidExpense`, `BuildAccountsSummary` | |
| `Inventory` | `InventoryItem`, `StockMovement` | `RecordStockMovementAction` | Throws `InsufficientStockException`. |
| `Payroll` | `Payslip` | `GeneratePayslipsAction` | `Payslip` uses soft deletes. |
| `Staff` | `Staff` | none | `Staff` uses soft deletes. |
| `Transport` | `TransportVehicle`, `TransportRoute`, `TransportAssignment` | `AssignStudentToRoute` | `TransportException` sits in the module root. |
| `AI`, `Reports` | none | none | Empty folders. |

## Request lifecycle

1. `public/index.php` boots the app from `bootstrap/app.php`. That file registers `routes/web.php`, `routes/console.php` and the `/up` health route.
2. Every web request runs the framework web group (cookie encryption, session start, error sharing, CSRF check, implicit route model binding). KCAM appends `SecurityHeaders` and then `SetLocale` (`bootstrap/app.php`).
3. `SecurityHeaders` sets `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy` and `Permissions-Policy`, plus a Content-Security-Policy. In `local` the CSP is report-only. `Strict-Transport-Security` is sent only when the request is secure.
4. `SetLocale` reads the `locale` session key, which `LocaleController` sets through `/language/{locale}` (`en` or `bn`). Without a choice it uses `config('app.locale')`.
5. Route middleware runs in this order: `auth` (`Authenticate`), then `module:<name>` (`EnsureModuleEnabled`, which returns 404 when the module is off), then `can:<permission>` (`Authorize`). Login allows 20 POSTs per minute and register allows 5 per minute.
6. The controller receives a Form Request. `NormalizesInput` converts Bangla digits, cleans text and canonicalizes mobile numbers before the rules run.
7. The controller calls an Action. Writes run in `DB::transaction`. Models that use `BelongsToSchool` add the school condition to every query and fill `school_id` on create.
8. The controller returns a Blade view, or a redirect with a `success` flash message. Domain exceptions are mapped to form errors in the controller. For example, `FeeController::collect` turns an `InvalidArgumentException` into a validation error on `amount`.
9. Exceptions render as JSON when the path starts with `api/` or the client expects JSON (`bootstrap/app.php`).

## Route groups and gates

The full route list is in `routes/web.php`. Each module group uses a URL prefix, a route name prefix and a module switch. Each route then adds one `can:` check.

| URL prefix | Name prefix | Module switch | Permissions per route |
|---|---|---|---|
| `/students` | `students.` | `students` | `view-students` (index, show, photo, guardian-nid), `create-students` (create, store), `edit-students` (edit, update) |
| `/attendance` | `attendance.` | `attendance` | `take-attendance` (index, store) |
| `/fees` | `fees.` | `fees` | `collect-fees` (index, collect) |
| `/exams` | `exams.` | `exams` | `view-exams` (index), `enter-marks` (marks.edit, marks.update) |
| `/notices` | `notices.` | `notices` | `view-notices` (index), `send-notices` (store) |
| `/portal` | `portal.` | `portal` | `view-portal` (index, children.show) |
| `/accounts` | `accounts.` | `accounts` | `view-accounts` (index, expenses, cash-book), `manage-expenses` (expense store and void, category store) |
| `/inventory` | `inventory.` | `inventory` | `manage-inventory` (all routes) |
| `/staff` | `staff.` | `payroll` | `manage-staff` (all routes) |
| `/payroll` | `payroll.` | `payroll` | `manage-payroll` (all routes) |
| `/transport` | `transport.` | `transport` | `view-transport` (index, route show and print), `manage-transport` (route and vehicle create, store, edit, update, assign, unassign) |

Routes outside a module group: `/` (`home`, redirects signed-in users to `dashboard`), `/dashboard` (`dashboard`, login only, no permission), `/health` (`health`, JSON status), `/language/{locale}` (`locale.switch`), `/offline` (`offline`), `/login`, `/register` (guest only), `/logout` (`logout`). `/up` is the framework health route that `bootstrap/app.php` registers.

Authorization uses spatie/laravel-permission. `PermissionRegistrar::registerPermissions()` adds a `Gate::before` callback that asks the user `checkPermissionTo($ability)`, so `can:view-students` checks the permission through the user's role. The application defines no `Gate::define` calls and no policy classes. Role permissions are set in `database/seeders/RoleAndPermissionSeeder.php`. The `super_admin` role receives every permission except `view-portal`. See [Roles and permissions](roles-and-permissions.md).

## School tenancy

- **Mode.** `KCAM_MODE` sets `config('kcam.mode')`. `single` (default) serves one school per install. `multi` serves many schools. See [decision 0003](decisions/0003-single-school-install-mode.md).
- **Which school applies.** `CurrentSchool::id()` checks, in order: the school set by `runAs()`, then the logged in user's `school_id`, then (single mode only) the only `schools` row. It returns null when there are zero or two rows.
- **Explicit scope.** `CurrentSchool::runAs($schoolId, Closure)` sets the school for a unit of work (jobs, commands, webhooks). `CurrentSchool::runAsPlatform(Closure)` is the explicit opt-in for cross-school work, used by `DemoSchoolSeeder`. Both restore the previous context in a `finally` block.
- **Scope.** `BelongsToSchool` adds a global scope named `school`. With no school and no platform flag, the scope throws `MissingSchoolContext`, so the query fails closed. On create, the trait fills `school_id` when it is empty.
- **Coverage.** 28 models use the trait. `School` is the only domain model without it, because it is the root. `App\Models\User` has a `school_id` column but no trait. Login looks users up across schools by email or mobile on purpose, since both columns are unique across the install.
- **Escape hatches.** `withoutGlobalScope('school')` appears in `CollectFeeAction`, `GenerateMonthlyInvoicesAction`, `AdmitStudentAction` and `RecordAttendanceAction`. Each filters by a `school_id` it has already resolved (`CollectFeeAction` takes it from the locked invoice). Review every new use.
- **Registration.** `RegisteredSchoolController` returns 404 in single mode, so the public sign-up form exists only in multi mode.

## Module switches

- `config/kcam.php` defines `modules`, one boolean per module, read from `KCAM_MODULE_<NAME>` with a default of true.
- `EnsureModuleEnabled` returns 404 when `config("kcam.modules.{$module}")` is not `true`.
- Menus and dashboard cards check the same keys: `components/app-layout.blade.php`, `dashboard.blade.php`, `dashboard-teacher.blade.php` and `dashboard-accountant.blade.php`.
- `/staff` and `/payroll` both use the `payroll` switch. There is no separate staff switch.
- The switches are config values, not database rows. After changing `.env` on a host with cached config, run `php artisan config:cache` again.

## Conventions

- **Actions.** One class per business operation in `app/Domain/<Module>/Actions`. Most expose `execute()`. `AssignStudentToRoute` exposes `handle()`, `GradeMarks` exposes `for()`, and `BuildAccountsSummary` exposes `forMonth()` and `forRange()`. Controllers call them with dependency injection, for example `CollectFeeAction $collectAction` in `FeeController::collect`.
- **Form Requests.** Live in `app/Http/Requests`. Input normalization uses the `NormalizesInput` trait (`normalizeInput(textFields, digitFields, mobileFields)`). `NormalizesInputHelper` offers the same functions outside requests.
- **Enums.** `app/Support/Enums` holds `UserRole`, `AttendanceStatus`, `FeeHeadType`, `PaymentMethod`, `PaymentStatus`, `Religion`, `StockMovementType` and `StudentStatus`. Each has `label()`, which reads `lang/<locale>/enums.php`.
- **Money.** `App\Support\Money` converts and calculates whole poisha. See [decision 0001](decisions/0001-money-as-integer-poisha.md).
- **Search.** `App\Support\Helpers\Search::contains()` escapes LIKE wildcards before it adds conditions.
- **Formatting.** `LocaleFormat` (digits, numbers, taka, dates, month and day names, mobile numbers) and `BanglaHelper` (Bangla digits, taka, dates, ordinals).
- **Components.** `resources/views/components` holds the shared Blade components, for example `app-layout`, `guest-layout`, `card`, `button`, `input`, `select`, `table`, `modal`, `confirm-modal`, `toast`, `stat-card` and `language-switcher`. See [Design system](design-system.md).
- **Factories.** `AppServiceProvider` resolves `App\Domain\...\Models\X` to `Database\Factories\XFactory`, so all 30 factories sit in `database/factories`.
- **Service provider rules.** Lazy loading is blocked outside production. The password rule requires 8 characters, and in production it also rejects breached passwords (`AppServiceProvider::boot`).
- **Localization.** Enum labels, flash messages and UI text use `__()` keys in `lang/en` and `lang/bn`. See [Localization](localization.md).

## Limitations

- The super admin dashboard is not built. `DashboardController` has no `super_admin` branch, so a super admin falls into the default (owner) view.
- There is no recent-activity feed. `activity()` writes to `activity_log` in six places (`CollectFeeAction`, `GenerateMonthlyInvoicesAction`, `RecordExpense`, `VoidExpense`, `InventoryController`, `NoticeController`), but no screen reads the table, and no model uses the `LogsActivity` trait.
- The inventory stock error is English only: `InsufficientStockException::for()` returns "Only {n} in stock, cannot remove {m}.".
- 20 of the 37 permissions seeded in `RoleAndPermissionSeeder` are not used by any route, view or action. Examples: `view-attendance`, `view-fees`, `generate-invoices`, `manage-school`, `view-sms-logs`. Assigning them has no effect yet.
- Empty folders: `app/Domain/AI`, `app/Domain/Reports`, every `Policies` folder, `Notices/Actions`, `Payments/Actions`, `Payments/Drivers`, `Staff/Actions` and `resources/views/layouts`. Git does not track empty folders, so a clone will not have them.
- SMS is not sent. `sms_logs` is never written. `NoticeController::store` saves the notice row, sets `sent_at` and logs an activity entry, with no gateway call.
- No payment gateway is connected. `payments.gateway_txn_id` exists, but no driver code runs.
- `receipts.pdf_path` is never set. No PDF generator exists.
- The school setting `settings.timezone` (`Asia/Dhaka`, written by `SetupSchoolAction`) is not read by any code. The application timezone is `UTC` (`config/app.php`).
- Action entry points are not uniform. Nine Actions expose `execute()`, `AssignStudentToRoute` exposes `handle()`, `GradeMarks` exposes `for()`, and `BuildAccountsSummary` exposes `forMonth()` and `forRange()`. Check the class before calling it.
