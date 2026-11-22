# KCAM Development Plan | School Management SaaS (PHP + Laravel)

KCAM = Kindergarten Cloud Academic Management. Companion to `idea.md`. This document describes how to build the product with PHP and Laravel, sprint by sprint. Where the tech stack here differs from `idea.md` (which still mentions React or Next.js), this document wins.

## 1. Tech Stack

| Layer | Choice | Why |
|---|---|---|
| Language / framework | PHP 8.4, Laravel 13 (as installed) | Fast to build, huge ecosystem, cheap hosting in Bangladesh |
| UI | Blade + Alpine.js + Tailwind CSS 4; plain controllers for simple pages, **Livewire 3** only for interactive screens (attendance grid, fee collection, marks entry) | One codebase, no separate SPA; Livewire only where it earns its weight |
| PWA | Service worker + manifest (Vite PWA plugin or hand-written) | Installable on phones, offline attendance |
| Database | **MySQL 8 in every environment** (local, CI, staging, production). No SQLite, not even for tests | Relational data, strong transactions for fees. SQLite ignores row locks, so fee and receipt concurrency cannot be tested on it |
| Cache / queue | Redis (database queue is fine at the very start) | SMS, PDFs, payment webhooks run in the background |
| Auth | Laravel Fortify or Breeze + OTP login for parents | Simple, secure, extendable |
| Roles | `spatie/laravel-permission` is the **only** source of roles. No `role` column on `users` | Owner, teacher, accountant, parent; one source of truth |
| Audit log | `spatie/laravel-activitylog` | Who changed marks, fees, receipts |
| Excel import/export | `maatwebsite/excel` | Student bulk import, reports |
| PDF | `spatie/laravel-pdf` (Chromium based) or Browsershot | **Bangla text shaping is broken in DomPDF**; Chromium renders Bangla correctly |
| Tests | Pest + Laravel Dusk or Playwright | TDD and E2E |
| Quality | Laravel Pint, Larastan (PHPStan level 8) | Style and static analysis in CI |
| API (phase 2) | Laravel Sanctum + API Resources | Parent mobile app later |

## 2. Architecture Decisions

### 2.1 Multi-tenancy: single database, `school_id` on every tenant table

- Chosen for simplicity and low hosting cost (hundreds of small schools do not need a database each).
- Use a `BelongsToSchool` trait with a **global scope** that always adds `where school_id = current school`, and auto-fills `school_id` on create.
- The current school lives in an explicit `CurrentSchool` context object, not in `Auth::user()` directly:
  - Web requests: middleware sets it from the logged-in user (and optionally from a subdomain such as `greenvalley.kcam.app`, domain to be confirmed).
  - Queue jobs: every tenant job carries `school_id` and a job middleware sets the context before `handle()`.
  - Scheduled commands and webhooks: loop over schools or resolve the school from the payload, and set the context for each unit of work.
- **Fail closed.** If a tenant model is queried or created with no current school set, the scope throws. It never silently returns all rows. Cross-school work (super admin, platform reports) must call an explicit, greppable escape hatch such as `CurrentSchool::runAsPlatform(fn)`, which is audit logged.
- Every table that holds school data has a `school_id` column, including child tables such as `invoice_items`.
- Tests (mandatory, run in CI):
  - An architecture test proving every model whose table has `school_id` uses `BelongsToSchool`.
  - A leak test that runs over **all** tenant models (dataset driven), not a hand-picked few.
  - A test that a query with no current school throws.
- Alternative later: `stancl/tenancy` if you need per-school databases.

### 2.2 Modular monolith

Keep one Laravel app, but organise by feature domain, not by technical layer:

```
app/
  Domain/
    School/        (profile, academic year, classes, sections, subjects)
    Student/       (admission, guardians, promotion, TC)
    Staff/
    Attendance/
    Fees/          (heads, structures, invoices, payments, receipts)
    Payments/      (gateway drivers, webhooks, reconciliation)
    Exams/         (terms, marks, grading, report cards)
    Notices/       (SMS, in-app, templates)
    Reports/
  Support/         (BelongsToSchool, helpers, Bangla number formatter)
```

