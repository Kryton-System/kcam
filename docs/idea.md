# KCAM | School Management Software for Small Bangladeshi Schools | Idea & Plan

## Brand

- **Product name:** KCAM (Kindergarten Cloud Academic Management)
- **Tagline (English):** Your whole school in your phone.
- **Tagline (Bangla idea):** আপনার পুরো স্কুল, আপনার ফোনে।
- **Voice:** simple, friendly, Bangla first, trustworthy with money.
- **To do before launch:** confirm domain (for example kcam.com.bd or kcam.app), Facebook page name, bKash/Nagad merchant name, and trademark search, because KCAM is not yet checked and UCAM is an existing product with a similar name.

## 1. The Problem

Bangladesh has a very large number of small kindergartens and primary-level schools (Nursery to Class 5, 50 to 200 students). Most run entirely on paper:

- Admission forms, attendance registers, fee receipt books, result sheets, all handwritten.
- Fees collected in cash at the school or by the teacher, with no digital receipt. Dues are tracked in a notebook and often disputed.
- Parents get no regular updates about attendance, results, or dues. Communication happens by word of mouth or paper notices.
- The owner or head teacher cannot see, in one place, how many students are enrolled, who has paid, and how much money is owed.
- Existing school software is built for large schools and colleges: too expensive, too complex, English-heavy, and needs a computer and reliable internet.

**Result:** lost records, leaked income, wasted teacher time, and parents who feel out of the loop.

### More paper-based problems that can be solved online

| # | Problem today | Online solution |
|---|---|---|
| 1 | Lost or torn admission files and registers | Cloud student records with photo and documents, searchable in seconds |
| 2 | Fake or duplicate receipts, cash leakage | Numbered digital receipts, audit log, daily cash closing report |
| 3 | Dues forgotten or disputed with parents | Auto dues ledger, SMS reminders, payment history visible to parent |
| 4 | Late fee and discount calculated by hand with errors | Rule-based fee engine (late fee, sibling discount, scholarship, waiver) |
| 5 | Teacher salary calculated on paper, delays and disputes | Payroll with attendance-linked salary, advance and deduction tracking, payslips |
| 6 | Teacher absence and late arrival unnoticed | Staff attendance with simple check-in, monthly report to owner |
| 7 | Report cards copied by hand, slow and error-prone | Auto GPA/grade calculation and printable Bangla report card in one click |
| 8 | Parents miss notices (exam dates, holidays, closures) | SMS and app notices, delivery log, emergency alerts (weather, flood, strike) |
| 9 | Parents cannot see homework or class diary | Daily digital diary and homework posts per class |
| 10 | Safe pickup: wrong person collects a young child | Authorised pickup list per child, OTP or QR pickup pass, pickup log |
| 11 | Van and transport confusion, parents worried | Route and van list, pickup/drop SMS, optional live location later |
| 12 | Parent-teacher communication only by physical visit | In-app messages and meeting booking, complaints and suggestion box |
| 13 | Admission inquiries recorded in a notebook and lost | Online inquiry and admission form, follow-up pipeline, seat count per class |
| 14 | Transfer certificate and testimonial written manually | Auto-generated TC, testimonial, and character certificates with serial numbers |
| 15 | Student promotion at year end is a huge manual task | One-click class promotion, roll renumbering, carry forward dues |
| 16 | Health data: allergies, vaccination, emergency contacts only on paper | Student health card, emergency contact quick view, vaccination reminders |
| 17 | Uniform, book, and stationery stock tracked in a notebook | Simple inventory and sales with low-stock alerts |
| 18 | No income and expense records, owner unsure of profit | Basic accounts: income, expense, monthly profit report |
| 19 | Event, picnic, annual sports and exam fees collected separately | Event module with online collection, consent forms, and headcount |
| 20 | Scholarship and stipend beneficiaries tracked on paper | Beneficiary list, disbursement records, exportable reports |
| 21 | Early learners' progress only in oral feedback | Kindergarten skill and milestone tracker with periodic progress notes to parents |
| 22 | Staff and parent feedback never collected | Short online surveys and ratings, monthly satisfaction score |
| 23 | School website or page missing, no online presence | Auto-generated public school page with admission info and notices |
| 24 | Document and form chaos (consent slips, leave applications) | Digital leave applications for students and staff, e-consent |
| 25 | Poor backup: one fire, flood, or theft loses everything | Automatic cloud backup and one-click data export |
| 26 | Regulatory and registration paperwork scattered | Document vault for registration, licence, land, and staff papers with expiry alerts |
| 27 | Dropout risk goes unnoticed | Alert when a student is absent several days or fees unpaid for months |
| 28 | Library books issued in a register and not returned | Simple library issue/return with due-date reminders |
| 29 | Duplicate or fake student identity | Unique student ID, birth registration number field, QR ID card |
| 30 | Many schools reinvent the same Bangla templates | Shared template library for notices, certificates, and report cards |

