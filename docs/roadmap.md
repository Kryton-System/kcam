# KCAM roadmap

Status as of 2026-11-24. Branch `main` holds: students (list, admit, profile, edit), attendance, fees and collection, exams (list and marks entry), notices, parent portal, accounts and expenses, inventory, staff and payroll, transport. 368 tests pass on MySQL.

Product model: sold as a package, one install per school, configured per customer. Every item below is judged against two questions: does it help a school run a normal day, and does it make KCAM easier to sell and install.

## What the review found

1. All 20 pages checked load for the owner. `/exams/{exam}/marks` returned 404 only because the demo data has no exam.
2. The new pages (portal, accounts, payroll, transport, inventory) have had no visual pass. Tests prove behavior, not layout.
3. Language debt grows with every page. Only the new pages show Bangla and English together. Retrofitting later costs more, so the language foundation moves to the front.
4. Core workflows are incomplete: exams cannot be created, students cannot be promoted or deactivated, and fee heads and structures have no setup screen. A school cannot run a full year today.
5. Selling readiness is missing: no backup, no update path, no deploy guide, no per-school branding, no CI.
6. Parents have no real login path. The portal links a parent by mobile number but there is no OTP or first-login flow.
7. Security items still open from `docs/dev-plan.md` section 13: owner verification, CSP nonce, Larastan config.

## Order of work and why

Phase 0 first because it is cheap and de-risks everything. Phase 1 next because new pages written before the language foundation must be rewritten. Workflows (phase 2) before polish features (phase 3). Release readiness (phase 4) before the first customer install.

## Phase 0: verify and stabilize (about 1 day)

| # | Item | Done when |
|---|---|---|
| 0.1 | Visual pass on every page, desktop and 375px phone | No overflow, no unreadable table, print pages print on one A4 sheet |
| 0.2 | Demo seeder safe to re-run, with data for every module (an exam, a route, stock, a payslip, an expense) | `db:seed` twice in a row succeeds and every page shows data |
| 0.3 | Browser smoke test with Playwright for login, admit a student, collect a fee, enter marks | One command runs it green |
| 0.4 | Cleanup: empty `app/Models/Domain` and `database/factories/Domain`, MySQL container on a normal volume | Container survives a reboot |

## Phase 1: language foundation (about 2 days)

| # | Item | Done when |
|---|---|---|
| 1.1 | Move labels into `lang/bn` and `lang/en`, use `__()` in every view, flash message and validation message | No hardcoded Bangla or English string left in views (a test greps for it) |
| 1.2 | Language switcher, choice saved on the user, default Bangla | Toggle changes every page, survives logout |
| 1.3 | Digits follow the language: Bangla digits in Bangla, Latin in English, for dates, money and counts | One helper used everywhere |
| 1.4 | Dates and months through one formatter (Bangla month names) | No raw `d/m/Y` left in views |

Risk: large diff touching every view. Mitigation: do it module by module, run the full suite after each, one reviewer pass at the end.

## Phase 2: complete the core workflows (about 1 week)

| # | Item | Done when |
|---|---|---|
| 2.1 | Exams: create and edit, publish and unpublish, lock marks after publish | Owner can run an exam from creation to published results |
| 2.2 | Report card: per student per exam, automatic total, GPA and grade, printable in Bangla and English | One click prints a correct card for a class |
| 2.3 | Fee setup: fee heads, per-class structures, discounts, monthly invoice generation with a preview | Owner sets up fees without a developer |
| 2.4 | Student lifecycle: deactivate, transfer out (with a transfer certificate), promote a class to the next year | Promotion is reversible within the same day and logged |
| 2.5 | Parent login: first-login by mobile and OTP or a one-time code from the owner, password set | A parent can sign in without staff typing a password |
| 2.6 | Staff attendance: daily check-in, monthly report, link to payroll deduction | Payslip generation can read absent days |

Order inside the phase: 2.3 before 2.1 (fees feed the dashboard), 2.4 needs academic year rollover, 2.2 needs 2.1.

## Phase 3: owner value and reporting (about 1 week)