Each domain holds its Models, Actions (single-purpose classes), DTOs, Policies, Livewire components, and tests.

### 2.3 Patterns to follow

- **Actions** for business logic (`CollectFeeAction`, `PromoteStudentsAction`); controllers and Livewire components stay thin.
- **Form Requests / Livewire validation** at every boundary; fail fast with clear messages.
- **Policies** for every model; never trust the UI to hide buttons.
- **Money as integer poisha** (`unsignedBigInteger` columns, `int` in PHP). No `float` and no `decimal` anywhere in money code; percentage discounts round once, with a documented rule (round half up to the nearest poisha). Format to taka only at the view layer.
- **Immutable receipts:** a receipt is never edited; corrections create a reversal entry.
- **Gapless receipt serials:** a `receipt_sequences(school_id, year, next_no)` row is locked with `SELECT ... FOR UPDATE` inside the payment transaction and incremented. Never derive the next serial by counting receipts.
- **Idempotent payments:** unique `(gateway, gateway_txn_id)` for online payments and unique `(school_id, client_uuid)` for cash collection (protects against double submit); wrap in DB transactions.
- **Soft deletes** on students, staff, invoices; no hard deletes of money data. Foreign keys on `invoices`, `invoice_items`, `payments`, `payment_allocations` and `receipts` use `restrictOnDelete`, never `cascadeOnDelete`.
- **Database-level uniqueness** for anything that must not duplicate (invoices per student and period, attendance per enrollment and date). An "exists" check in PHP is not enough under concurrency; write with `upsert` or catch the unique violation.
- **Queues** for SMS, PDFs, emails, webhooks.
- **Enums** (PHP 8.1) for status fields.
- **Localization:** all text in `lang/bn` and `lang/en`; Bangla digits helper; Noto Sans Bengali font.

## 3. Database Design (core tables)

```
schools(id, name, name_bn, logo, eiin, phone, address, plan_id, status, ...)
users(id, school_id, name, mobile, email, password, ...)   roles via spatie only
academic_years(id, school_id, name, starts_on, ends_on, is_current)
classes(id, school_id, name, name_bn, order)
sections(id, school_id, class_id, name)
subjects(id, school_id, class_id, name, name_bn, full_marks, pass_marks)
students(id, school_id, student_code, name, name_bn, dob, gender, birth_reg_no, photo, blood_group, status)
guardians(id, school_id, user_id, name, name_bn, mobile, nid, email)
guardian_student(id, school_id, guardian_id, student_id, relation, is_primary)
enrollments(id, school_id, student_id, academic_year_id, class_id, section_id, roll_no, status)
staff(id, school_id, user_id, designation, joined_on, salary_basic, ...)
holidays(id, school_id, date, name)
attendance_sessions(id, school_id, section_id, date, taken_by, client_uuid, submitted_at)
attendance_records(id, school_id, attendance_session_id, enrollment_id, date, status, marked_by, synced_at)
fee_heads(id, school_id, name, type[monthly|one_time|yearly])
fee_structures(id, school_id, academic_year_id, class_id, fee_head_id, amount)
fee_discounts(id, school_id, student_id, fee_head_id, type, value, reason)
late_fee_rules(id, school_id, fee_head_id, grace_days, amount)
invoices(id, school_id, student_id, academic_year_id, type[monthly|one_time|yearly], period, total, paid, due, status, due_date)
invoice_items(id, school_id, invoice_id, fee_head_id, amount)
payments(id, school_id, student_id, method[cash|bkash|nagad|rocket|card], amount, gateway, gateway_txn_id, client_uuid, status, paid_at, collected_by)
payment_allocations(id, school_id, payment_id, invoice_id, amount)
receipts(id, school_id, payment_id, serial_no, pdf_path)
receipt_sequences(school_id, year, next_no)
exams(id, school_id, academic_year_id, name, starts_on, ends_on, published_at)
grading_scales(id, school_id, min_marks, max_marks, grade, gp)
marks(id, school_id, exam_id, enrollment_id, subject_id, marks, grade, gp)
notices(id, school_id, title, body, audience, channel, sent_at)
sms_logs(id, school_id, to, body, provider_ref, status, cost)
sms_credit_ledger(id, school_id, change, balance_after, reason, sms_log_id)
otp_codes(id, mobile, code_hash, expires_at, attempts, consumed_at)
plans(id, name, max_students, price)
subscriptions(id, school_id, plan_id, status, trial_ends_at, renews_at)
audit logs via spatie activitylog table
```