These are added to the roadmap: items 1 to 8, 13, 14, 15 fit the MVP and Phase 1; items 9 to 12, 16 to 19 fit Phase 2; the rest are Phase 3 or add-ons.

## 2. The Idea

A **simple, Bangla-first, mobile-first, low-cost school management platform (SaaS)** designed specifically for small schools of 50 to 200 students. A school can sign up and start using it in under one hour, with no IT staff and no training workshop.

**Positioning:** "Your whole school in your phone. Fees, attendance, results, and parent messages, in Bangla."

### Design principles

1. **Bangla first**, English optional. Bangla digits, Bangla fonts, Bangla SMS.
2. **Phone first.** The head teacher and teachers use Android phones; a web dashboard is secondary.
3. **Works on weak internet.** Offline-capable attendance and fee entry that syncs later (PWA).
4. **Mirror paper workflows.** Screens look like the registers and receipt books they already know.
5. **Cheap and predictable pricing** that a small school owner can approve alone.
6. **Parents need no app install** at first: SMS and a simple web link work on any phone.

## 3. Target Users

| Role | Main needs |
|---|---|
| Owner / Head teacher | Overview of students, income, dues, staff; reports; control |
| Class teacher | Fast attendance, enter marks, send notices, see student list |
| Accountant / office staff | Collect fees, issue receipts, track dues, daily cash report |
| Parent | See fees due, pay online, see attendance and results, get notices |
| Student (later) | Routine, homework, results (optional, phase 3) |

## 4. Feature Plan

### Core modules (MVP)

1. **School setup**: school profile, logo, academic year, classes (Nursery, KG, 1 to 5), sections, subjects, shifts.
2. **Student management**: admission form, photo, guardian details, mobile numbers, unique student ID, class and roll assignment, promotion to next class at year end, transfer certificate, import from Excel.
3. **Staff management**: teachers and staff profiles, class and subject assignment, basic salary record.
4. **Attendance**: daily attendance in 2 taps per student, offline support, automatic SMS to guardian on absence, monthly attendance report.
5. **Fee management**: fee heads (admission, monthly tuition, exam, session, transport, books, uniform), per-class and per-student fee structure, discounts and waivers, dues tracking, late fee rules, printable and digital receipts, daily collection report.
6. **Online payment**: bKash, Nagad, Rocket, and cards via an aggregator (SSLCommerz, aamarPay, or direct bKash/Nagad merchant APIs). Payment link sent by SMS, automatic reconciliation, receipt sent to parent.
7. **Exams and results**: exam terms, subject-wise marks entry, grade and GPA calculation, report card and mark sheet PDF in Bangla, result publishing to parents. Support the primary-level grading used in the school.
8. **Notices and communication**: SMS and in-app notices, class-wise or whole-school broadcast, holiday and exam alerts, reusable Bangla templates.
9. **Reports and dashboard**: enrolment count, attendance rate, collection vs dues, defaulter list, monthly income, exportable to PDF and Excel.
10. **Roles and permissions**: owner, teacher, accountant, parent, with audit log on money and marks changes.

### Phase 2 features

- Parent mobile app (Android) with push notifications.
- Class routine and timetable, homework and diary.
- ID card and admit card generation.
- Teacher salary and payroll with payslips.
- Expense tracking and simple accounts (income vs expense).
- Inventory for books and uniforms.
- Multi-branch support for owners with several campuses.
- Online admission form with public school page.

