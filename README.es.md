# Prueba-Energia — Backend API

[🇬🇧 English](README.md) | [🇧🇷 Português](README.pt.md) | 🇪🇸 Español

API REST en Laravel para calcular precios indexados de energía eléctrica a partir de consumos
horarios y precios horarios del segmento OMIE_MD (mercado mayorista España/Portugal).

## 📋 Índice

- [Stack](#stack)
- [Cómo funciona](#cómo-funciona)
- [Requisitos previos](#requisitos-previos)
- [Instalación y ejecución](#instalación-y-ejecución)
- [Migraciones y seeders](#migraciones-y-seeders)
- [Tests y calidad de código](#tests-y-calidad-de-código)
- [Variables de entorno](#variables-de-entorno)
- [Estructura de carpetas](#estructura-de-carpetas)
- [Endpoints de la API](#endpoints-de-la-api)
- [Colección de Postman](#colección-de-postman)

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Laravel 13 |
| Lenguaje | PHP 8.3 |
| Base de datos | MySQL 8.0 |
| Tests | Pest PHP 4.x |
| Análisis estático | PHPStan / Larastan (nivel 8) |
| Evaluación de fórmula | symfony/expression-language |
| Contenedor | Docker + Docker Compose |
| Servidor web | Nginx + PHP-FPM |

## Cómo funciona

En el mercado ibérico, las comercializadoras ofrecen contratos indexados al precio del OMIE
(mercado mayorista). El precio final para el cliente es una función de la variación horaria del
OMIE_MD, del consumo del cliente y de una fórmula contractual. Esta API automatiza ese cálculo para
cualquier intervalo de fechas y cualquier fórmula proporcionada por el usuario.

- `consumptions` y `prices` tienen `date` como clave única y almacenan 25 franjas horarias
  (`h1`..`h25`) cada una, para acomodar días de cambio de horario (fall-back, una hora extra en
  octubre). `h25` es `NULL` en días normales y se trata como `0` en los cálculos.
- Una `formula` debe contener literalmente el token `[OMIE_MD]` (ej.: `([OMIE_MD] * 0.6) + 0.88`).
  Antes de evaluarla, `[OMIE_MD]` se sustituye por el precio OMIE de cada hora. Las fórmulas se
  evalúan con `symfony/expression-language` — **`eval()` nunca se utiliza**.
- El precio indexado es un promedio ponderado por el consumo:
  `price_indexed = Σ(fórmula(precio_h) × consumo_h) / Σ(consumo_h)`, calculado sobre cada hora del
  intervalo `[start_date, end_date]` (inclusivo en ambos extremos). Las horas con consumo cero se
  ignoran por completo (incluida la evaluación de la fórmula para esa hora).
- Si algún día del intervalo no tiene registro en alguna de las tablas, la solicitud falla con
  `404`. Si la suma total de consumo del intervalo es `0`, falla con `500` (caso límite de
  división por cero).
- `POST /calculate` nunca persiste nada — es un endpoint de cálculo puro.
- Fuera de alcance por decisión de diseño: autenticación, CRUD para `consumptions`/`prices` y
  persistencia del resultado del cálculo. Consulta
  [`docs/guia/PARTE-1-Guia-Backend-Laravel.md`](docs/guia/PARTE-1-Guia-Backend-Laravel.md) para el
  PRD completo, las reglas de negocio y los ADRs (Architecture Decision Records).

## Requisitos previos

- Docker >= 24.0
- Docker Compose >= 2.20
- Git
- Make (opcional, pero recomendado)

No es necesario tener PHP ni Composer instalados localmente — todo se ejecuta dentro del
contenedor.

## Instalación y ejecución

```bash
# 1. Clonar el repositorio
git clone <repo-url> prueba-energia-backend
cd prueba-energia-backend

# 2. Copiar las variables de entorno
cp .env.example .env

# 3. Levantar los contenedores
make up
# o: docker compose up -d --build

# 4. Instalar dependencias
make install
# o: docker compose exec app composer install

# 5. Generar la clave de la aplicación
make key
# o: docker compose exec app php artisan key:generate

# 6. Ejecutar migraciones y seeders
make migrate
docker compose exec app php artisan db:seed
# o: make fresh (recrea la base de datos desde cero y la siembra)

# La API estará disponible en: http://localhost:8000
```

Si `http://localhost:8000` no responde aunque `docker compose ps` muestre los tres contenedores
saludables, es un problema de red/firewall del host, no de la aplicación — primero confirma que la
stack funciona con `docker compose exec app curl http://nginx/api/v1/health`, y luego revisa tu
firewall local o software de seguridad por reglas que afecten al puerto 8000.

## Migraciones y seeders

```bash
# Ejecutar migraciones
make migrate

# Recrear la base de datos desde cero (migrate:fresh --seed)
make fresh

# Ejecutar solo los seeders
make seed

# Revertir la última migración
docker compose exec app php artisan migrate:rollback
```

Los seeders generan datos de ejemplo para marzo de 2025 en las tablas `consumptions` y `prices` (31
días cada una), lo que permite probar el endpoint `/calculate` inmediatamente después de la
instalación.

## Tests y calidad de código

```bash
# Ejecutar toda la suite de tests (Pest)
make test

# Ejecutar con cobertura (mínimo obligatorio del 85%)
make test-coverage

# Ejecutar solo un archivo/filtro específico
docker compose exec app ./vendor/bin/pest tests/Feature/CalculateEndpointTest.php
docker compose exec app ./vendor/bin/pest --filter="calcula el precio indexado"

# Estilo de código (Laravel Pint)
make pint                                          # aplica las correcciones
docker compose exec app ./vendor/bin/pint --test   # solo verifica, sin modificar

# Análisis estático (PHPStan / Larastan, nivel 8)
make phpstan
```

La suite cubre tests unitarios del evaluador/validador de fórmulas y del calculador de precio
indexado (con repositorios simulados), además de tests de feature para cada respuesta documentada
de `/calculate` (200, cada caso de `400`, `404`, `500`) y los endpoints de listado/health.

## Variables de entorno

Ver el `.env.example` completo. Variables principales:

| Variable | Predeterminado | Descripción |
|---|---|---|
| `APP_NAME` | Prueba-Energia | Nombre de la aplicación |
| `APP_ENV` | local | Entorno (local/production) |
| `APP_DEBUG` | true | Modo debug |
| `APP_URL` | http://localhost:8000 | URL base |
| `APP_TIMEZONE` | Europe/Madrid | Zona horaria (relevante para la semántica horaria del OMIE y el DST) |
| `DB_CONNECTION` | mysql | Driver de base de datos |
| `DB_HOST` | mysql | Host de la base de datos (nombre del servicio Docker) |
| `DB_PORT` | 3306 | Puerto |
| `DB_DATABASE` | prueba_energia | Nombre de la base de datos |
| `DB_USERNAME` | laravel | Usuario |
| `DB_PASSWORD` | secret | Contraseña |
| `LOG_CHANNEL` | stack | Canal de log |
| `LOG_LEVEL` | debug | Nivel de log |
| `CACHE_STORE` | database | Almacén de caché |
| `CORS_ALLOWED_ORIGINS` | http://localhost:5173 | Orígenes permitidos vía CORS |

Nota: la suite de tests usa SQLite en memoria (configurado en `phpunit.xml`), independientemente
del `DB_CONNECTION` del `.env` — por eso los tests se ejecutan rápido y no dependen de que el
contenedor de MySQL esté activo.

## Estructura de carpetas

```
prueba-energia-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/     # Calculate, Consumption, Price, Health
│   │   ├── Requests/V1/            # validación de entrada
│   │   └── Resources/V1/           # formato de la respuesta JSON
│   ├── Models/                     # Consumption, Price (+ Concerns/HasHourlySlots)
│   ├── Repositories/
│   │   ├── Contracts/              # interfaces
│   │   └── Eloquent/               # implementaciones
│   ├── Services/
│   │   ├── Formula/                # FormulaEvaluator, FormulaValidator
│   │   └── IndexedPrice/           # IndexedPriceCalculatorService + DTOs
│   ├── Exceptions/Api/             # ApiException y tipos de error concretos
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
├── docs/guia/                      # documento original de PRD/ADR/spec
├── postman/                        # colección de Postman lista para importar
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── .env.example
└── README.md
```

## Endpoints de la API

| Método | Ruta | Descripción | Autenticación |
|---|---|---|---|
| `POST` | `/api/v1/calculate` | Calcula el precio indexado para un intervalo de fechas | No |
| `POST` | `/calculate` | Alias en la raíz, mantenido por compatibilidad estricta con la especificación original | No |
| `GET` | `/api/v1/consumptions` | Lista consumos horarios (con filtro opcional por intervalo y paginación) | No |
| `GET` | `/api/v1/prices` | Lista precios horarios OMIE_MD (con filtro opcional por intervalo y paginación) | No |
| `GET` | `/api/v1/health` | Health check (incluye el estado de la conexión con la base de datos) | No |

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

**400** — error de validación (campo ausente, formato de fecha inválido, `end_date` anterior a
`start_date`, o `formula` sin el token `[OMIE_MD]`):
```json
{
  "message": "The formula field must contain the [OMIE_MD] segment.",
  "errors": { "formula": ["The formula field must contain the [OMIE_MD] segment."] }
}
```

**404** — algún día del intervalo no tiene datos de consumo y/o precio:
```json
{
  "message": "No consumption or price data found for the specified date range.",
  "error_code": "DATA_NOT_FOUND",
  "details": { "missing_dates": ["2025-01-01", "2025-01-02"] }
}
```

**500** — la suma total de consumo del intervalo es cero:
```json
{
  "message": "Total consumption for the specified period is zero; indexed price cannot be calculated.",
  "error_code": "ZERO_CONSUMPTION"
}
```

### `GET /api/v1/consumptions` y `GET /api/v1/prices`

Ambos aceptan los mismos parámetros de query opcionales:

| Parámetro | Tipo | Predeterminado | Descripción |
|---|---|---|---|
| `start_date` | `Y-m-d` | — | Filtra resultados desde esa fecha (inclusive) |
| `end_date` | `Y-m-d` | — | Filtra resultados hasta esa fecha (inclusive) |
| `per_page` | entero | 30 | Tamaño de página (máximo 100) |

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

## Colección de Postman

Una colección lista para importar, con todos los endpoints —incluidos los escenarios de error
`400`/`404` documentados— está disponible en
[`postman/Prueba-Energia.postman_collection.json`](postman/Prueba-Energia.postman_collection.json).

En Postman: **File → Import** y selecciona el archivo. Todas las solicitudes usan la variable de
colección `base_url` (predeterminada `http://localhost:8000`) — cámbiala ahí en lugar de editar
cada solicitud individualmente.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