Notes on the schema:

- All money columns (`amount`, `total`, `paid`, `due`, `price`, `cost`, `salary_basic`) are integer poisha.
- **Guardians are people, not per-student rows.** One guardian links to many students through `guardian_student`, so siblings share a parent and the parent portal can list all linked children from one mobile number.
- **One payment can settle several invoices** (parents often pay several months at once) through `payment_allocations`. One payment produces one receipt. `sum(allocations.amount) = payment.amount` is enforced in the Action and checked by the nightly reconciliation.
- **`attendance_sessions`** records that a teacher took attendance for a section on a date. Without it "not taken" looks the same as "everyone present". `holidays` stops absence SMS and reports from counting closed days.
- **`invoices.period`** is always `YYYY-MM` for monthly invoices, `YYYY` for yearly, and null for one-time. No free-text month names.
- `otp_codes` stores only a hash of the code.

Indexes and constraints: `(school_id, ...)` composite on all tenant tables; unique `(school_id, student_code)`, `(school_id, serial_no)`, `(enrollment_id, date)`, `(school_id, section_id, date)` on attendance sessions, `(school_id, client_uuid)` on attendance sessions and payments, `(gateway, gateway_txn_id)` on payments, `(school_id, student_id, academic_year_id, type, period)` on invoices, `(school_id, mobile)` on guardians, `(guardian_id, student_id)` on the pivot.

## 4. Key Feature Implementation Notes

| Feature | Approach |
|---|---|
| Student bulk import | Excel template, validation preview, queued import, error report download |
| Attendance | Livewire grid; tap to toggle; one submission per section and date creates an `attendance_session` plus its records in one transaction (`upsert`). PWA stores the submission in IndexedDB when offline and POSTs it to a sync endpoint with a client UUID (idempotent). Conflict rule: the latest `submitted_at` wins per section and date, and the overwritten values go to the audit log. Queue SMS for absentees only after the session is submitted, never on holidays |
| Fee generation | Scheduled command (`fees:generate-monthly`) creates invoices per student from fee structure and discounts; safe to re-run because of the unique invoice key. One-time and yearly heads (admission, session, exam fee) are invoiced on admission and at year start by their own Actions. A student admitted mid-month is charged from the admission month, full month (no proration in the MVP) |
| Late fee | Daily scheduled job applies `late_fee_rules` once per invoice as an extra invoice item |
| Fee collection (cash) | Accountant screen: search student, see all dues, tick the months being paid, collect once, one receipt PDF, print or share. Online only in the MVP (no offline cash collection): receipt serials and double-collection checks need the server |
| Online payment | Gateway driver interface (`PaymentGateway`) with SSLCommerz / aamarPay / bKash implementations; payment link by SMS; verified webhook marks payment; reconcile job nightly |
| Results | Grading scale config per school; GPA calculation in an Action; report card via Blade + Chromium PDF in Bangla |
| SMS | Laravel Notification custom channel for the chosen provider; per school SMS credit balance; retry with backoff; delivery status log |
| Parent access | Login by mobile and OTP; read-only views for all linked children, dues, attendance, results. Each OTP costs an SMS, so keep parents signed in for 90 days on a trusted device, and let payment and receipt SMS carry signed, expiring links that open without a login |
| Reports | Query classes with cached aggregates; export Excel and PDF |
| Promotion | Action creates next-year enrollments in a transaction, carries dues, and can be previewed first |

## 5. Security Checklist (enforced from day one)

