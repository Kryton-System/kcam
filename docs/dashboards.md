# Dashboards

The home page (`GET /dashboard`, route name `dashboard`) shows each role the figures its job needs. Every figure is computed from database records on each request. Nothing is a fixed or cached value, and every query runs under the school scope (see [Security and privacy](security-and-privacy.md#school-isolation)). This page lists what each dashboard shows, the helper that computes it, the time window it uses, what the role cannot see, and the quick actions. Role rules are in [Roles and permissions](roles-and-permissions.md).

## How the page is chosen

Source: `app/Http/Controllers/DashboardController.php`, `__invoke()`.

| Role | Result | View | Figures method |
|---|---|---|---|
| `accountant` | Accountant dashboard | `resources/views/dashboard-accountant.blade.php` | `accountantFigures()` |
| `teacher` | Teacher dashboard | `resources/views/dashboard-teacher.blade.php` | `teacherFigures($academicYear)` |
| `owner` | Owner dashboard | `resources/views/dashboard.blade.php` | `ownerFigures($academicYear)` |
| `super_admin` | Falls through to the owner dashboard (no dedicated page) | `dashboard.blade.php` | `ownerFigures($academicYear)` |
| `parent` | Redirect to `portal.index` when `kcam.modules.portal` is on. Otherwise 403 with `dashboard.flash.portal_off` | none | none |

Every role except parent also receives the school and the current academic year. The academic year comes from `School::currentAcademicYear()`, which is the row with `is_current = true`. When no academic year is current, the class and exam figures are empty.

Super admin: no dashboard is built. The `default` branch of the `match` sends super admin to the owner view. In multi mode that view's first scoped count throws `MissingSchoolContext`, because the super admin has no school. In single mode it uses the install's only school. See [Limitations](#limitations-and-open-questions).

## Time windows

| Figure | Window | Used by |
|---|---|---|
| Attendance today, classes today | `whereDate('date', now()->toDateString())` | owner, teacher |
| Collected today, payments today | `now()->startOfDay()` to `endOfDay()` on `paid_at` | accountant |
| Collected this month | `now()->startOfMonth()` to `endOfMonth()` on `paid_at` | owner, accountant |
| Expenses this month | Month range on `spent_on` (date strings) | owner, accountant |
| Upcoming exams | `starts_on` on or after today, current academic year when one exists, first 3 by `starts_on` | owner, teacher |
| Outstanding dues | No window. All invoices with `due > 0` in every year | owner, accountant |
| Recent lists | Latest by `paid_at`, `created_at`, `spent_on` or `id` (see each table) | all |

Timezone: `config/app.php` sets `timezone` to `UTC`. `SetupSchoolAction` stores `'timezone' => 'Asia/Dhaka'` in school settings, but no code reads that value. "Today" and "this month" therefore follow UTC. For Bangladesh (UTC+6), payments taken from 00:00 to 05:59 local time count toward the previous day. See [Limitations](#limitations-and-open-questions).

## Owner dashboard

Source: `DashboardController::ownerFigures()`, view `dashboard.blade.php`.

| Card | Computed by | Rule |
|---|---|---|
| Total students | `Student::count()` | All non-deleted students in the school (soft-deleted rows are excluded by `SoftDeletes`). |
| Active students | `Student::where('status', active)->count()` | The note shows inactive as total minus active, which also counts transferred, graduated and dropped students. |
| Attendance today | `attendanceToday()` | Present out of marked. The rate is present divided by marked (all statuses, including late and excused), rounded to 1 decimal. Shows "not marked" when nothing is recorded today. |
| Collected this month | `collectedBetween()` | Sum of `payments.amount` (poisha) where status is completed and `paid_at` is in the month. Shown as taka. |
| Outstanding dues | `outstandingDues()` | Sum of `invoices.due` where `due > 0`. All years. |
| Expenses this month | `expensesBetween()` | Sum of `expenses.amount` for active expenses (`voided_at` null) with `spent_on` in the month. Links to Accounts when the module is on. |
| Staff | `Staff` counts | Active staff out of all staff. No pay figures. |
| Recent transactions | `recentPayments(5)` | Latest 5 completed payments: student name, receipt number, amount, method, date. |
| Upcoming exams | `upcomingExams()` | Up to 3, as above. |
| Recent notices | `Notice::latest('created_at')` | Latest 3. |

The owner sees the whole school: students, money, staff counts, notices. It shows no payroll amounts.

## Accountant dashboard

Source: `DashboardController::accountantFigures()`, view `dashboard-accountant.blade.php`.

| Card | Computed by | Rule |
|---|---|---|
| Collected today | `collectedBetween()` for today | Completed payments with `paid_at` today. The note shows the count from `paymentsCountBetween()`. |
| Collected this month | `collectedBetween()` for the month | As above. |
| Outstanding dues | `outstandingDues()` | As owner. |
| Expenses this month | `expensesBetween()` | As owner. |
| Recent payments | `recentPayments(8)` | Latest 8 completed payments: student name, amount, date. |
| Recent receipts | `Receipt::with('payment.invoice.student')->latest('id')` | Latest 8 receipts: receipt number, student name, amount. |
| Recent expenses | `Expense::active()->with('category')->latest('spent_on')` | Latest 5 active expenses: date, category, payee, amount. |

The accountant does not see attendance, exams, notices or staff. Student names appear in payment and receipt rows, but there are no student totals.

## Teacher dashboard

Source: `DashboardController::teacherFigures()`, view `dashboard-teacher.blade.php`.

| Card | Computed by | Rule |
|---|---|---|
| Active students | `Student::where('status', active)->count()` | School-wide. Not limited to any class. |
| Present today | `attendanceToday()` | Count of present records today. The note shows the present rate when anything is marked. |
| Absent today | `attendanceToday()` | Count of absent records today. |
| Pending attendance | Computed in the view: `max(0, activeStudents - marked)` | School-wide estimate. It can be off when records exist for students who are not active. Shows "all marked" when zero. |
| Classes and today's attendance | `classRows()` | One row per class in order. Students are active enrollments in the current academic year. Present, absent and marked come from today's records. Status "done" when marked is at least the enrolled count, otherwise "pending". |
| Upcoming exams | `upcomingExams()` | Up to 3, as above. |
| Recent notices | `Notice::latest('created_at')` | Latest 3. |
| Recent students | `Student::with('currentEnrollment.schoolClass')->latest('id')` | Latest 5 students added: name, class, roll number. |

`classRows()` covers every class in the school. The code has no teacher-to-class assignment, so the card title "Classes and today's attendance" does not mean "my classes". The card is empty when no academic year is current, and the empty message then reads "No classes set up yet" even when classes exist.

The teacher sees no money. `teacherFigures()` has no collected, dues, expenses or payroll values, and a test checks that (see [Tests](#tests)).

## Parent

A parent is sent to `/portal`. The portal shows only the parent's linked children (see [Roles and permissions](roles-and-permissions.md#parent-linking) and [Security and privacy](security-and-privacy.md#parent-portal-linking)). With the portal module off, the parent gets 403 and the message "The parent portal is turned off for this school." School-wide finance totals never reach a parent.

## Quick actions

Quick action buttons sit above the figures. Each has a condition in its view.

| Role | Button | Condition in view | Route enforces |
|---|---|---|---|
| Owner | Attendance | `modules.attendance` and `can('take-attendance')` | `module:attendance`, `can:take-attendance` |
| Owner | Fees | `modules.fees` and `can('collect-fees')` | `module:fees`, `can:collect-fees` |
| Owner | Admission | `can('create-students')` only | `module:students`, `can:create-students` |
| Owner | Notices | `modules.notices` | `module:notices`, `can:view-notices` |
| Teacher | Take attendance | `modules.attendance` and `can('take-attendance')` | same |
| Teacher | Enter marks | `modules.exams` and `can('enter-marks')` | `module:exams`, `can:enter-marks` |
| Teacher | Student list | `modules.students` | `module:students`, `can:view-students` |
| Teacher | Notices | `modules.notices` | `module:notices`, `can:view-notices` |
| Accountant | Fees | `modules.fees` | `module:fees`, `can:collect-fees` |
| Accountant | Open accounts | `modules.accounts` | `module:accounts`, `can:view-accounts` |

Known gap: the owner's Admission button checks `create-students` but not `modules.students`. With the students module off, the button still shows and leads to a 404. The accountant's two buttons check only the module, not the permission. The routes still enforce the permission, and the accountant holds both permissions, so nothing is exposed. See [Roles and permissions](roles-and-permissions.md#module-toggles).

## Empty states

| Role | Figure or card | Empty state |
|---|---|---|
| Owner | Recent transactions | Shown when there are no completed payments |
| Owner | Upcoming exams | Shown when no future exam exists |
| Owner | Recent notices | Shown when no notice exists |
| Owner | Numeric cards (students, money, staff) | Show zero. No empty state |
| Accountant | Recent payments, recent receipts, recent expenses | Shown when each list is empty |
| Teacher | Classes card | Shown when `classRows` is empty (no current academic year, or no classes) |
| Teacher | Upcoming exams, recent students, recent notices | Shown when each list is empty |

## What each role sees on the dashboard

Yes means the figure appears on that role's dashboard. No means it does not. The parent has no dashboard figures, only the redirect to the portal.

| Information | Owner | Teacher | Accountant | Parent |
|---|---|---|---|---|
| Collected and outstanding dues (school-wide) | Yes | No | Yes | No |
| Expenses | Yes | No | Yes | No |
| Staff counts | Yes | No | No | No |
| Attendance today (school-wide) | Yes | Yes | No | No (own children in the portal) |
| Attendance by class | No | Yes (all classes) | No | No |
| Recent notices | Yes | Yes | No | No (notices reach the portal, see [Notices](notices.md)) |
| Exam dates | Yes | Yes | No | No |
| Student names | Yes (payment rows, not a list) | Yes (recent students) | Yes (payment and receipt rows) | Own children in the portal |
| NID or BDRIS values | No | No | No | No |

## Tests

`tests/Feature/DashboardRolesTest.php` covers the role views:

- Owner dashboard shows students, collections, dues, expenses and staff with Bangla digits.
- Owner dashboard uses English digits and dates in English.
- Accountant dashboard shows collections, dues, expenses and receipt history.
- Teacher dashboard shows attendance and upcoming exams but no fee or expense totals.
- Dashboard shows helpful empty states for a school with no activity yet.
- Teacher sees a class overview without a school-wide finance card.

Parent redirect and portal-off behaviour are covered in `tests/Feature/ParentPortalTest.php`. Run `vendor/bin/pest --filter=DashboardRolesTest`.

## Adding a figure

1. Pick the role. Add the figure to that role's method (`ownerFigures`, `accountantFigures` or `teacherFigures`), not to a shared array, so the role boundary stays visible.
2. Write a private helper for any query used by more than one card (the existing helpers are `collectedBetween`, `expensesBetween`, `outstandingDues`, `attendanceToday` and `recentPayments`). Money comes back as an integer in poisha. Format it in the view with `LocaleFormat::taka()`.
3. Choose and document the time window. Use the school timezone once the app supports it (see [Limitations](#limitations-and-open-questions)).
4. Add the card to the view with the translation key (see [Localization](localization.md)).
5. Add a test in `DashboardRolesTest` that checks the value and that other roles do not see it.

## Limitations and open questions

- Timezone: "today" and "this month" use UTC. The school's `Asia/Dhaka` setting is stored but not applied. Payments between midnight and 06:00 local time land on the previous day.
- Super admin: no dashboard. The owner view runs for super admin, which fails in multi mode and shows one school's data in single mode. Decide the behaviour and add a test.
- Outstanding dues cover all academic years, including old unpaid invoices. There is no year filter.
- Teacher "pending attendance" is a school-wide estimate. Teacher class rows cover all classes, not assigned ones, since no assignment exists.
- Attendance rate counts late and excused records in the denominator, so the rate is lower than present over present-plus-absent.
- Recent transactions and collected totals do not reflect reversals or refunds. No code sets `refunded` or `cancelled` (see [Fees and payments](fees-and-payments.md)).
- The classes card reads "No classes set up yet" when the cause is a missing current academic year.
- The owner's Admission button ignores the students module switch.
- Figures are not cached, and no load test has been run. Several count queries run on each owner or teacher page view.
