# Prueba-Energia — Backend API

🇬🇧 English | [🇧🇷 Português](README.pt.md) | [🇪🇸 Español](README.es.md)

Laravel REST API that computes an indexed electricity price from hourly consumption and hourly
OMIE_MD price data (Iberian wholesale electricity market — Spain/Portugal).

## 📋 Table of Contents

- [Stack](#stack)
- [How it works](#how-it-works)
- [Prerequisites](#prerequisites)
- [Installation and running](#installation-and-running)
- [Migrations and seeders](#migrations-and-seeders)
- [Testing and code quality](#testing-and-code-quality)
- [Environment variables](#environment-variables)
- [Project structure](#project-structure)
- [API endpoints](#api-endpoints)
- [Postman collection](#postman-collection)

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.3 |
| Database | MySQL 8.0 |
| Testing | Pest PHP 4.x |
| Static analysis | PHPStan / Larastan (level 8) |
| Formula evaluation | symfony/expression-language |
| Container | Docker + Docker Compose |
| Web server | Nginx + PHP-FPM |

## How it works

In the Iberian market, energy retailers offer contracts indexed to the OMIE wholesale price. The
final price a customer pays is a function of the hourly OMIE_MD variation, the customer's
consumption, and a contractual formula. This API automates that calculation for any date range and
any user-supplied formula.

- `consumptions` and `prices` are keyed by a unique `date` and each store 25 hourly slots
  (`h1`..`h25`) to accommodate DST fall-back days (an extra hour in October). `h25` is `NULL` on
  normal days and is treated as `0` in calculations.
- A `formula` must literally contain the token `[OMIE_MD]` (e.g. `([OMIE_MD] * 0.6) + 0.88`).
  Before evaluation, `[OMIE_MD]` is substituted with each hour's OMIE price. Formulas are evaluated
  with `symfony/expression-language` — **`eval()` is never used**.
- The indexed price is a consumption-weighted average:
  `price_indexed = Σ(formula(price_h) * consumption_h) / Σ(consumption_h)`, computed over every
  hour in the inclusive `[start_date, end_date]` range. Hours with zero consumption are skipped
  entirely (including formula evaluation for that hour).
- If any day in the range is missing a row in either table, the request fails with `404`. If total
  consumption for the range is `0`, it fails with `500` (division-by-zero edge case).
- `POST /calculate` never persists anything — it's a pure calculation endpoint.
- Out of scope by design: authentication, CRUD for `consumptions`/`prices`, and persisting
  calculation results. See
  [`docs/guia/PARTE-1-Guia-Backend-Laravel.md`](docs/guia/PARTE-1-Guia-Backend-Laravel.md) for the
  full PRD, business rules and architecture decision records (ADRs).

## Prerequisites

- Docker >= 24.0
- Docker Compose >= 2.20
- Git
- Make (optional, but recommended)

No local PHP or Composer installation is required — everything runs inside the container.

## Installation and running

```bash
# 1. Clone the repository
git clone <repo-url> prueba-energia-backend
cd prueba-energia-backend

# 2. Copy environment variables
cp .env.example .env

# 3. Start the containers
make up
# or: docker compose up -d --build

# 4. Install dependencies
make install
# or: docker compose exec app composer install

# 5. Generate the application key
make key
# or: docker compose exec app php artisan key:generate

# 6. Run migrations and seeders
make migrate
docker compose exec app php artisan db:seed
# or: make fresh (rebuilds the database from scratch and seeds it)

# The API will be available at: http://localhost:8000
```

If `http://localhost:8000` doesn't respond even though `docker compose ps` shows all three
containers healthy, that's a host networking/firewall issue, not an application problem — verify
the stack works with `docker compose exec app curl http://nginx/api/v1/health` first, then check
your local firewall or security software for rules affecting port 8000.

## Migrations and seeders

```bash
# Run migrations
make migrate

# Rebuild the database from scratch (migrate:fresh --seed)
make fresh

# Run seeders only
make seed

# Roll back the last migration
docker compose exec app php artisan migrate:rollback
```

The seeders populate sample data for March 2025 in the `consumptions` and `prices` tables (31 days
each), letting you exercise the `/calculate` endpoint immediately after setup.

## Testing and code quality

```bash
# Run the full test suite (Pest)
make test

# Run with coverage (enforces a minimum of 85%)
make test-coverage

# Run a specific file or filter
docker compose exec app ./vendor/bin/pest tests/Feature/CalculateEndpointTest.php
docker compose exec app ./vendor/bin/pest --filter="calculates the indexed price"

# Code style (Laravel Pint)
make pint                                          # apply fixes
docker compose exec app ./vendor/bin/pint --test   # check only, no changes

# Static analysis (PHPStan / Larastan, level 8)
make phpstan
```

The suite covers unit tests for the formula evaluator/validator and the indexed-price calculator
(with mocked repositories), plus feature tests for every documented response of `/calculate` (200,
each `400` validation case, `404`, `500`) and the listing/health endpoints.

## Environment variables

See the full `.env.example`. Key variables:

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | Prueba-Energia | Application name |
| `APP_ENV` | local | Environment (local/production) |
| `APP_DEBUG` | true | Debug mode |
| `APP_URL` | http://localhost:8000 | Base URL |
| `APP_TIMEZONE` | Europe/Madrid | Timezone (relevant to OMIE hour semantics and DST) |
| `DB_CONNECTION` | mysql | Database driver |
| `DB_HOST` | mysql | Database host (Docker service name) |
| `DB_PORT` | 3306 | Port |
| `DB_DATABASE` | prueba_energia | Database name |
| `DB_USERNAME` | laravel | User |
| `DB_PASSWORD` | secret | Password |
| `LOG_CHANNEL` | stack | Log channel |
| `LOG_LEVEL` | debug | Log level |
| `CACHE_STORE` | database | Cache store |
| `CORS_ALLOWED_ORIGINS` | http://localhost:5173 | Allowed CORS origins |

Note: the test suite runs against SQLite in memory (configured in `phpunit.xml`), independent of
`.env`'s `DB_CONNECTION` — so tests run fast and don't depend on the MySQL container being up.

## Project structure

```
prueba-energia-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/     # Calculate, Consumption, Price, Health
│   │   ├── Requests/V1/            # input validation
│   │   └── Resources/V1/           # JSON response shaping
│   ├── Models/                     # Consumption, Price (+ Concerns/HasHourlySlots)
│   ├── Repositories/
│   │   ├── Contracts/              # interfaces
│   │   └── Eloquent/               # implementations
│   ├── Services/
│   │   ├── Formula/                # FormulaEvaluator, FormulaValidator
│   │   └── IndexedPrice/           # IndexedPriceCalculatorService + DTOs
│   ├── Exceptions/Api/             # ApiException and concrete error types
│   ├── Enums/                      # ErrorCode
│   ├── Rules/                      # ValidFormula
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Pest.php
├── docker/
│   ├── php/
│   ├── nginx/
│   └── mysql/
├── docs/guia/                      # original PRD/ADR/spec document
├── postman/                        # ready-to-import Postman collection
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── .env.example
└── README.md
```

## API endpoints

| Method | Route | Description | Auth |
|---|---|---|---|
| `POST` | `/api/v1/calculate` | Calculates the indexed price for a date range | No |
| `POST` | `/calculate` | Root-level alias, kept for strict compatibility with the original spec | No |
| `GET` | `/api/v1/consumptions` | Lists hourly consumptions (optional date range filter and pagination) | No |
| `GET` | `/api/v1/prices` | Lists hourly OMIE_MD prices (optional date range filter and pagination) | No |
| `GET` | `/api/v1/health` | Health check (includes database connection status) | No |

### `POST /api/v1/calculate`

```bash
curl -X POST http://localhost:8000/api/v1/calculate \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-03-01",
    "end_date": "2025-03-31",
    "formula": "([OMIE_MD] * 0.6) + 0.88"
  }'
```

**200 OK**
```json
{
  "data": {
    "price_indexed": 0.9642,
    "start_date": "2025-03-01",
    "end_date": "2025-03-31",
    "formula": "([OMIE_MD] * 0.6) + 0.88"
  }
}
```

**400** — validation error (missing field, bad date format, `end_date` before `start_date`, or
`formula` missing the `[OMIE_MD]` token):
```json
{
  "message": "The formula field must contain the [OMIE_MD] segment.",
  "errors": { "formula": ["The formula field must contain the [OMIE_MD] segment."] }
}
```

**404** — a day in the range has no consumption and/or price data:
```json
{
  "message": "No consumption or price data found for the specified date range.",
  "error_code": "DATA_NOT_FOUND",
  "details": { "missing_dates": ["2025-01-01", "2025-01-02"] }
}
```

**500** — total consumption for the range is zero:
```json
{
  "message": "Total consumption for the specified period is zero; indexed price cannot be calculated.",
  "error_code": "ZERO_CONSUMPTION"
}
```

### `GET /api/v1/consumptions` and `GET /api/v1/prices`

Both accept the same optional query parameters:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `start_date` | `Y-m-d` | — | Filters results from this date (inclusive) |
| `end_date` | `Y-m-d` | — | Filters results up to this date (inclusive) |
| `per_page` | integer | 30 | Page size (max 100) |

```bash
curl "http://localhost:8000/api/v1/consumptions?start_date=2025-03-01&end_date=2025-03-07&per_page=10"
```

```json
{
  "data": [
    { "id": 1, "date": "2025-03-01", "hours": { "h1": 5.2, "h2": 4.8, "...": "...", "h24": 6.1, "h25": null } }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 10, "total": 7 }
}
```

### `GET /api/v1/health`

```json
{ "status": "ok", "timestamp": "2025-11-01T12:00:00+01:00", "database": "ok" }
```

## Postman collection

A ready-to-import collection with all endpoints — including the documented `400`/`404` error
scenarios — is available at
[`postman/Prueba-Energia.postman_collection.json`](postman/Prueba-Energia.postman_collection.json).

In Postman: **File → Import** and select the file. All requests use a `base_url` collection
variable (default `http://localhost:8000`) — change it there instead of editing each request.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
