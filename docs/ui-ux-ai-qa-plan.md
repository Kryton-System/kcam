# KCAM | Frontend, UI/UX, AI Agents, and Backend Checks | Addendum to dev-plan.md

This document adds three things to `dev-plan.md`:

1. A frontend and UI/UX plan.
2. AI agents: (A) agents that help you **build** the product faster, and (B) AI features **inside** the product for schools.
3. A **backend check gate at every stage** of development.

---

## Part 1: Frontend and UI/UX Plan

### 1.1 Users and devices

- Main device: low to mid range Android phone (360 px wide), slow 3G/4G, sometimes offline.
- Owners also use a laptop or desktop for reports.
- Teachers have low digital literacy; parents may only use SMS and a link.

### 1.2 UX principles

1. **Bangla first.** Bangla labels, Bangla digits, Noto Sans Bengali, no mixed-language buttons.
2. **Paper-familiar.** Attendance looks like the register; fee collection looks like the receipt book.
3. **Fewest taps.** Daily tasks (attendance, collect fee) finish in under 30 seconds.
4. **One primary action per screen**, big touch targets (at least 48 px).
5. **Forgiving.** Undo for attendance, confirm before money actions, clear error messages in Bangla.
6. **Fast and light.** Page under 150 KB of JS where possible, lazy load tables, paginate.
7. **Accessible.** WCAG 2.2 AA contrast, labels on every input, keyboard navigation on desktop.
8. **Trust.** Show school name and logo on every receipt and parent page; visible payment confirmation.

### 1.3 Frontend stack (matches the Laravel plan)

- Blade + Alpine.js + Tailwind CSS 4, built with Vite. Plain controllers for simple pages; Livewire 3 only for interactive screens (attendance grid, fee collection, marks entry).
- Component library: Blade components for button, input, select, table, modal, toast, empty state, stat card.
- PWA: manifest, service worker, offline page, IndexedDB queue for attendance only. Cash collection stays online in the MVP, because receipt serials and double-collection checks need the server.
- Icons: Heroicons (inline SVG). Charts: lightweight (Chart.js) only on dashboard.
- Design tokens in the CSS `@theme` block of `resources/css/app.css` (Tailwind 4 has no `tailwind.config.js`): colors, spacing, radius, font sizes, so the look is consistent.

### 1.4 Information architecture (screens)

| Role | Main navigation (bottom tabs on mobile) |
|---|---|
| Owner | Home (dashboard) / Students / Fees / Reports / More |
| Teacher | Today / Attendance / Marks / Notices / More |
| Accountant | Collect / Dues / Receipts / Reports |
| Parent | Home / Fees / Attendance / Results / Notices |

Key flows to design first (wireframe, then prototype):

1. School signup and setup wizard (under 10 minutes).
2. Admit a student (single form and Excel import).
3. Take attendance (class list, tap to mark, submit, sync status).
4. Collect a fee (search student, see dues, choose method, receipt).
5. Parent pays online (SMS link, amount, bKash/Nagad, confirmation, receipt).
6. Enter marks and publish results.
7. Send a notice to a class.

### 1.5 Design process

| Step | Output | Check |
|---|---|---|
| Research | Interviews and photos of paper registers (see `idea.md`) | Real forms reflected in the screens |
| Wireframes | Low fidelity flows in Figma (Figma MCP is available) | Walk through with 3 owners and 3 teachers |
| Design system | Colors, type scale, components, Bangla font tests | Contrast checked, long Bangla text does not break layout |
| Hi-fi prototype | Clickable prototype for the 7 key flows | Usability test: can a new teacher take attendance without help? |
| Build | Blade components then pages | Matches design tokens; mobile first |
| Usability test on real device | Low-end Android over throttled network | Task time and error rate recorded |

### 1.6 Frontend quality gates (every sprint)

- Responsive check at 360, 768, 1280 px.
- Lighthouse mobile: performance 80+, accessibility 95+, PWA installable.
- Axe or Pa11y accessibility scan has zero critical issues.
- Bangla rendering check: digits, conjuncts (juktakkhor), long names, and print preview.
- Offline test: turn airplane mode on, take attendance, reconnect, confirm sync without duplicates.
- Playwright visual and flow tests for the key flows.
- Empty, loading, error, and success states exist for every list and form.