| # | Item | Done when |
|---|---|---|
| 3.1 | Dashboard cards for new modules, role aware | A teacher never sees money, a parent never sees school totals |
| 3.2 | Fee reminders: overdue list, one-click notice or SMS, delivery log | Owner can message every defaulter in one action |
| 3.3 | Reports: dues by class, daily collection, monthly income against expense, attendance by class | Each report has a date filter and a print view |
| 3.4 | Exports: CSV and PDF for students, fees, payroll, expenses | Opens correctly in Excel with Bangla text |
| 3.5 | Global search for students by name, code or guardian mobile | One box in the header |
| 3.6 | Bulk student import from CSV with a dry run and an error report | A school can migrate its register in an afternoon |

## Phase 4: release readiness for selling (about 1 week)

| # | Item | Done when |
|---|---|---|
| 4.1 | School settings: logo, name, accent colour, address, module toggles, saved in the database and shown on receipts and report cards | New customer is branded without editing code |
| 4.2 | Installer polish: `kcam:install` also seeds sample classes and a fee head, and prints next steps | A fresh server goes from zero to login in under 15 minutes |
| 4.3 | Backups: nightly database dump command, retention, restore command, documented | Restore tested on a clean machine |
| 4.4 | Update path: documented `git pull`, `composer install`, `migrate`, `npm run build` and a `kcam:update` wrapper that runs them safely | An update never needs manual steps |
| 4.5 | Deploy guide for a cheap VPS: PHP, MySQL, Nginx, queue worker, scheduler, HTTPS, firewall | Followed on a fresh VPS without help |
| 4.6 | CI on GitHub Actions: MySQL service, Pint, Larastan, Pest, `composer audit`, `npm audit` | Green badge on `main`, required on pull requests |
| 4.7 | Security items: owner email or mobile verification, CSP nonce, Larastan config at a stated level | `docs/dev-plan.md` section 13 is empty |
| 4.8 | Test databases: root access fixed so each parallel run has its own database | Agents and CI can run in parallel |
| 4.9 | Error and uptime monitoring hook, log rotation | Owner is told when the site is down |

## Later (not scheduled)

bKash and Nagad collection, SMS gateway integrations beyond the log, parent mobile app on an API, diary and homework, pickup safety passes, admission inquiries, ID cards, multi-branch, offline attendance sync.

## Working method

- Tests first, then code. Aim for 80% or more coverage of new code.
- Tenant tables use `school_id` and the `BelongsToSchool` trait. Money is integer poisha. No floats.
- Every route has `can:` and `module:` middleware.
- Code work goes to Haiku agents in separate worktrees, each with its own test database once item 4.8 is done. Until then only one process uses a test database at a time.
- Each branch gets a read-only review, then the full suite on MySQL, then merge to `main`. Do not trust an agent's test claim: rerun it.
- Two lessons already paid for: a PHP fatal error can exit the test run with no output (run files one at a time to find it), and merge conflicts in shared files (routes, nav, seeder) must be checked by compiling every view and running the suite, not by reading the diff.

## Definition of done for any item

Failing test first, passing after. Authorization tested for every role. Tenant isolation tested. Bilingual strings from the language files. Visual check on desktop and phone. Pint clean. Full suite green on MySQL. Reviewed. Docs updated if behavior changed.

## Risks

| Risk | Effect | Mitigation |
|---|---|---|
| Language retrofit touches every view | Regressions in layout and tests | Phase 1 before more pages, module by module |
| Money bugs in fees or payroll | Loss of trust with schools | Integer poisha, locked sequences, reviewer checks every money path |
| Single install per school multiplies support | Slow updates, drift between customers | Phase 4 update path and installer, one tagged release per version |
| Data loss on a customer server | Fatal for the business | Phase 4 backups with a tested restore |
| Agent output untested on MySQL | Silent bugs such as row locks never exercised | Rerun every branch on MySQL before merge |

## Carried over from docs/dev-plan.md section 13

Guardian pivot table, payment allocations, attendance sessions, parallel row-lock test. Owner verification, CSP nonce and Larastan config are now item 4.7.