### Phase 3 features

- Student and parent portal with progress charts.
- Transport and van tracking, hostel (if needed).
- Library module.
- Digital learning content and video links.
- AI helpers: auto-drafted notices, result comments, dues reminder tone, voice entry in Bangla.
- Integration with government reporting formats where applicable.

## 5. Technical Plan

### Architecture

- **Multi-tenant SaaS**: one codebase, many schools, strict data isolation per school (school_id on every row, enforced in the data layer).
- **Frontend**: React or Next.js as a PWA (installable, offline cache, Bangla font), mobile-first UI.
- **Backend**: Node.js (NestJS) or Python (FastAPI), REST API with schema validation (Zod / Pydantic).
- **Database**: PostgreSQL with row-level security; Redis for queues and caching.
- **Offline sync**: local IndexedDB queue for attendance and fee entries, background sync with conflict rules.
- **File storage**: S3-compatible object storage for photos and PDFs.
- **PDF generation**: server-side templates for receipts, report cards, ID cards (Bangla font embedded, e.g. Noto Sans Bengali / SolaimanLipi).
- **Notifications**: SMS gateway (local providers such as SSL Wireless, BulkSMSBD, Alpha SMS), later push and WhatsApp.
- **Payments**: aggregator integration with webhook verification, idempotent payment records, daily reconciliation job.
- **Hosting**: start on a single region cloud (or a Bangladesh-based host for latency and data residency), automated backups, daily restore test.
- **Observability**: error tracking, uptime monitor, audit logs.

### Core data model (simplified)

`School, AcademicYear, Class, Section, Subject, Student, Guardian, Enrollment, Staff, AttendanceRecord, FeeHead, FeeStructure, Invoice, Payment, Receipt, Exam, Mark, ResultSummary, Notice, SmsLog, User, Role, AuditLog`

### Security and privacy

- Student data includes minors, so treat it as sensitive: encryption in transit and at rest, least-privilege roles, per-school isolation, audit trail.
- Never store card data; rely on the payment provider's hosted page.
- Secrets in environment variables or a secrets manager, never in code.
- Parent access by verified mobile number and OTP.
- Regular backups and a documented data export so schools can always take their data.
- Follow Bangladesh's data protection and digital security laws as they apply.

## 6. Product Roadmap

| Phase | Duration | Goal | Output |
|---|---|---|---|
| 0. Discovery | Weeks 1 to 3 | Validate the problem | 15 to 20 school interviews, collected paper forms, competitor review, pricing test |
| 1. MVP build | Weeks 4 to 14 | Core modules usable | Students, attendance, fees, receipts, SMS, basic reports |
| 2. Pilot | Weeks 15 to 22 | Prove value in real schools | 5 to 10 free pilot schools, weekly feedback, bug fixing |
| 3. Payments and results | Weeks 23 to 30 | Close the money loop | Online payment live, exams and report cards, parent view |
| 4. Public launch | Weeks 31 to 40 | First paying customers | Pricing live, onboarding flow, support channel, 50 schools |
| 5. Scale | Month 10 onward | Grow and extend | Parent app, payroll, accounts, multi-branch, 500+ schools |

Follow the user's TDD rule: tests first, 80%+ coverage, Playwright E2E for the critical flows (admission, attendance, fee collection, online payment, result publishing).

## 7. Go-To-Market

### Who to approach first

- Kindergartens and primary schools in district towns and city neighbourhoods, run by an owner-principal who makes decisions alone.
- Start in one area (for example one city zone) to allow in-person visits and word of mouth.

### Channels

1. **Field sales and demos**: a 15-minute demo on the owner's own phone, importing a sample class from a photo or Excel.
2. **Local school owner groups**: Facebook groups and WhatsApp groups of school owners and teachers.
3. **Referral program**: one free month for each referred school.
4. **Teacher and coaching associations**, local education officers, and printing shops that already serve schools.
5. **Facebook and YouTube Bangla tutorials**: short videos "how to collect fees without a receipt book".
6. **Free onboarding**: the team enters the first class data for the school to remove the biggest barrier.

