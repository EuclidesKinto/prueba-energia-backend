# Prueba-Energia — Backend API

[🇬🇧 English](README.md) | 🇧🇷 Português | [🇪🇸 Español](README.es.md)

API REST em Laravel para cálculo de preços indexados de energia elétrica com base em consumos
horários e preços horários do segmento OMIE_MD (mercado atacadista Espanha/Portugal).

## 📋 Índice

- [Stack](#stack)
- [Como funciona](#como-funciona)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e execução](#instalação-e-execução)
- [Migrações e seeders](#migrações-e-seeders)
- [Testes e qualidade de código](#testes-e-qualidade-de-código)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Endpoints da API](#endpoints-da-api)
- [Collection do Postman](#collection-do-postman)

## Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 13 |
| Linguagem | PHP 8.3 |
| Banco | MySQL 8.0 |
| Testes | Pest PHP 4.x |
| Análise estática | PHPStan / Larastan (nível 8) |
| Avaliação de fórmula | symfony/expression-language |
| Container | Docker + Docker Compose |
| Web server | Nginx + PHP-FPM |

## Como funciona

No mercado ibérico, comercializadoras oferecem contratos indexados ao preço do OMIE (mercado
atacadista). O preço final ao cliente é uma função da variação horária do OMIE_MD, do consumo do
cliente e de uma fórmula contratual. Esta API automatiza esse cálculo para qualquer intervalo de
datas e qualquer fórmula fornecida pelo usuário.

- `consumptions` e `prices` têm `date` como chave única e armazenam 25 slots horários (`h1`..`h25`)
  cada, para acomodar dias de fall-back de horário de verão (uma hora extra em outubro). `h25` é
  `NULL` em dias comuns e é tratado como `0` nos cálculos.
- Uma `formula` deve conter literalmente o token `[OMIE_MD]` (ex.: `([OMIE_MD] * 0.6) + 0.88`).
  Antes da avaliação, `[OMIE_MD]` é substituído pelo preço OMIE de cada hora. As fórmulas são
  avaliadas com `symfony/expression-language` — **o `eval()` nunca é usado**.
- O preço indexado é uma média ponderada pelo consumo:
  `price_indexed = Σ(fórmula(preço_h) × consumo_h) / Σ(consumo_h)`, calculada sobre cada hora do
  intervalo `[start_date, end_date]` (inclusivo em ambas as pontas). Horas com consumo zero são
  totalmente ignoradas (inclusive a avaliação da fórmula para aquela hora).
- Se qualquer dia do intervalo não tiver registro em uma das tabelas, a requisição falha com `404`.
  Se a soma total de consumo do intervalo for `0`, falha com `500` (edge case de divisão por zero).
- `POST /calculate` nunca persiste nada — é um endpoint de cálculo puro.
- Fora de escopo por decisão de projeto: autenticação, CRUD para `consumptions`/`prices` e
  persistência do resultado do cálculo. Veja
  [`docs/guia/PARTE-1-Guia-Backend-Laravel.md`](docs/guia/PARTE-1-Guia-Backend-Laravel.md) para o
  PRD completo, as regras de negócio e os ADRs (Architecture Decision Records).

## Pré-requisitos

- Docker >= 24.0
- Docker Compose >= 2.20
- Git
- Make (opcional, mas recomendado)

Não é necessário PHP nem Composer instalados localmente — tudo roda dentro do container.

## Instalação e execução

```bash
# 1. Clonar o repositório
git clone <repo-url> prueba-energia-backend
cd prueba-energia-backend

# 2. Copiar variáveis de ambiente
cp .env.example .env

# 3. Subir os containers
make up
# ou: docker compose up -d --build

# 4. Instalar dependências
make install
# ou: docker compose exec app composer install

# 5. Gerar chave da aplicação
make key
# ou: docker compose exec app php artisan key:generate

# 6. Rodar migrações e seeders
make migrate
docker compose exec app php artisan db:seed
# ou: make fresh (recria o banco do zero e já semeia)

# A API estará disponível em: http://localhost:8000
```

Se `http://localhost:8000` não responder mesmo com `docker compose ps` mostrando os três
containers saudáveis, é um problema de rede/firewall do host, não da aplicação — confirme que a
stack funciona com `docker compose exec app curl http://nginx/api/v1/health` primeiro, e depois
verifique seu firewall local ou software de segurança por regras afetando a porta 8000.

## Migrações e seeders

```bash
# Rodar migrações
make migrate

# Recriar o banco do zero (migrate:fresh --seed)
make fresh

# Rodar apenas seeders
make seed

# Rollback da última migração
docker compose exec app php artisan migrate:rollback
```

Os seeders populam dados de exemplo para março de 2025 nas tabelas `consumptions` e `prices` (31
dias cada), permitindo testar o endpoint `/calculate` imediatamente após o setup.

## Testes e qualidade de código

```bash
# Rodar todos os testes (Pest)
make test

# Rodar com cobertura (mínimo de 85% obrigatório)
make test-coverage

# Rodar apenas um arquivo/filtro específico
docker compose exec app ./vendor/bin/pest tests/Feature/CalculateEndpointTest.php
docker compose exec app ./vendor/bin/pest --filter="calcula o preço indexado"

# Estilo de código (Laravel Pint)
make pint                                          # aplica as correções
docker compose exec app ./vendor/bin/pint --test   # só verifica, sem alterar

# Análise estática (PHPStan / Larastan, nível 8)
make phpstan
```

A suíte cobre testes unitários do avaliador/validador de fórmula e do calculador de preço indexado
(com repositórios mockados), além de testes de feature para cada resposta documentada do
`/calculate` (200, cada caso de `400`, `404`, `500`) e dos endpoints de listagem/health.

## Variáveis de ambiente

Ver `.env.example` completo. Principais variáveis:

| Variável | Padrão | Descrição |
|---|---|---|
| `APP_NAME` | Prueba-Energia | Nome da aplicação |
| `APP_ENV` | local | Ambiente (local/production) |
| `APP_DEBUG` | true | Modo debug |
| `APP_URL` | http://localhost:8000 | URL base |
| `APP_TIMEZONE` | Europe/Madrid | Timezone (importante para a semântica horária do OMIE e DST) |
| `DB_CONNECTION` | mysql | Driver do banco |
| `DB_HOST` | mysql | Host do banco (nome do serviço Docker) |
| `DB_PORT` | 3306 | Porta |
| `DB_DATABASE` | prueba_energia | Nome do banco |
| `DB_USERNAME` | laravel | Usuário |
| `DB_PASSWORD` | secret | Senha |
| `LOG_CHANNEL` | stack | Canal de log |
| `LOG_LEVEL` | debug | Nível de log |
| `CACHE_STORE` | database | Store de cache |
| `CORS_ALLOWED_ORIGINS` | http://localhost:5173 | Origens permitidas via CORS |

Observação: a suíte de testes usa SQLite em memória (configurado em `phpunit.xml`), independente do
`DB_CONNECTION` do `.env` — por isso os testes rodam rápido e sem depender do container MySQL.

## Estrutura de pastas

```
prueba-energia-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/     # Calculate, Consumption, Price, Health
│   │   ├── Requests/V1/            # validação de entrada
│   │   └── Resources/V1/           # formatação da resposta JSON
│   ├── Models/                     # Consumption, Price (+ Concerns/HasHourlySlots)
│   ├── Repositories/
│   │   ├── Contracts/              # interfaces
│   │   └── Eloquent/               # implementações
│   ├── Services/
│   │   ├── Formula/                # FormulaEvaluator, FormulaValidator
│   │   └── IndexedPrice/           # IndexedPriceCalculatorService + DTOs
│   ├── Exceptions/Api/             # ApiException e tipos de erro concretos
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
├── postman/                        # collection do Postman pronta pra importar
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── .env.example
└── README.md
```

## Endpoints da API

| Método | Rota | Descrição | Autenticação |
|---|---|---|---|
| `POST` | `/api/v1/calculate` | Calcula o preço indexado para um intervalo de datas | Não |
| `POST` | `/calculate` | Alias na raiz, mantido por compatibilidade estrita com a especificação original | Não |
| `GET` | `/api/v1/consumptions` | Lista consumos horários (com filtro opcional por intervalo e paginação) | Não |
| `GET` | `/api/v1/prices` | Lista preços horários OMIE_MD (com filtro opcional por intervalo e paginação) | Não |
| `GET` | `/api/v1/health` | Health check (inclui status da conexão com o banco) | Não |

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

**400** — erro de validação (campo ausente, formato de data inválido, `end_date` anterior a
`start_date`, ou `formula` sem o token `[OMIE_MD]`):
```json
{
  "message": "The formula field must contain the [OMIE_MD] segment.",
  "errors": { "formula": ["The formula field must contain the [OMIE_MD] segment."] }
}
```

**404** — algum dia do intervalo não tem dado de consumo e/ou preço:
```json
{
  "message": "No consumption or price data found for the specified date range.",
  "error_code": "DATA_NOT_FOUND",
  "details": { "missing_dates": ["2025-01-01", "2025-01-02"] }
}
```

**500** — soma total de consumo do intervalo é zero:
```json
{
  "message": "Total consumption for the specified period is zero; indexed price cannot be calculated.",
  "error_code": "ZERO_CONSUMPTION"
}
```

### `GET /api/v1/consumptions` e `GET /api/v1/prices`

Ambos aceitam os mesmos parâmetros de query opcionais:

| Parâmetro | Tipo | Padrão | Descrição |
|---|---|---|---|
| `start_date` | `Y-m-d` | — | Filtra resultados a partir dessa data (inclusive) |
| `end_date` | `Y-m-d` | — | Filtra resultados até essa data (inclusive) |
| `per_page` | inteiro | 30 | Tamanho da página (máximo 100) |

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

## Collection do Postman

Uma collection pronta pra importar, com todos os endpoints — incluindo os cenários de erro `400`/
`404` documentados — está disponível em
[`postman/Prueba-Energia.postman_collection.json`](postman/Prueba-Energia.postman_collection.json).

No Postman: **File → Import** e selecione o arquivo. Todas as requisições usam a variável de
collection `base_url` (padrão `http://localhost:8000`) — altere ela lá em vez de editar cada
requisição individualmente.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