- Global tenant scope plus policy checks plus a cross-tenant leak test.
- CSRF, XSS (Blade escaping), mass-assignment protection (`$fillable`), SQL via Eloquent / bindings only.
- Rate limit login and OTP; lock after repeated failures.
- Secrets only in `.env` or a secrets manager; `.env` never committed.
- Verify payment webhooks by signature; never trust redirect callbacks alone.
- Never store card data.
- File uploads: validate MIME and size, store outside public root, serve via signed URLs.
- Production: `APP_DEBUG=false`, HTTPS only, secure cookies, no stack traces to clients.
- Encrypted nightly DB backups plus off-server copy; restore test monthly.
- Privacy: student data is sensitive; restrict export permission to owners.

### 5.1 Authorization

- Every route sits behind a Policy or a permission middleware. "Logged in" is never enough: a parent or teacher account must get a 403 on fee collection, notices, admission and exports.
- A permissions matrix (role by action) is written per module at Gate A and tested with a dataset: each role against each route.
- Any id that arrives from the client (class, section, student, invoice, enrollment) is checked to belong to the current school. Validation uses `Rule::exists('classes', 'id')->where('school_id', $currentSchoolId)`, never a bare `exists:classes,id`. Related ids are checked against each other too (the section must belong to the chosen class).
- Use `withoutGlobalScope('school')` when a tenant bypass is really needed, never `withoutGlobalScopes()`, which also switches off soft deletes.
- `school_id` and role fields are never mass assignable from request data. Remove `role` and `school_id` from the `User` fillable list; set them explicitly in Actions.

### 5.2 Input handling: validate on the way in, escape on the way out

The rule is **validate and normalise input, escape output for its context**. Do not strip or rewrite HTML out of names and notices on input; store what the user typed and escape it wherever it is shown.

Validation (one Form Request per write endpoint, one per filtered list):

- Every field has a type, a maximum length that matches the column, and a closed list where one exists (`Rule::enum(...)` for gender, relation, status, method, audience).
- Query-string filters (`search`, `class_id`, `date`, `status`) are validated as well. `search` is a string of at most 100 characters; an array there must return 422, not a 500.
- Mobile numbers match a Bangladesh pattern (`01[3-9]` plus 8 digits, optional `+88`) and are stored in one canonical form. A wrong number means an SMS to a stranger and wasted credit.
- Dates are bounded: date of birth before today, attendance date not in the future and inside the current academic year.
- Money input is whole poisha, minimum 1, maximum the invoice due; reject more than 2 decimal places.
- Bulk payloads (attendance grid, Excel rows) have a size limit, integer keys, and every row validated with the same rules as the single form.
- Text that becomes an SMS has a length cap in SMS parts, shown to the user before sending.

Normalisation (in `prepareForValidation`, before the rules run):

- Trim (Laravel default) and convert Bangla digits to ASCII digits in numeric, date, money and mobile fields, so `০১৭...` and `৫০০` are accepted.
- Unicode NFC normalisation for Bangla names so search and duplicate checks work.
- Remove control characters. Keep ZWJ and ZWNJ, which Bangla conjuncts need.

Output escaping by context:

| Context | Rule |
|---|---|
| HTML body and attributes | Blade `{{ }}` only. `{!! !!}` is banned by a CI grep unless the line has a reviewed allow comment |
| JavaScript, including Alpine attributes (`x-data`, `x-init`, `@click`) | `@js($value)` or `Js::from()`. Never `'{{ $value }}'` inside script or an Alpine expression: the browser decodes HTML entities before the script runs, so a quote in the value breaks out |
| SQL | Eloquent or bindings only. In `LIKE` searches escape `%`, `_` and `\` in the user's term |
| Excel and CSV exports | Prefix cells that start with `=`, `+`, `-`, `@`, tab or carriage return with a single quote, to stop formula injection from a student name |
| PDF templates (Chromium) | Same Blade escaping; render with JavaScript and network access off, so injected markup cannot fetch local files or internal URLs |
| SMS | Plain text, length capped, no user-supplied links |
| Logs | Never log passwords, OTPs, tokens or full guardian NIDs |
| Uploaded photos | Check the real MIME type, re-encode the image on the server (drops EXIF and hidden payloads), random file name, private disk |

### 5.3 Authentication and sessions

- Login, registration, OTP request and OTP verify are all rate limited (per identifier and per IP). The same generic error is returned whether the account exists or not, and a password hash is always checked so response time does not reveal which.
- `users.mobile` is unique (it is a login identifier).
- New school signups verify the owner's mobile or email before the account is active; `email_verified_at` is never set without proof.
- `Password::defaults()` is configured: at least 8 characters, and checked against known breached passwords in production.
- Demo accounts and one-click demo login exist only when `APP_ENV=local`. The demo seeder refuses to run elsewhere. Staging is behind HTTP basic auth or an IP allow list.
- Session cookie is `Secure`, `HttpOnly`, `SameSite=Lax` in production; session id is regenerated on login and invalidated on logout.

### 5.4 Browser and platform hardening

- A middleware (`App\Http\Middleware\SecurityHeaders`) sets security headers on every response: Content-Security-Policy, `Strict-Transport-Security` (HTTPS only), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`. **Interim state:** the CSP still allows inline script and `eval` because Livewire and the standard Alpine build need them, and it is report-only in the local environment. The target is nonces plus the Alpine CSP build, then removing `unsafe-inline` and `unsafe-eval`.
- The service worker caches only versioned build assets and the offline page. It never caches authenticated HTML or JSON, and all caches plus the IndexedDB queue are cleared on logout (phones are shared).
- Domain exceptions (for example "amount exceeds due") are caught and shown as validation messages. Users never see a stack trace, and the health endpoint does not report the environment name.
- Every money, marks, role and export action writes to the activity log (who, what, old and new values).
- Generated codes (student code, receipt serial) come from locked counter rows, never from counting rows.

## 6. Testing Strategy (TDD, 80%+ coverage)

1. **Unit (Pest):** fee calculation, late fee, discount, GPA, Bangla number helper, promotion logic.
2. **Feature / integration:** every controller and Livewire component, policies, tenant isolation, webhook handling, queue jobs (using fakes for SMS and gateways).
3. **E2E (Playwright or Dusk):** admission, attendance, cash collection, online payment (sandbox), result publish, parent OTP login.
4. **Static analysis:** Larastan level 8 and Pint in CI; fail the build on errors.
5. **Coverage gate:** `pest --coverage --min=80` in CI.
6. Process per feature: write failing test, implement minimal code, refactor.

## 7. Environments and DevOps

- **Local:** Laravel Sail or Herd / Docker, seeders with realistic Bangla demo school.
- **Git:** GitHub, trunk based with short feature branches, Conventional Commits, PRs under review with CI green.
- **CI (GitHub Actions):** install, Pint, Larastan, Pest with coverage, build assets, run Playwright on main.
- **Staging:** separate server and database, sandbox payment keys, deployed on every merge to main.
- **Production:** VPS (Bangladesh or Singapore region), Nginx, PHP-FPM with OPcache, MySQL/PostgreSQL, Redis, Supervisor for queue workers, cron for `schedule:run`. Use Laravel Forge or Ploi for easier deploys, with zero-downtime deployment.
- **Monitoring:** Sentry (errors), uptime monitor, Laravel Telescope in staging only, log rotation.
- **Backups:** daily database dump (`spatie/laravel-backup`) to S3-compatible storage.

## 8. Sprint Plan (2 week sprints)

