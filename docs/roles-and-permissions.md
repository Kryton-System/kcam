# Roles and permissions

This page explains who can do what in KCAM and where that rule lives in code. Two layers work together. The `users.role` column (enum `UserRole`) decides the home page, the menu and the parent portal. Spatie permissions (`roles`, `permissions` and their pivot tables, guard `web`, spatie/laravel-permission 8.5.0 in `composer.lock`) decide every `can:` route middleware, every FormRequest `authorize()` check and the Blade `@can` blocks. Read [Security and privacy](security-and-privacy.md) for tenancy and data protection, and [Dashboards](dashboards.md) for what each role sees on the home page.

## Roles

Source: `app/Support/Enums/UserRole.php`. Labels come from `lang/en/enums.php` and `lang/bn/enums.php` under `enums.roles.*`.

| Case | Value | Label (en) | Label (bn) | Purpose |
|---|---|---|---|---|
| `UserRole::Owner` | `owner` | Owner | মালিক | Runs the school: students, fees, accounts, staff, payroll, inventory, transport, exams |
| `UserRole::Teacher` | `teacher` | Teacher | শিক্ষক | Attendance, marks entry, student list (read), notices, transport (read) |
| `UserRole::Accountant` | `accountant` | Accountant | হিসাবরক্ষক | Fees and payments, expenses, cash book, inventory, student list (read) |
| `UserRole::Parent` | `parent` | Parent | অভিভাবক | Read-only parent portal for linked children |
| `UserRole::SuperAdmin` | `super_admin` | Super Admin | সুপার অ্যাডমিন | Platform role. Seeded with permissions, but no screens exist and it has no school. See [Security and privacy](security-and-privacy.md) |

`UserRole::schoolRoles()` returns the four school roles (all except super admin).

## How a user gets a role

- `users.role` is a string column cast to `UserRole`. The migration `2026_11_21_154918_harden_users_table` sets its default to `parent`, so a user created without a role has the least privilege.
- `role` and `school_id` are not in the `User` model's fillable list. Code sets them with `forceFill()`, as `SetupSchoolAction` does.
- The `User` model's `saved` hook (`App\Models\User::booted()`) copies the role into Spatie: when a user is created or the `role` changes, it calls `syncRoles([$role])` if a Spatie role with that name exists. The roles must be seeded first.
- The owner account is created by `App\Domain\School\Actions\SetupSchoolAction`, in one of two ways:
  - Single mode (`KCAM_MODE` unset or `single`, the default in `config/kcam.php`): run `php artisan kcam:install`. The command refuses to run when a school already exists. It seeds `RoleAndPermissionSeeder` first, then creates the school and owner.
  - Multi mode: public registration at `GET /register` and `POST /register` (`RegisteredSchoolController`; the POST is throttled to 5 requests per minute). In single mode both routes return 404.
- Teacher, accountant and parent accounts have no creation screen. `routes/web.php` has no user management route. The only other code that creates users is `DemoSchoolSeeder`, which runs in the `local` and `testing` environments only.
- `Staff` has a `user_id` column and a `user()` relation, but no code writes it. Staff records are not linked to login accounts.
- Changing a user's role has no screen. It needs code or a database change, after which the `saved` hook syncs the Spatie role.

## Permission gates

No `Gate::define`, `Gate::policy` or policy class exists in `app/`, `bootstrap/`, `routes/` or `config/`. Every `can:` name is a Spatie permission name, and it is defined as a row in `database/seeders/RoleAndPermissionSeeder.php`. Spatie's `registerPermissions()` in `vendor/spatie/laravel-permission/src/PermissionRegistrar.php` adds a `Gate::before` callback that calls `checkPermissionTo()` for the user, and `permission.register_permission_check_method` (default true) turns that on. A permission name that is not in the database is treated as a denial (403), not an error. Super admin holds every permission except `view-portal` through its role, so the same check passes for it.

The seeder (`database/seeders/RoleAndPermissionSeeder.php`) defines 37 permissions. Seventeen are used by routes, requests or views, listed below.