### Pricing ideas (to be validated in discovery)

| Plan | Who | Price idea |
|---|---|---|
| Starter | Up to 100 students | BDT 500 to 800 per month |
| Standard | 101 to 200 students | BDT 1,000 to 1,500 per month |
| Plus | 200+ students, extras | Custom |

- Free 30-day trial, no card required.
- SMS charged at cost plus small margin, or bundled credits.
- Online payment: small percentage per transaction, ideally paid by the parent or absorbed in the plan, so the school sees no upfront cost.
- Annual prepay discount (2 months free) to improve cash flow.

## 8. Revenue Model

1. Monthly or yearly subscription per school (main).
2. SMS credit top-ups.
3. Payment gateway transaction margin.
4. Paid add-ons: parent app branding, payroll, ID cards, extra branches.
5. Onboarding and data-entry service fee for schools that want it done for them.

## 9. Competition and Differentiation

- **Large school ERPs**: expensive, complex, desktop-oriented. We win on price, simplicity, and Bangla mobile UX.
- **Generic apps (Excel, Google Sheets, WhatsApp groups)**: free but manual, with no payment or reporting. We win on automation and online payment.
- **Other local EdTech products**: study their pricing and gaps in the discovery phase.

**Moat:** deep fit for tiny schools, onboarding that takes under an hour, payment reconciliation, local support in Bangla, and the switching cost once a school has years of student and fee history.

## 10. Key Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Owners distrust software and prefer paper | Keep screens paper-like; offer printable receipts; free trial; free data entry |
| Low digital literacy of teachers | Bangla UI, large buttons, video guides, WhatsApp support |
| Poor internet | Offline-first attendance and fee entry with sync |
| Parents without smartphones | SMS-first notices and payment links |
| Payment integration delays and merchant onboarding | Start with an aggregator, apply for direct merchant accounts in parallel |
| Low willingness to pay | Low entry price, per-school plans, ROI message: "recover unpaid dues" |
| Data loss or breach | Backups, encryption, role-based access, audit logs |
| SMS cost eats margin | Bundle credits, push free in-app notices where possible |
| Support load at scale | Self-serve help center, in-app guides, tiered support |

## 11. Success Metrics

- **Activation**: school enters first 20 students and takes first attendance within 7 days.
- **Engagement**: percentage of school days with attendance marked; fee receipts issued per month.
- **Value**: percentage of fees collected digitally; reduction in overdue amount for pilot schools.
- **Retention**: monthly school churn below 3%.
- **Growth**: 10 pilot schools by month 5, 50 paying schools by month 10, 500 by month 24.
- **Satisfaction**: NPS above 40 among owners; parent rating of the payment experience.

## 12. Team and Budget (lean start)

**Initial team (4 to 6 people):**

- 1 product lead / founder (school relations, sales)
- 2 full-stack developers
- 1 UI/UX designer (Bangla-first)
- 1 QA / support person
- 1 field onboarding agent (part-time, grows with sales)

**Rough cost buckets:** salaries, cloud hosting, SMS credits for pilots, payment aggregator setup, field travel and demo devices, legal and company registration. Keep the MVP phase under a tight budget by using managed cloud services and open-source tools.

## 13. Immediate Next Steps (first 30 days)

1. Visit 15 to 20 small schools; photograph their registers, receipt books, and result sheets.
2. Interview owners, teachers, and 10 parents about pain points and willingness to pay.
3. List the 5 workflows to digitise first (admission, attendance, fee collection, dues follow-up, result sheet).
4. Pick the payment aggregator and SMS provider; start merchant account applications.
5. Design clickable Bangla mockups and test them with owners.
6. Decide the tech stack, set up the repo, CI, and test framework (TDD from day one).
7. Recruit 5 pilot schools and agree on a free pilot for feedback and a testimonial.

## 14. Vision

Within a few years, every small school in Bangladesh can run on a phone: admissions, attendance, fees, results, and parent communication, all in Bangla, all online. The paper register becomes a backup, not the system.