| Sprint | Weeks | Deliverables |
|---|---|---|
| 0 | 1 to 2 | Repo, CI, Laravel + Livewire + Tailwind skeleton, Bangla font and lang files, auth, roles, `BelongsToSchool` trait, tenant leak test, seeders, deploy to staging. **Start the paperwork now:** payment gateway merchant account and SMS provider account with sender ID registration (approval usually takes weeks, and both are needed long before Sprint 9) |
| 1 | 3 to 4 | School setup wizard (profile, academic year, classes, sections, subjects), user and staff management |
| 2 | 5 to 6 | Students and guardians: admission form, photo upload, list and search, Excel import, student ID |
| 3 | 7 to 8 | Attendance (online first), absence SMS via provider, attendance reports. Two day spike: prove the offline queue and sync endpoint on one real low-end phone, so Sprint 7 holds no surprises |
| 4 | 9 to 10 | Fee heads, structures, discounts, monthly invoice generation, dues screen |
| 5 | 11 to 12 | Cash collection, receipts (Bangla PDF), daily collection report, defaulter list |
| 6 | 13 to 14 | Owner dashboard, notices (SMS and in-app), SMS credit and log |
| 7 | 15 to 16 | Performance pass, onboarding flow, **pilot release to 3 to 5 schools (online only)**; PWA offline attendance sync built behind a feature flag |
| 8 | 17 to 18 | Pilot fixes, offline attendance switched on for pilot schools once it passes the offline gate, help videos, audit log views |
| 9 | 19 to 20 | Online payment (gateway driver, webhook, reconciliation), payment links by SMS |
| 10 | 21 to 22 | Parent portal (OTP login, dues, pay, attendance), receipt by SMS |
| 11 | 23 to 24 | Exams, marks entry, grading, report card PDF, result publishing |
| 12 | 25 to 26 | Subscription billing for schools, plans and trial, admin super panel, hardening, **public launch** |
| 13+ | 27 onward | Phase 2: diary and homework, payroll, expenses and accounts, ID cards, parent app (Sanctum API), pickup safety, inventory, multi-branch |

Each sprint ends with a demo to one real school owner and a feedback note.

The pilot does not wait for offline sync. Offline sync is the hardest feature in the MVP, and shipping it in the same sprint as the first pilot puts the pilot date at risk. The plan has no slack built in: if a sprint slips, cut scope from that sprint rather than moving the pilot.

## 9. Super Admin (your own back office)

- Manage schools, plans, trial and subscription status, suspend or reactivate.
- SMS credit top-up and usage per school.
- Payment and webhook logs for debugging.
- Impersonate a school user (with audit log) for support.
- Platform metrics: active schools, students, collection volume, churn.
- Use Filament (admin panel for Laravel) to build this fast.

## 10. Definition of Done (per feature)

- Tests written first and passing, coverage at least 80% for the module.
- Policy and tenant isolation covered.
- Bangla and English strings, mobile layout checked at 360 px width.
- Pint and Larastan clean; no N+1 queries (use `preventLazyLoading` in non-production).
- Audit log on sensitive actions.
- Deployed to staging and verified on a real low-end Android phone.
- Short note added to docs and changelog.

## 11. Risks Specific to the Laravel Build

| Risk | Mitigation |
|---|---|
| Bangla PDF text breaks | Use Chromium based PDF generation from day one; test early with real report cards |
| Tenant data leak | Global scope, policies, dedicated leak tests, code review checklist |
| Payment webhook duplicates or missed callbacks | Idempotency keys, signature check, retry-safe handlers, nightly reconciliation |
| Slow pages on low-end phones | Livewire pagination, lazy loading, small assets, caching, image resizing |
| Queue failures lose SMS | Failed job table, retries with backoff, alert on failures |
| Single server failure | Daily offsite backup, documented restore, move to managed DB when revenue allows |
| Scope creep | Only MVP modules until the pilot proves value |
| Tenant scope skipped outside web requests | Scope fails closed; jobs, commands and webhooks set `CurrentSchool` explicitly (section 2.1) |
| Concurrency bugs hidden by the test database | MySQL in local and CI; tests for double collect, duplicate invoice run, and parallel receipt serials |
| Gateway or SMS approval arrives late | Applications started in Sprint 0 |

## 12. First Week Checklist

1. Create the repo (`git init`, push to GitHub) and the Laravel 13 app; install Livewire, Tailwind, Pest, Pint, Larastan, spatie permission and activitylog.
2. Set up GitHub Actions with Pint, Larastan, and Pest coverage gate.
3. Add `lang/bn`, Noto Sans Bengali font, and a Bangla digit helper.
4. Build `BelongsToSchool` trait and global scope, with the cross-tenant test first.
5. Build register school and login, role seeding, and a base layout that works on mobile.
6. Deploy the empty app to staging with HTTPS.
7. Write the Sprint 1 user stories and acceptance criteria.