| Permission | Routes (`routes/web.php`) | Also checked in |
|---|---|---|
| `view-students` | `GET /students`, `GET /students/{student}`, `GET /students/{student}/photo`, `GET /students/{student}/guardian-nid/{side}` | `IndexStudentsRequest` |
| `create-students` | `GET /students/create`, `POST /students` | `StoreStudentRequest`; dashboard admission link |
| `edit-students` | `GET /students/{student}/edit`, `PUT /students/{student}` | `UpdateStudentRequest`; `students/show.blade.php` edit button |
| `take-attendance` | `GET /attendance`, `POST /attendance` | `IndexAttendanceRequest`, `StoreAttendanceRequest`; dashboard quick links |
| `collect-fees` | `GET /fees`, `POST /fees/{invoice}/collect` | `IndexFeesRequest`, `CollectFeeRequest`; owner dashboard quick link |
| `view-exams` | `GET /exams` | none |
| `enter-marks` | `GET /exams/{exam}/marks`, `PUT /exams/{exam}/marks` | `UpdateMarksRequest` (also refuses when the exam is published); `exams/index.blade.php` |
| `view-notices` | `GET /notices` | none (the notices page uses `send-notices` for its compose form) |
| `send-notices` | `POST /notices` | `StoreNoticeRequest`; `notices/index.blade.php` `$canSend` |
| `view-portal` | `GET /portal`, `GET /portal/children/{student}` | `PortalController` (404 for a child not linked to the parent) |
| `view-accounts` | `GET /accounts`, `GET /accounts/expenses`, `GET /accounts/cash-book` | `AccountsPeriodRequest`; `accounts/index.blade.php` |
| `manage-expenses` | `POST /accounts/expenses`, `POST /accounts/expenses/{expense}/void`, `POST /accounts/categories` | `StoreExpenseRequest`, `VoidExpenseRequest`, `StoreExpenseCategoryRequest`; `accounts/expenses.blade.php` |
| `manage-inventory` | `GET /inventory`, `POST /inventory`, `GET /inventory/{item}`, `POST /inventory/{item}/movements` | `StoreInventoryItemRequest`, `StoreStockMovementRequest` |
| `manage-staff` | `GET/POST /staff`, `GET /staff/create`, `GET /staff/{staff}/edit`, `PUT /staff/{staff}` | `StoreStaffRequest` |
| `manage-payroll` | `GET /payroll`, `POST /payroll/generate`, `GET /payroll/{payslip}`, `PUT /payroll/{payslip}`, `POST /payroll/{payslip}/pay` | `IndexPayrollRequest`, `GeneratePayrollRequest`, `UpdatePayslipRequest` |
| `view-transport` | `GET /transport`, `GET /transport/routes/{transportRoute}`, `GET /transport/routes/{transportRoute}/print` | `transport/index.blade.php` and `transport/show.blade.php` use `manage-transport` for the manage buttons |
| `manage-transport` | route create, store, edit, update, assign, unassign; vehicle create, store, edit, update | `SaveRouteRequest`, `SaveVehicleRequest`, `AssignStudentRequest`; `TransportController::show` loads the candidate list only for managers |

