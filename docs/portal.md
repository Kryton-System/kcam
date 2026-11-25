# Parent portal

The parent portal is a read-only view for parents. A parent signs in with a user account whose mobile number matches a guardian on one or more student records, and sees only those children. This page covers who can use the portal, how children are linked, the routes, what each page shows, the locale and mobile behaviour, and the known gaps. Admission and guardian data are described in [Students](students.md). Code lives in `app/Http/Controllers/PortalController.php`, `resources/views/portal` and `lang/*/portal.php`.

## Who can use it

- Role `parent` (`UserRole::Parent`). In the permission seeder only this role has `view-portal`. Owner, teacher, accountant and super admin receive 403 on `/portal`.
- Sign-in: the login form accepts an email address or a mobile number. Mobile lookup uses the canonical `01XXXXXXXXX` form (`AuthenticatedSessionController`).
- After sign-in, `DashboardController` sends a parent to `portal.index` while the portal module is on. When it is off, the dashboard returns 403 with a message and shows no school totals.
- Account creation: nothing in `app/` creates a parent user. The only parent account in the repository is created by `database/seeders/DemoSchoolSeeder.php`, which is demo data. Real parent accounts must be created outside the application today (see Limitations).

## How children are linked

`PortalController::linkedChildren()` returns every student that has a guardian row whose `mobile` equals the signed-in user's `mobile`.

What follows from that rule:

- The match is an exact string comparison, and the portal normalises neither side. Guardian mobiles are saved in canonical form by the admission and edit forms. The user's `mobile` must also be canonical (`01` followed by nine digits, no `+88`) to match.
- Any guardian row counts, not only the primary guardian.
- One number links every student that has a guardian with that number. Siblings who share a guardian number share one parent account and see all of them.
- A user with an empty mobile number sees no children.
- Only the user's school is searched, through the school scope on `Student`.
- A child who is not linked gets 404 on `/portal/children/{student}`. So does a student from another school, and a missing id. The response does not say which case applies.

## Routes

| Method | Path | Name | Permission | Action |
|---|---|---|---|---|
| GET | `/portal` | `portal.index` | `view-portal` | `PortalController@index`, list of children |
| GET | `/portal/children/{student}` | `portal.children.show` | `view-portal` | `PortalController@show`, child page |

Both routes sit behind `auth` and `module:portal`. The module flag is `kcam.modules.portal` in `config/kcam.php`, set by `KCAM_MODULE_PORTAL` and on by default. Both routes are read only.

## Children list

`portal.index` lists every linked child, ordered by name. Each card shows:

- the Bangla name when one is stored, otherwise the English name
- the English name and the student code
- the class and roll number of the active enrollment

With no linked children the page shows: "No children found. Log in with the mobile number registered at school."

## Child page

`portal.children.show` renders these sections in order:

1. **Header**: name (Bangla when stored), English name and student code, and a link back to the list.
2. **Child switcher**: appears only when the parent has two or more linked children. It lists all of them, ordered by name. The current child is highlighted and marked with `aria-current="page"`.
3. **Profile**: class, section, roll number, date of birth (d/m/Y) and blood group. Class, section and roll come from the active enrollment, so they show "-" for a child with no active enrollment.
4. **Attendance**: counts for present, absent, late and excused, plus a total. The counts cover every attendance record of the active enrollment, across all of its dates.
5. **Dues and fees**: total due, which is the sum of `due` over all of the child's invoices in any year and any status. Below it, the 12 most recent invoices ordered by the `month` text, descending, each showing month, raw status, and paid over total.
6. **Receipts**: the 10 most recent receipts for payments on the child's invoices, showing the serial number and the payment amount. No date or payment method is shown.
7. **Results**: marks for the active enrollment, limited to exams with `published_at` set. Grouped by exam. Each row shows subject, marks, grade and grade point.
8. **Notices**: the 10 most recent sent notices (`sent_at` set) whose audience is `all` or `parents`, or `class` with the target class equal to the child's active class. Each shows the title and body, with no date.

Amounts are stored as whole poisha and shown as taka with two decimals through `LocaleFormat::taka()`.

The portal does not show a photo, guardian contact details, fee structure or fee heads, payment method, previous academic years, or section-audience notices.

## Locale behaviour

- The chosen language is stored in the session by `GET /language/{en|bn}` (route name `locale.switch`). `SetLocale` applies it on every request and falls back to `APP_LOCALE`, which is `en` in `.env.example`. Guests can switch language as well.
- Labels come from `lang/en/portal.php` and `lang/bn/portal.php`, which have 23 keys each with the same key set. The student labels in `lang/bn/students.php` also match the English file, 94 keys each.
- Bangla digits appear in Bangla mode for attendance counts and totals, and for taka amounts.
- Not localised: roll numbers, dates (d/m/Y), receipt serials, invoice months, grades and grade points. Invoice status shows the raw value (`unpaid`, `partial`, `paid` or `cancelled`) in every language.
- Names do not follow the language. The Bangla name and the Bangla class name are shown whenever they exist, in both languages. Notices likewise show `title_bn` and `body_bn` when present. The staff profile page (`students.show`) differs: it chooses the header name by locale.

## Mobile layout

- The Portal item appears in the navigation menu for parents, and in the bottom navigation bar on phones (`app-layout` component).
- Children list: one column on phones, two columns from the `sm` breakpoint.
- Child switcher: wrapping buttons, each at least 44 px tall.
- Attendance summary: two columns on phones, five from `sm`.
- Profile: one column on phones, two from `sm`.
- The portal uses cards and rows, not tables, so the `data-stack` table rule does not apply.
- The child page is one column, capped at `max-w-3xl`.

These notes come from the Blade templates and class names. The layout was not tested on a device.

## Related

[Students](students.md), [Roles and permissions](roles-and-permissions.md), [Security and privacy](security-and-privacy.md), [Localization](localization.md), [Attendance](attendance.md), [Fees and payments](fees-and-payments.md), [Exams](exams.md), [Notices](notices.md), [Testing](testing.md).

## Limitations

- **No account management**: no screen or action creates, resets or links parent accounts. Mobile numbers must match exactly, and there is no UI to correct them.
- **Fragile linking**: the exact string match means a mistyped guardian number silently hides a child, and the parent has no way to report it. Any guardian row links a child, so a number on a non-primary guardian also grants access.
- **Current year only**: attendance, results, class notices and the profile follow the active enrollment. A child with no active enrollment shows an empty results list and no class.
- **Notices**: audience `section` notices never appear. The notice form stores a class as the target for section notices, but the portal matches only `all`, `parents` and `class`. Teacher-only notices are correctly hidden.
- **Receipts and payments**: no date or payment method is shown. The application has no refund or reversal code (nothing writes payment status `refunded` or `cancelled`), so receipts cannot yet reflect reversals. The `reverse-receipts` permission has no route.
- **Invoice totals**: total due includes every invoice whatever its status. The invoice `month` is free text and is sorted as text, which is correct only for `YYYY-MM` values; the seeder uses that format. `GenerateMonthlyInvoicesAction` is called only from a test (verified by search), so the production source of invoices is not confirmed.
- **Fixed limits without paging**: 12 invoices, 10 receipts and 10 notices. Attendance and results have no limit. Results have no explicit ordering of exams or subjects.
- **Mixed localisation**: see Locale behaviour.
- **Stale comment**: the fees route block in `routes/web.php` says parents get their own portal routes later. The portal already exists.

Open questions:

- Should parents see previous academic years, and notices addressed to a section?
- How should parent accounts be created and linked: an admin screen, an import, or an invitation?
