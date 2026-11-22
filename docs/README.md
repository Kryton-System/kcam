# KCAM docs

## Index

| File | Purpose |
|---|---|
| idea.md | Product idea and scope |
| dev-plan.md | Stack, architecture, schema, sprints, security rules, catch-up status (section 13) |
| ui-ux-ai-qa-plan.md | UI/UX, AI and QA gates |
| decisions/ | Architecture decision records |
| agents/ | Agent notes |

## Local setup

1. Start the database container: `docker start kcam-mysql` (MySQL 8.0 on 127.0.0.1:3317).
2. Databases: `kcam` for dev, `kcam_test` for tests (forced in `phpunit.xml`). Credentials live only in `.env`.
3. `php artisan migrate` then `php artisan db:seed` (the demo seeder is not yet safe to re-run).
4. Run tests: `php artisan test --compact`. Format: `vendor/bin/pint --format agent`.

## Conventions

- Tenancy: `school_id` global scope fails closed; use `CurrentSchool::runAs` outside HTTP.
- Money: integer poisha via `App\Support\Money`; percent discounts in basis points.
- Input: Form Requests with `NormalizesInput`; search via `Search::contains`.
- Style: no em or en dashes, TDD, 80%+ coverage.

## Code navigation

CodeGraph index lives in `.codegraph/` (local only). Rebuild with `codegraph init -y` or `codegraph sync`.
