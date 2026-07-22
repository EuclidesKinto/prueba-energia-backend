# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

**Prueba-Energia** is a Laravel 13 / PHP 8.3 API that computes an indexed electricity price from
hourly consumption and hourly OMIE_MD price data. It runs on Docker (app + nginx + MySQL 8), tests
with Pest PHP 4, and is statically analyzed with PHPStan/Larastan at level 8.

The full spec — PRD, business rules (RN-001..RN-011), functional requirements (RF-001..RF-016),
acceptance criteria, and architecture decisions (ADR-001..ADR-008) — lives in
`docs/guia/PARTE-1-Guia-Backend-Laravel.md` (Portuguese). That document is the original planning
guide; this file describes what was actually built, which follows it closely with the adjustments
noted below.

Deviations from the guide's exact code samples:
- Pest 4 / Larastan 3 / PHPStan 2 are used instead of the versions the guide's snippets implied —
  Pest 3 does not support PHPUnit 12 (Laravel 13's default), and Larastan 3 is required for
  Laravel 13 + PHPStan 2 support.
- `routes/api.php` explicitly prefixes its versioned group with `Route::prefix('api/v1')` and
  `bootstrap/app.php` sets `apiPrefix: ''` on `withRouting()` — this is what makes the root
  `POST /calculate` alias (ADR-006) actually live at `/calculate` instead of being force-prefixed
  to `/api/calculate` by Laravel's default API routing.
- Repository/Service methods take and return Eloquent `Consumption`/`Price` models (via a shared
  `HasHourlySlots` trait providing `hoursArray()`), not raw arrays as in the guide's illustrative
  snippets.

## Commands

```bash
make up                    # build + start app/nginx/mysql containers
make install                # composer install (inside the app container, includes dev deps)
make key                    # php artisan key:generate
make migrate                # run migrations
make seed                   # run seeders (populates March 2025 sample data)
make fresh                  # migrate:fresh --seed

make test                   # full Pest suite
make test-coverage          # Pest with coverage (pcov driver baked into the image), min 85%
docker compose exec app ./vendor/bin/pest --filter="some test name"
docker compose exec app ./vendor/bin/pest tests/Feature/CalculateEndpointTest.php

make pint                   # apply Laravel Pint code style
docker compose exec app ./vendor/bin/pint --test   # check only

make phpstan                # Larastan level 8 static analysis
make shell                  # bash inside the app container
make artisan cmd="route:list"
```

Host PHP in this environment may not satisfy `composer.json`'s `^8.3` requirement or have the right
extensions — always run Composer/Artisan/Pest/Pint/PHPStan **inside the app container** via
`docker compose exec app ...` (or the `make` targets above), never on the host directly.

`docker compose exec app curl http://nginx/...` is the reliable way to smoke-test an endpoint from
inside this environment; the host-published port (`localhost:8000`) can be unreliable depending on
the sandbox's network setup even when the stack is healthy.

## Architecture

```
Controller (thin) → FormRequest (validation) → Service (business logic) → Repository (data access) → Model → DB
```

- `app/Http/Controllers/Api/V1/` — `CalculateController`, `ConsumptionController`, `PriceController`,
  `HealthController`. Orchestration only, no business logic.
- `app/Http/Requests/V1/` — `CalculateRequest` (validates `start_date`/`end_date`/`formula`, the
  latter via the `App\Rules\ValidFormula` rule) and `DateRangeRequest` (optional range + pagination
  filters shared by the two listing endpoints).
- `app/Http/Resources/V1/` — shape JSON responses (`CalculateResource` wraps the `CalculateResult`
  DTO; `ConsumptionResource`/`PriceResource` expose `{ id, date, hours: {h1..h25} }`).
- `app/Services/Formula/` — `FormulaEvaluator` (uses `symfony/expression-language`, substitutes the
  literal `[OMIE_MD]` token for a safe `omie_md` variable before evaluating — **never `eval()`**)
  and `FormulaValidator` (checks token presence + RN-011's character whitelist).
- `app/Services/IndexedPrice/` — `IndexedPriceCalculatorService` orchestrates the two repositories,
  enforces full date-range coverage, and computes the weighted average; `DTOs/CalculateInput` and
  `DTOs/CalculateResult` are the readonly value objects passed in/out.
- `app/Repositories/Contracts/` (interfaces) + `app/Repositories/Eloquent/` (implementations) —
  bound in `App\Providers\RepositoryServiceProvider` (registered in `bootstrap/providers.php`).
  Controllers/Services always go through repositories, never call `Model::query()` directly.
- `app/Models/Concerns/HasHourlySlots.php` — shared by `Consumption` and `Price`; provides
  `hoursArray()` returning `['h1' => ..., ..., 'h25' => ...]`.
- `app/Enums/ErrorCode.php` — `VALIDATION_FAILED`, `INVALID_FORMULA`, `DATA_NOT_FOUND`,
  `ZERO_CONSUMPTION`, `CALCULATION_ERROR`, `INTERNAL_ERROR`.
- `app/Exceptions/Api/ApiException.php` — abstract base with its own `render()` method (Laravel
  calls it automatically), so concrete exceptions (`DataNotFoundException` 404,
  `InvalidFormulaException` 400, `ZeroConsumptionException` 500) don't need any wiring beyond
  extending it. `ValidationException` is separately intercepted in `bootstrap/app.php` to respond
  `400` (not Laravel's default `422`) with `{ message, errors }`, per ADR-007.

## Core domain rules

- `formula` must literally contain `[OMIE_MD]`; it's substituted with each hour's price before
  evaluation.
- `price_indexed = Σ(formula(price_h) * consumption_h) / Σ(consumption_h)` over every hour in the
  inclusive `[start_date, end_date]` range. Hours with zero consumption are skipped entirely
  (including formula evaluation), so an invalid/unusual price on a zero-consumption hour never
  causes an error.
- `h25` (DST fall-back slot) is `NULL` on normal days and is treated as `0`.
- Any missing day (in either `consumptions` or `prices`) across the requested range → `404`
  `DATA_NOT_FOUND` with the list of missing dates.
- Zero total consumption for the range → `500` `ZERO_CONSUMPTION`.
- `/calculate` (both the `/api/v1/` and root-alias routes) never persists anything.
- No auth, no CRUD for `consumptions`/`prices` — explicitly out of scope (ADR-003, PRD §10).

## Conventions

- `declare(strict_types=1);` at the top of every PHP file.
- PSR-12 via Pint (`laravel` preset).
- PHP 8.3+ features where useful: readonly properties, enums, constructor promotion, named arguments.
- Routes: kebab-case. DB columns: snake_case. Classes: PascalCase. Methods/properties: camelCase.
- API responses versioned under `/api/v1/`.
- App timezone is `Europe/Madrid` (relevant to OMIE hour semantics and `h25`).
- Tests run against SQLite in-memory (`phpunit.xml` overrides `DB_CONNECTION` regardless of
  `.env`), so the Pest suite doesn't need the MySQL container to be healthy — only the app
  container's PHP with `pdo_sqlite` (already in the image).
