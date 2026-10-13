# Contributing

Binding standards: Plan §4 (backend), §5 (admin), §6 (Flutter), §7 (domain). This page is the short version.

## 1. Backend layering

`Controller → FormRequest → DTO (final readonly) → Service → Repository (interface + Eloquent) → Model`

- Controllers: authorize, build DTO, call **one** service method, return Resource / `ApiResponse` / view (~15 lines max).
- No `Model::query()` / `DB::table()` outside repositories (migrations and seeders excepted).
- Services own transactions, locks, events, audit and integration calls; they never read `request()`.
- Models: casts, relations, scopes only. Enums: values, `label()`, `color()`, transitions.
- Every status/type/permission/setting key is a backed enum. No magic strings.
- Money: integer minor units + currency via `Money` VO. Never floats.
- Critical writes: transaction + row lock + `Idempotency-Key`. Never trust client totals or statuses.
- External services only via adapters in `app/Integrations`; fake drivers only in local/testing.
- No hard-coded user-visible strings: `lang/ar/*` and `lang/en/*` (Arabic is default).
- Admin: `<x-admin.form>` (Precognition before submit), Yajra `AdminDataTable` + `<x-admin.filters-card>` collapsed by default,
  permission enum + policy + audit on every privileged action. Logical CSS utilities only (`ms-*`, `pe-*`, `start-*`).

## 2. Naming

| Thing | Convention | Example |
|---|---|---|
| Namespaces | `<Layer>/<Module>/` | `App\Services\Booking\OfferSelectionService` |
| DTOs | `<Thing>Data`, `<Thing>FilterData` | `CouponData` |
| Repositories | `<Model>RepositoryInterface` / `<Model>Repository` | `CouponRepository` |
| Enums | singular, TitleCase cases, snake_case values | `BookingStatus::SearchingOffers = 'searching_offers'` |
| Tables / columns | snake_case plural tables; money `*_minor` + `currency` | `amount_minor` |
| Routes | kebab-case paths, dot-named routes | `admin.coupons.store` |
| Translation keys | `<file>.<module>.<key>` | `admin.coupons.created` |
| Error codes | snake_case, stable forever | `booking_already_assigned` |
| Tests | `test_<behaviour>` methods, `<Feature>Test` classes | `test_loser_receives_conflict` |
| Dart | files snake_case, classes UpperCamel, feature-first folders | `features/booking/…` |

## 3. Branches and commits

- `main` always holds the latest finished milestone. Never force-push; never rewrite history.
- Work on `milestone/<id>-<slug>` (e.g. `milestone/p0.2-backend-core`) branched from `main`.
- When the gate passes: commit, then `git checkout main && git merge --ff-only milestone/<id>-<slug>`.
- Conventional commits: `feat(booking): …`, `fix(admin): …`, `chore: …`, `docs: …`, `test: …`, `refactor: …`.
- AI-authored commits end with the `Co-Authored-By:` trailer.

## 4. Before committing (quality gate, Plan §11.1)

```bash
make check     # pint --test, phpstan, php artisan test --parallel, npm run build, dart format, flutter analyze, flutter test
```

Then update `docs/IMPLEMENTATION_STATUS.md` with the real results, plus `DECISIONS.md`, `API.md`, `INTEGRATIONS.md` as needed.
Never claim a check passed unless you saw it pass.

## 5. Secrets

Never commit `.env`, keystores, `*.p12`, provisioning profiles, private keys, Firebase service-account JSON, DB dumps or user
documents. Use `.env.example` with documented placeholders.

## Core conventions added in P0.2

- Throw `BusinessRuleViolation::make('<code>', field: '<field>')`, `ConflictException` or `ForbiddenActionException` for expected
  domain errors and add `errors.<code>` to `lang/ar/api.php` **and** `lang/en/api.php`.
- Return JSON only through `App\Support\Http\ApiResponse`.
- New repository → interface in `Repositories/Contracts/<Module>/`, implementation extending `BaseRepository`, binding in
  `RepositoryServiceProvider::$bindings`.
- New polymorphic model → alias in `Relation::enforceMorphMap()` (`AppServiceProvider`).
- New enum → `use EnumHelpers`, implement `HasLabel` (and `HasColor` when shown as a badge), labels in `lang/{ar,en}/enums.php`.
- Money → `Money`/`MoneyCast` with `*_minor` + `currency` columns; never floats.
- Sensitive actions → `AuditLogger::record()` with `auditSnapshot()` before/after.