### 1.7 UI/UX success metrics

- A new teacher completes attendance in under 1 minute on first try.
- Accountant collects a fee in under 30 seconds.
- Parent completes online payment in under 2 minutes.
- Task success rate above 90% in usability tests.
- Support tickets about "how do I..." under 1 per school per month.

---

## Part 2: AI Agents

### 2A. AI agents that help build the product (development workflow)

Use specialised agents (Claude Code subagents and skills) at fixed points so each stage has an independent reviewer instead of one author checking their own work.

| Agent | Used for | When |
|---|---|---|
| `planner` | Break each sprint into tasks and acceptance criteria | Sprint start |
| `architect` | Review schema, tenancy, module boundaries, payment design | Before each new domain |
| `tdd-guide` | Enforce RED, GREEN, IMPROVE and the 80% coverage gate | During implementation |
| `code-reviewer` | Review every PR for correctness, readability, immutability | Before merge |
| `security-reviewer` | OWASP checks, tenant leakage, webhook and upload security | Every PR touching auth, money, uploads, webhooks |
| `database-reviewer` | Migrations, indexes, N+1 queries, transactions | Every migration |
| `e2e-runner` | Playwright flows for the key journeys | Every sprint end |
| `build-error-resolver` | Fix CI and build failures | On red CI |
| `refactor-cleaner` | Remove dead code and duplication | End of each phase |
| UI/UX agent (Figma MCP, `frontend-design`, `a11y-audit` skills) | Screens from design, accessibility scans | Frontend work |

**Rules for using agents**

- Agents propose, humans approve. Never merge without reading the diff.
- Every agent task has a written brief and a clear definition of done.
- Run `security-reviewer` and `database-reviewer` as separate read-only passes, not by the same agent that wrote the code.
- Keep prompts and agent outputs free of real student data, secrets, and API keys. Use the seeded demo school only.
- Record decisions in `docs/decisions/` (short ADR files) so later agents have context.

### 2B. AI features inside the product (for schools and parents)

Start small; add only after the MVP proves value. Build behind a `Features\AI` module with a provider interface (`LlmProvider`) so the model can be swapped, with per-school usage limits and cost tracking.

| Agent / feature | What it does | Phase |
|---|---|---|
| **Notice writer** | Owner types a short Bangla idea, the agent drafts a polite notice or SMS (holiday, exam, fee reminder) for review before sending | 2 |
| **Dues reminder assistant** | Suggests who to remind, with tone options (gentle, firm), never sends without approval | 2 |
| **Result comment generator** | Drafts short teacher remarks per student from marks and attendance, teacher edits | 2 |
| **Data entry from photo** | Teacher photographs a paper admission form or register; vision model extracts fields into a draft form for confirmation | 2 |
| **Bangla voice input** | Dictate notices or remarks in Bangla | 3 |
| **Parent help chatbot** | Answers "what is my child's due?", "when is the exam?" from the parent's own data only (read-only tools) | 3 |
| **Owner insights agent** | "Why did collection drop this month?" using aggregated reports; returns plain-language summary | 3 |
| **Dropout and risk alert** | Rule based first, ML later: flags long absences and unpaid fees | 3 |
| **Support agent** | Answers school staff how-to questions from your help docs | 3 |

**AI safety rules for the product**

1. **Human in the loop:** AI drafts, a person approves. No auto-sent SMS, no auto-changed marks or fees.
2. **Tenant isolation:** the agent can only call tools scoped to the current school and role. Tools are read-only unless explicitly approved.
3. **Privacy:** minimise student data sent to a model (no names or IDs when not needed), contract and region checks, log what was sent, never use school data for training.
4. **Prompt injection defense:** treat all user text (names, notices, uploaded photos) as untrusted data; the agent cannot follow instructions found inside records.
5. **Accuracy:** show "AI draft" label; for numbers, always read from the database, never let the model compute fees or marks.
6. **Cost control:** per school monthly quota, caching, smaller model for simple drafts, alerts on spend.
7. **Fallbacks:** if the AI is down, the feature hides gracefully and the core system works unchanged.
8. **Audit:** log every AI call (who, when, purpose, tokens) in the activity log.