## 13. Catch-up Work (existing code vs this plan)

Code for parts of Sprints 0 to 5 already exists but was written before the decisions above were fixed. Close these gaps before building new features, in this order:

1. Put the project under git and add the CI workflow (Pint, Larastan, Pest with coverage, `composer audit`).
2. Switch local and test databases from SQLite to MySQL.
3. Replace the `Auth::user()` based tenant scope with the `CurrentSchool` context that fails closed; add the architecture test and the all-model leak test.
4. Convert money columns and the fee Actions from `decimal` and `float` to integer poisha.
5. Add `receipt_sequences`, and change money foreign keys from cascade to restrict.
6. Reshape guardians into `guardians` plus `guardian_student`; add `payment_allocations`, `attendance_sessions`, and the unique keys listed in section 3; add `school_id` to `invoice_items`.
7. Remove the `role` column from `users` and use spatie roles only.
8. Add a Policy for every model and a Form Request for every write endpoint and every filtered list, following section 5.1 and 5.2. Today any logged-in user, including a parent, can collect fees, admit students and post notices.
9. Scope every `exists:` rule to the current school (`class_id`, `section_id`, `target_class_id` currently accept another school's ids).
10. Fix the toast component: replace `'{{ session('success') }}'` inside `x-init` with `@js(...)`.
11. Tighten existing validation: enums for gender, relation and method; mobile pattern; bounded dates; maximum lengths; validated `search` and filter inputs with escaped `LIKE` terms; Bangla digit normalisation.
12. Replace `withoutGlobalScopes()` (8 uses) with `withoutGlobalScope('school')` or the `CurrentSchool` context.
13. Auth hardening: throttle registration, unique `users.mobile`, constant-time login failure, no auto-verified email, `role` and `school_id` out of the `User` fillable list.
14. Restrict demo login buttons and the demo seeder to `APP_ENV=local`.
15. Limit the service worker cache to build assets; clear caches on logout. Add the security headers middleware.
16. Catch domain exceptions in the fee controller; drop `environment` from the health response; add activity log entries to fee collection and invoice generation.
17. Add the missing views for attendance, fees, exams and notices (their routes exist but the Blade files do not).

All of this is cheap now because there is no production data. Each item is done test first, like any other feature.

**Status (security pass):** items 3, 8, 9, 10, 11, 12, 13 (except the verification flow itself), 14, 15 and 16 are done, with tests in `tests/Feature/` (`AuthorizationTest`, `TenantContextTest`, `InputValidationTest`, `SecurityHardeningTest`). Item 7 is partly done: `role` and `school_id` are no longer mass assignable, the default role is least privileged, and the `role` column drives the spatie role through a model hook, but the column itself still exists. Item 17 has simple first versions of the four views. Items 4 and 5 are also done (money pass): every money column is integer poisha (`App\Support\Money` parses and formats, percentage discounts are basis points), receipt serials come from the locked `receipt_sequences` table, money foreign keys are `restrictOnDelete`, `invoice_items` has `school_id`, invoices are unique per student, year and month, and fee collection is idempotent through `payments.client_uuid`. Tests: `MoneyIntegrityTest`. Item 6 is only partly done (the unique keys and `invoice_items.school_id`); the guardian pivot, `payment_allocations` and `attendance_sessions` are still open. Item 2 is done: the suite and the migrations now run on MySQL 8 (local container `kcam-mysql` on 127.0.0.1:3317, databases `kcam` and `kcam_test`; `phpunit.xml` forces `kcam_test`). Row-lock behaviour under truly parallel requests is still untested, because Pest runs in one process. Still open: 1 (git and CI), email or mobile verification for new owners, the CSP nonce work in 5.4, and Larastan configuration (there is no `phpstan.neon` yet).