The route groups also carry a module middleware (`module:students`, `module:fees` and so on). See [Module toggles](#module-toggles).

### Seeded but unused permissions

These 20 permissions exist in the seeder but no route, FormRequest, view or controller checks them. Treat them as reserved until a feature uses them.

`manage-school`, `manage-academic-years`, `manage-classes`, `manage-subjects`, `delete-students`, `export-students`, `import-students`, `view-attendance`, `edit-attendance`, `view-fees`, `manage-fee-structure`, `generate-invoices`, `reverse-receipts`, `view-financial-reports`, `manage-exams`, `publish-results`, `send-sms`, `view-sms-logs`, `view-audit-logs`, `manage-subscriptions`

## Role by capability

Yes means the role can use the feature. Read means view only. Own children means only the children linked to that parent (see [Parent linking](#parent-linking)).

| Capability | Owner | Teacher | Accountant | Parent | Permission |
|---|---|---|---|---|---|
| Student list, profile | Yes | Yes | Read | No | `view-students` |
| Student admission and edit | Yes | No | No | No | `create-students`, `edit-students` |
| Guardian NID numbers, NID scans, student photo | Yes | Yes | Yes | No | `view-students` (same gate as the profile) |
| Fee invoices and collecting payment | Yes | No | Yes | No | `collect-fees` |
| Payments and receipts (staff side) | Yes | No | Yes | No | `collect-fees` |
| Payments, invoices and receipts of own children | No | No | No | Own children | `view-portal` |
| Attendance marking | Yes | Yes | No | No | `take-attendance` |
| Attendance summary of own child | No | No | No | Own children | `view-portal` |
| Exam list | Yes | Yes | No | No | `view-exams` |
| Marks entry (blocked once an exam is published) | Yes | Yes | No | No | `enter-marks` |
| Published results of own children | No | No | No | Own children | `view-portal` |
| Expenses: list, cash book, monthly summary | Yes | No | Yes | No | `view-accounts` |
| Expenses: record, void, categories | Yes | No | Yes | No | `manage-expenses` |
| Payroll and payslips | Yes | No | No | No | `manage-payroll` |
| Staff records | Yes | No | No | No | `manage-staff` |
| Transport routes and vehicles: view and print | Yes | Yes | No | No | `view-transport` |
| Transport routes and vehicles: create, edit, assign | Yes | No | No | No | `manage-transport` |
| Inventory items and stock movements | Yes | No | Yes | No | `manage-inventory` |
| Notices: read | Yes | Yes | Yes | Yes (all notices, see [Limitations](#limitations-and-open-questions)) | `view-notices` |
| Notices: send | Yes | Yes | No | No | `send-notices` |
| Parent portal | No | No | No | Yes | `view-portal` |

Super admin is not in the table. The seeder gives it every permission except `view-portal` (`RoleAndPermissionSeeder`, `Permission::where('name', '!=', 'view-portal')`). It has no school, so school routes need a school context that it does not have. See [Security and privacy](security-and-privacy.md#school-isolation).

The seeded role lists also grant parents `view-attendance` and `view-fees`. No route checks either name, so parents reach that data only through `view-portal`.

## Module toggles

Source: `config/kcam.php`, key `modules`. Each module is read from an environment variable and defaults to on. Set the variable in the deployment `.env`, not in code.

| Module key | Environment variable | Routes hidden when off | Menu and dashboard entries hidden when off |
|---|---|---|---|
| `students` | `KCAM_MODULE_STUDENTS` | `/students/*` | Students menu, teacher student list link |
| `attendance` | `KCAM_MODULE_ATTENDANCE` | `/attendance/*` | Attendance menu, dashboard quick links |
| `fees` | `KCAM_MODULE_FEES` | `/fees/*` | Fees menu, dashboard quick links |
| `exams` | `KCAM_MODULE_EXAMS` | `/exams/*` | Exams menu, teacher dashboard quick link |
| `notices` | `KCAM_MODULE_NOTICES` | `/notices/*` | Notices menu, quick links |
| `portal` | `KCAM_MODULE_PORTAL` | `/portal/*` | Parent portal menu. Parent dashboard returns 403 (see below) |
| `accounts` | `KCAM_MODULE_ACCOUNTS` | `/accounts/*` | Accounts menu, owner and accountant dashboard links |
| `inventory` | `KCAM_MODULE_INVENTORY` | `/inventory/*` | Inventory menu |
| `payroll` | `KCAM_MODULE_PAYROLL` | `/staff/*` and `/payroll/*` | Staff and payroll menus |
| `transport` | `KCAM_MODULE_TRANSPORT` | `/transport/*` | Transport menu |

How it works:

- `App\Http\Middleware\EnsureModuleEnabled` is registered as the `module` alias in `bootstrap/app.php`. It calls `abort_unless(config("kcam.modules.{$module}") === true, 404)`. Each route group uses it, and it runs before the `can:` middleware on the routes inside the group.
- The menu in `resources/views/components/app-layout.blade.php` wraps each entry in `config('kcam.modules.*')`.
- A module switch changes visibility only. It does not change permissions.
- Parent with the portal module off: `DashboardController` returns 403 with the message from `dashboard.flash.portal_off` ("The parent portal is turned off for this school.").

Known gap: the owner dashboard admission link (`resources/views/dashboard.blade.php`, the `students.create` entry) checks `create-students` only, not `kcam.modules.students`. With the students module off, the link still shows and leads to a 404.

## Parent linking

Source: `app/Http/Controllers/PortalController.php`. The class docblock states the rule: a parent is linked to children through the guardian mobile number matching the user's mobile, within the same school.

```php
private function linkedChildren(User $user): Builder
{
    $mobile = (string) $user->mobile;

    return Student::query()->whereHas('guardians', fn (Builder $q) => $q->where('mobile', $mobile))
        ->when($mobile === '', fn (Builder $q) => $q->whereRaw('1 = 0'));
}
```

Rules that follow from the code:

- Any guardian row of the student counts. `is_primary` is not checked, so a second guardian with the same mobile also links the child.
- A user with an empty mobile links to nothing.
- `Student` and `Guardian` both use `BelongsToSchool`, so the match never crosses schools.
- `GET /portal` lists the linked children. `GET /portal/children/{student}` returns 404 when the child is not linked, which covers children of other parents and of other schools alike.
- The child page shows: attendance counts for the current enrollment, the latest 12 invoices, total due, the latest 10 receipts, marks from published exams only, and sent notices for `all`, `parents` or the child's class (latest 10). The sibling switcher uses the same linked list.

Data formats that make the match work:

- `users.mobile` is unique across the whole install (migration `2026_11_21_154918_harden_users_table`), so one mobile number maps to one login.
- Guardian mobiles must match `/^01[3-9]\d{8}$/` (`StoreStudentRequest` and `UpdateStudentRequest`). Login lookups and school registration canonicalise mobile input with `NormalizesInputHelper::canonicalMobile()`, which accepts Bangla digits, spaces, dashes and a `+88` prefix.

Consequence: the app does not check that the account holder controls the mobile number. Whoever the school enters as the guardian mobile, and whoever holds a login with that number, gets read access to that child. Set parent mobiles with care. See [Security and privacy](security-and-privacy.md#parent-portal-linking).

## Adding a permission safely

1. Add the name to the `$permissions` array in `database/seeders/RoleAndPermissionSeeder.php`. The seeder uses `firstOrCreate`, so re-running it is safe.
2. Add the name to each role's `syncPermissions([...])` list that should have it. `syncPermissions` replaces that role's permissions with the list, so a permission added by hand in the database is removed on the next seed run. Super admin receives every permission except `view-portal` automatically.
3. Protect the route inside its module group with `->middleware('can:your-permission')`. For writes, return the same check from the FormRequest `authorize()` method, using `$this->user()?->can('your-permission') ?? false`. Use the same name in Blade `@can` blocks.
4. Do not gate by role string (`$role === 'owner'`) in new code. The menu and `DashboardController` use role strings today, so they do not follow the seeder. If a menu entry should appear for the new permission, update `resources/views/components/app-layout.blade.php` by hand.
5. Add a row to the `route permissions` dataset in `tests/Feature/AuthorizationTest.php` listing the roles that must get 200 and the roles that must get 403. Add a feature test for the success path.
6. Reseed and run the tests:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder --no-interaction
php artisan permission:cache-reset
vendor/bin/pest --filter=AuthorizationTest
```

A typo in a permission name denies every user, including super admin, with 403. The authorization matrix catches this. See [Testing](testing.md).

## Limitations and open questions

- Notices: `NoticeController::index()` returns every notice in the school to any role with `view-notices`. The parent role has that permission (`AuthorizationTest` expects 200 for parents), so parents can see notices aimed at `teachers` and at other classes. The portal filters notices for parents, but `/notices` does not. Confirm intent.
- NID and birth registration data: teachers and accountants can see guardian NID numbers, NID scans and the BDRIS birth registration number, because those pages use `view-students` with no further check. Confirm intent.
- Super admin has permissions but no screens, and school routes fail for it (see [Security and privacy](security-and-privacy.md#school-isolation)).
- No user management: the app cannot create teacher, accountant or parent accounts or change a role. `Staff.user_id` is never written.
- No exam publishing: no route or action sets `exams.published_at`, so published results and the marks lock only work when the value is set outside the app. This also leaves `publish-results` unused.
- Invoice generation has no route or console command. `GenerateMonthlyInvoicesAction` is called only from tests.
- Menu visibility uses role strings in `app-layout.blade.php`, so changing a role's permissions does not change the menu.
- Twenty seeded permissions are not checked anywhere (listed above).
- Role and permission changes are not audited. The app has no screen for them, and the seeder writes no activity log rows.