### 2C. Laravel implementation notes for AI features

- `app/Domain/AI/` with `LlmProvider` interface, `ClaudeProvider` (and others as needed), prompt templates in files with Bangla variants.
- Run calls in queued jobs; stream or poll the result into a Livewire component.
- Tool layer: small PHP classes (`GetStudentDues`, `GetAttendanceSummary`) that enforce policies and the `school_id` scope; the model only sees their JSON output.
- Tests: fake the provider, test policies on every tool, test that a parent agent cannot read another family's data, test prompt-injection fixtures.
- Evaluate quality with a small set of Bangla examples before each release.

---

## Part 3: Backend Check at Every Stage of Development

A **backend gate** runs at each stage. A stage cannot be called done until its gate is green. Items marked (auto) run in CI; others are reviewed by an agent or a human.

### Gate A: Planning and design (before coding a feature)

- [ ] User story, acceptance criteria, and edge cases written.
- [ ] Data model reviewed by `architect` (tenancy column, indexes, constraints, soft deletes).
- [ ] Permissions matrix defined (who can view, create, edit, delete, export).
- [ ] Money, privacy, and audit impact noted.
- [ ] API and event contracts sketched (request, response, errors).

### Gate B: Development (while coding, TDD)

- [ ] Failing test written first, then minimal implementation (RED, GREEN, IMPROVE). (auto)
- [ ] Input validated with a Form Request or Livewire rules at every boundary, including query-string filters: type, maximum length, enum, and normalisation (Bangla digits, mobile format) as in `dev-plan.md` section 5.2.
- [ ] Every client-supplied id is checked against the current school (`Rule::exists(...)->where('school_id', ...)`); a test submits another school's id and expects 422 or 404.
- [ ] Output escaped for its context: `{{ }}` in HTML, `@js()` in script and Alpine attributes, escaped `LIKE` terms, formula-safe exports. No `{!! !!}`. (auto: CI grep)
- [ ] Policy exists and is tested for every role; no logic only hidden in the UI. A wrong-role request returns 403.
- [ ] Queries go through tenant scope; no raw SQL with concatenation; `preventLazyLoading` on in dev. (auto)
- [ ] Money uses integers or decimals; operations in DB transactions; idempotent where repeatable.
- [ ] Errors are caught, logged with context, and shown as safe messages; nothing swallowed silently.
- [ ] Pint formatting and Larastan level 8 pass. (auto)

### Gate C: Pull request (before merge)

Reviewer passes are tiered by risk, so a small team is not slowed down on low-risk changes:

| Change touches | Reviewer passes required |
|---|---|
| Money, auth, tenancy, uploads, webhooks, AI tools | `code-reviewer` + `security-reviewer` (+ `database-reviewer` if there is a migration) |
| Any migration | `code-reviewer` + `database-reviewer` |
| Everything else (views, copy, reports, styling) | `code-reviewer` only |

The (auto) checks below run on every PR regardless of tier.

- [ ] `code-reviewer` pass: correctness, readability, small functions, no duplication.
- [ ] `security-reviewer` pass (high-risk tier): injection, XSS, mass assignment, authz, uploads, secrets, webhooks.
- [ ] `database-reviewer` pass on any migration: reversible, safe on a large table, correct indexes, restrict (not cascade) on money tables.
- [ ] Architecture test green: every model with `school_id` uses `BelongsToSchool`; no query runs without a current school. (auto)
- [ ] Cross-tenant leak test still green. (auto)
- [ ] Test suite green with coverage at least 80% for changed modules. (auto)
- [ ] No secrets in diff (secret scanner). (auto)
- [ ] Dependency audit: `composer audit` and `npm audit` clean or justified. (auto)

### Gate D: Integration and staging

- [ ] Migrations run on a copy of realistic data; rollback tested.
- [ ] Queue workers, scheduler, and failed-job handling verified.
- [ ] Payment sandbox: success, failure, duplicate webhook, delayed webhook, wrong amount, forged signature.
- [ ] SMS provider sandbox: delivery, failure, retry, credit deduction.
- [ ] E2E (Playwright) for the key flows pass. (auto)
- [ ] Load smoke test on heavy endpoints (attendance sync, invoice generation, report queries).
- [ ] Backup and restore of staging database verified.

### Gate E: Release (production)

- [ ] `APP_DEBUG=false`, HTTPS, secure cookies, correct CORS and headers.
- [ ] Config and route caching enabled; OPcache on.
- [ ] Database backup taken immediately before deploy; rollback plan written.
- [ ] Migrations are backward compatible with the previous app version (zero downtime).
- [ ] Health check endpoint and uptime monitor green.
- [ ] Error tracking (Sentry) receiving events; alerts routed to you.
- [ ] Release notes and changelog updated.

### Gate F: Post-release monitoring (continuous)

- [ ] Watch error rate, slow queries, queue depth, failed jobs, SMS and payment failure rates.
- [ ] Nightly payment reconciliation: gateway report vs database; mismatches alert.
- [ ] Weekly: review audit log for odd activity (bulk mark changes, receipt reversals).
- [ ] Monthly: backup restore test, dependency updates, permission review, cost review (SMS, AI, hosting).
- [ ] Quarterly: security review and a mini penetration test of auth, tenancy, and payments.

### How the gates map to sprints

| Sprint stage | Gates |
|---|---|
| Sprint start | A |
| Daily development | B |
| Every PR | C |
| Sprint end on staging | D |
| Release / pilot / launch | E |
| Always after launch | F |

### Extra backend checks for high-risk modules

| Module | Special checks |
|---|---|
| Fees and receipts | All amounts integer poisha (no float, no decimal), balance never negative, sums reconcile (invoices = payment allocations + dues, allocations = payment amount), receipts immutable, serial numbers unique and gapless per school from a locked counter row, parallel-collection test on MySQL, cash double submit blocked by client UUID |
| Online payments | Signature verification, amount and currency match, idempotency on (gateway, txn id), replay protection, reconciliation job, tenant context set from the payload before any query |
| Attendance sync | Client UUID idempotency, latest submission wins per section and date with the old values audit logged, no duplicate (enrollment, date) rows, a session row exists for every taken class, no absence SMS on holidays |
| Queue jobs and scheduled commands | Each sets the current school explicitly; a job with no school context fails loudly instead of touching all schools |
| Results | Only authorised teachers edit marks, change history kept, published results locked unless reopened with audit |
| Parent portal | Mobile OTP verified, parent sees only children linked through `guardian_student`, rate limits on OTP, OTP stored hashed, signed links expire |
| AI features | Tool scope per school and role, prompt-injection tests, PII minimisation, cost quotas |
| Data import | File validation, row-level errors, transaction per batch, no partial corrupt state |

---

## Updated Sprint Cadence (adds UX, AI, and gates to `dev-plan.md`)

| When | Add to the existing plan |
|---|---|
| Sprint 0 | Figma design system, Bangla font test, Tailwind tokens, Blade component library, CI gates C, agent briefs, gateway and SMS sender applications submitted |
| Sprints 1 to 7 | Design first, build second, usability test every key flow with a real teacher; run gates A to D each sprint |
| Pilot (Sprint 7 to 8) | Gate E for pilot (online only in Sprint 7); offline attendance switched on in Sprint 8 after the offline gate passes in a real school; collect UX metrics |
| Sprints 9 to 12 | Parent payment UX tested with real parents; gates for payments and exams; launch with gate E and F running |
| Phase 2 | Add AI notice writer, dues assistant, result comments, photo data entry, under the AI safety rules |
| Phase 3 | Parent chatbot, owner insights, voice input, risk alerts, with evals and cost limits |

## Immediate Next Steps

1. Create the Figma file and the design system (colors, Bangla type scale, components).
2. Sketch and test the attendance and fee collection flows on paper with two teachers.
3. Add the gate checklist as a pull request template in the repo, and the CI jobs (Pint, Larastan, Pest coverage, composer audit, secret scan).
4. Write the short agent briefs (planner, architect, reviewers) and keep them in `docs/agents/`.
5. Decide the AI provider and data policy before building any AI feature (privacy first).
