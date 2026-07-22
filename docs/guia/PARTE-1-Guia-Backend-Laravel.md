# PARTE 1 — GUIA BACKEND (Laravel API)

> **Projeto:** Prueba-Energia
> **Stack:** Laravel 11 + PHP 8.3 + MySQL 8 + Docker
> **Referência:** Teste técnico Nemon — Especificación Técnica / Prueba de Indexado de Energía

---

## Índice Geral

1. [README.md](#1-readmemd)
2. [CLAUDE.md](#2-claudemd)
3. [PRD — Product Requirements Document](#3-prd--product-requirements-document)
4. [ADRs — Architecture Decision Records](#4-adrs--architecture-decision-records)
5. [Docker & Infraestrutura](#5-docker--infraestrutura)
6. [Estrutura de Código Sugerida](#6-estrutura-de-código-sugerida)
7. [Plano de Testes (Pest PHP)](#7-plano-de-testes-pest-php)

---

# 1. README.md

````markdown
# Prueba-Energia — Backend API

API REST em Laravel 11 para cálculo de preços indexados de energia elétrica com base em consumos horários e preços horários do segmento OMIE_MD (mercado atacadista Espanha/Portugal).

## 📋 Índice

- [Stack](#stack)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e execução](#instalação-e-execução)
- [Migrações e seeders](#migrações-e-seeders)
- [Testes](#testes)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Endpoints da API](#endpoints-da-api)

## Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 11 |
| Linguagem | PHP 8.3 |
| Banco | MySQL 8.0 |
| Testes | Pest PHP 3.x |
| Fórmula | symfony/expression-language |
| Container | Docker + Docker Compose |
| Web server | Nginx + PHP-FPM |

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
# ou: docker compose exec app php artisan migrate --seed

# A API estará disponível em: http://localhost:8000
```

## Migrações e seeders

```bash
# Rodar migrações
make migrate

# Recriar o banco do zero
make fresh

# Rodar apenas seeders
make seed

# Rollback da última migração
docker compose exec app php artisan migrate:rollback
```

Os seeders populam dados de exemplo para março de 2025 nas tabelas `consumptions` e `prices`, permitindo testes imediatos do endpoint `/calculate`.

## Testes

```bash
# Rodar todos os testes
make test

# Rodar com cobertura
make test-coverage

# Rodar apenas testes unitários
docker compose exec app ./vendor/bin/pest --group=unit

# Rodar apenas testes de feature
docker compose exec app ./vendor/bin/pest --group=feature

# Rodar um teste específico
docker compose exec app ./vendor/bin/pest tests/Feature/CalculateEndpointTest.php
```

## Variáveis de ambiente

Ver `.env.example` completo. Principais variáveis:

| Variável | Padrão | Descrição |
|---|---|---|
| `APP_NAME` | Prueba-Energia | Nome da aplicação |
| `APP_ENV` | local | Ambiente (local/production) |
| `APP_DEBUG` | true | Modo debug |
| `APP_URL` | http://localhost:8000 | URL base |
| `APP_TIMEZONE` | Europe/Madrid | Timezone (importante para OMIE) |
| `DB_CONNECTION` | mysql | Driver do banco |
| `DB_HOST` | mysql | Host do banco (nome do serviço Docker) |
| `DB_PORT` | 3306 | Porta |
| `DB_DATABASE` | prueba_energia | Nome do banco |
| `DB_USERNAME` | laravel | Usuário |
| `DB_PASSWORD` | secret | Senha |
| `LOG_CHANNEL` | stack | Canal de log |
| `LOG_LEVEL` | debug | Nível de log |
| `CACHE_STORE` | database | Store de cache |

## Estrutura de pastas

```
prueba-energia-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Middleware/
│   ├── Models/
│   ├── Repositories/
│   │   ├── Contracts/
│   │   └── Eloquent/
│   ├── Services/
│   │   ├── Formula/
│   │   └── IndexedPrice/
│   ├── Exceptions/
│   ├── Enums/
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
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── .env.example
├── .dockerignore
└── README.md
```

## Endpoints da API

| Método | Rota | Descrição | Autenticação |
|---|---|---|---|
| `POST` | `/api/v1/calculate` | Calcula o preço indexado para um intervalo de datas | Não |
| `GET` | `/api/v1/consumptions` | Lista consumos horários (com filtro opcional por range) | Não |
| `GET` | `/api/v1/prices` | Lista preços horários OMIE_MD (com filtro opcional por range) | Não |
| `GET` | `/api/v1/health` | Health check | Não |

Detalhes completos de request/response no [PRD](./docs/PRD.md).
````

---

# 2. CLAUDE.md

````markdown
# CLAUDE.md — Contexto para Claude Code (Backend)

Este arquivo orienta o Claude Code sobre convenções, arquitetura e regras do projeto Prueba-Energia (backend Laravel).

## Contexto do Projeto

- **O que é**: API REST para calcular o preço indexado de energia elétrica.
- **Cliente**: Nemon (teste técnico).
- **Domínio**: Mercado atacadista de eletricidade (OMIE — Espanha/Portugal).
- **Núcleo do negócio**: dado um período de faturamento, um conjunto de consumos horários e preços horários, calcular uma média ponderada aplicando uma fórmula configurável que utiliza `[OMIE_MD]` como variável.

## Convenções de Código

- **PSR-12** para estilo de código PHP.
- **PHP 8.3+**: usar readonly properties, enums, constructor promotion, named arguments quando apropriado.
- **Tipagem estrita**: `declare(strict_types=1);` no topo de cada arquivo.
- **Naming**:
  - Classes: `PascalCase`.
  - Métodos e propriedades: `camelCase`.
  - Constantes: `SCREAMING_SNAKE_CASE`.
  - Rotas: `kebab-case`.
  - Colunas de banco: `snake_case`.
- **Comentários**: apenas quando o código não é autoexplicativo. Preferir nomes claros.
- **PHPDoc**: obrigatório em métodos públicos de Services e Repositories quando o tipo por si só não descreve a intenção.

## Arquitetura

### Camadas

```
Controller → FormRequest → Service → Repository → Model → Database
                                  ↓
                              (retorno)
                                  ↓
                              Resource
```

- **Controllers** (`app/Http/Controllers/Api/V1/`): finos, só orquestram; sem lógica de negócio.
- **FormRequests** (`app/Http/Requests/`): concentram validação de entrada.
- **Resources** (`app/Http/Resources/`): moldam a resposta JSON.
- **Services** (`app/Services/`): contêm lógica de negócio.
- **Repositories** (`app/Repositories/`): abstraem o acesso a dados. Interface em `Contracts/`, implementação em `Eloquent/`.
- **Models** (`app/Models/`): Eloquent puro, mínimo de lógica.
- **Enums** (`app/Enums/`): tipos fechados (ex.: códigos de erro).

### Padrão de Injeção

Todas as dependências são injetadas via construtor. Interfaces são bindadas em `AppServiceProvider`:

```php
$this->app->bind(
    ConsumptionRepositoryInterface::class,
    EloquentConsumptionRepository::class
);
```

### Fluxo do `POST /calculate`

1. `CalculateController::store()` recebe a requisição.
2. `CalculateRequest` valida entrada (regras da seção "Regras de Negócio").
3. `IndexedPriceCalculatorService::calculate()` orquestra:
   - Busca consumos e preços via repositórios.
   - Verifica cobertura do range (para retornar 404 se aplicável).
   - Delega avaliação da fórmula ao `FormulaEvaluator`.
   - Calcula a média ponderada.
4. Retorna `CalculateResource` com `{ "price_indexed": <float> }`.

## Regras de Negócio (extraídas do PDF)

- **RN-001**: `date` é chave natural única em `consumptions` e `prices`.
- **RN-002**: Cada dia tem 25 slots horários (`h1`–`h25`) para acomodar mudança de horário (fall-back).
- **RN-003**: O cálculo requer **ambos** consumo e preço presentes para **todo** o intervalo. Se algum dia faltar dado em qualquer das tabelas → **404**.
- **RN-004**: A `formula` **deve** conter o segmento literal `[OMIE_MD]`. Sem isso → **400**.
- **RN-005**: `[OMIE_MD]` é substituído pelo preço da hora correspondente ao avaliar a fórmula.
- **RN-006**: `precio_indexado = Σ(fórmula(preço_h) × consumo_h) / Σ(consumo_h)`.
- **RN-007**: O endpoint `/calculate` **não persiste** o resultado.
- **RN-008**: O intervalo `[start_date, end_date]` é inclusivo em ambas extremidades.
- **RN-009**: Valores `NULL` em `h25` (dias comuns) são tratados como `0`.
- **RN-010**: Se `suma_consumos = 0`, retornar **500** com mensagem clara (edge case não previsto no PDF).

## Comandos Úteis

```bash
# Desenvolvimento
make up              # sobe os containers
make down            # derruba os containers
make shell           # abre bash no container app
make logs            # tail dos logs

# Laravel
make artisan cmd="route:list"
make artisan cmd="make:model Foo -m"
make tinker

# Banco
make migrate         # rodar migrations
make fresh           # drop + migrate + seed
make seed            # rodar seeders

# Testes
make test            # todos os testes
make test-coverage   # com cobertura
make pest args="--filter=IndexedPrice"

# Qualidade
make pint            # Laravel Pint (formatação)
make phpstan         # análise estática
```

## Padrões de Commit (Conventional Commits)

```
<tipo>(<escopo>): <descrição curta>

[corpo opcional]

[rodapé opcional]
```

Tipos: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`, `build`.

Exemplos:
- `feat(calculate): implementa endpoint POST /calculate`
- `fix(formula): trata divisão por zero em suma_consumos`
- `test(indexed-price): adiciona teste de intervalo com dia faltante`
- `refactor(repository): extrai contrato ConsumptionRepositoryInterface`

## Fluxo de Trabalho (TDD)

1. Criar branch a partir de `main`: `feat/<escopo>` ou `fix/<escopo>`.
2. **Escrever o teste primeiro** (Pest — Feature ou Unit).
3. Ver o teste falhar.
4. Implementar o mínimo para o teste passar.
5. Refatorar mantendo os testes verdes.
6. Rodar `make pint` + `make phpstan`.
7. Commit seguindo Conventional Commits.
8. Abrir PR contra `main`.

## Testes e Linting

```bash
# Testes com filtro
docker compose exec app ./vendor/bin/pest --filter="calcula preço indexado"

# Cobertura mínima esperada: 85% nas camadas Service e Repository.
docker compose exec app ./vendor/bin/pest --coverage --min=85

# Linting
docker compose exec app ./vendor/bin/pint --test    # apenas verifica
docker compose exec app ./vendor/bin/pint           # aplica

# Análise estática (PHPStan nível 8)
docker compose exec app ./vendor/bin/phpstan analyse
```

## Segurança da Avaliação de Fórmula

⚠️ **NUNCA usar `eval()` do PHP nativo.**

- Usar `symfony/expression-language` com whitelist de operadores.
- Substituir `[OMIE_MD]` por um nome de variável seguro antes de avaliar (ex.: `omie_md`).
- Validar sintaxe antes de executar.
- Envolver a avaliação em try/catch e mapear falhas → erro 400 (fórmula inválida) ou 500 (erro inesperado).

## O que NÃO fazer

- ❌ Colocar lógica de negócio em controllers.
- ❌ Chamar `Model::query()` diretamente de controllers ou services (use repositories).
- ❌ Usar `eval()`, `create_function` ou `assert()` para avaliar strings.
- ❌ Commits sem mensagens seguindo Conventional Commits.
- ❌ Adicionar endpoints de CRUD para `consumptions` e `prices` — está fora de escopo conforme PDF.
- ❌ Persistir resultado do cálculo em banco.
````

---

# 3. PRD — Product Requirements Document

````markdown
# PRD — Prueba-Energia Backend

## Índice

1. [Visão Geral](#1-visão-geral)
2. [Objetivos e Métricas](#2-objetivos-e-métricas-de-sucesso)
3. [Personas](#3-personas--usuários)
4. [Requisitos Funcionais](#4-requisitos-funcionais)
5. [Requisitos Não-Funcionais](#5-requisitos-não-funcionais)
6. [Regras de Negócio](#6-regras-de-negócio)
7. [Modelo de Dados](#7-modelo-de-dados)
8. [Endpoints da API](#8-endpoints-da-api-detalhados)
9. [Critérios de Aceite](#9-critérios-de-aceite)
10. [Fora de Escopo](#10-fora-de-escopo)
11. [Riscos e Dependências](#11-riscos-e-dependências)

---

## 1. Visão Geral

**Produto:** API REST para gestão e cálculo de preços indexados de energia elétrica.

**Contexto:** No mercado ibérico, comercializadoras oferecem contratos indexados ao preço do OMIE (mercado atacadista). O preço final ao cliente é uma função da variação horária do OMIE_MD, do consumo do cliente e de uma fórmula contratual. A API automatiza esse cálculo, aceitando qualquer fórmula matemática configurável.

**Valor entregue:**
- Cálculo determinístico e auditável do preço indexado.
- Flexibilidade total na fórmula (ajustável por contrato/cliente).
- Base de dados histórica pronta para futuras análises.

---

## 2. Objetivos e Métricas de Sucesso

| Objetivo | Métrica | Meta |
|---|---|---|
| Cálculo correto | % de casos de teste passando | 100% |
| Baixa latência | P95 de `/calculate` para 1 mês de dados | < 300ms |
| Robustez | Cobertura de testes em Service/Repository | ≥ 85% |
| Segurança | Vulnerabilidades críticas em `composer audit` | 0 |
| Portabilidade | Setup em máquina nova via Docker | ≤ 5 min |

---

## 3. Personas / Usuários

### Persona 1 — Analista de Faturamento
- Fornece intervalo de faturamento e fórmula contratual.
- Precisa obter o preço indexado do período rapidamente.
- Não é técnico; usa a interface frontend, não a API diretamente.

### Persona 2 — Integrador de Sistemas (consumidor da API)
- Sistema ERP ou billing que chama `POST /calculate` programaticamente.
- Precisa de contrato estável e códigos de erro claros.

### Persona 3 — Auditor / QA
- Verifica se o cálculo bate com planilhas de referência.
- Consulta `GET /consumptions` e `GET /prices` para conferir a matéria-prima.

---

## 4. Requisitos Funcionais

| ID | Descrição | Prioridade |
|---|---|---|
| **RF-001** | Sistema deve armazenar consumos horários (h1–h25) por dia na tabela `consumptions`. | 🔴 Must |
| **RF-002** | Sistema deve armazenar preços horários OMIE_MD (h1–h25) por dia na tabela `prices`. | 🔴 Must |
| **RF-003** | Sistema deve garantir unicidade da coluna `date` em `consumptions` e `prices`. | 🔴 Must |
| **RF-004** | Sistema deve expor endpoint `POST /api/v1/calculate` aceitando `start_date`, `end_date` e `formula`. | 🔴 Must |
| **RF-005** | Sistema deve validar presença dos três campos e formato de datas (Y-m-d). | 🔴 Must |
| **RF-006** | Sistema deve validar que `formula` contém o segmento literal `[OMIE_MD]`. | 🔴 Must |
| **RF-007** | Sistema deve avaliar a fórmula substituindo `[OMIE_MD]` pelo preço da hora. | 🔴 Must |
| **RF-008** | Sistema deve calcular o preço indexado conforme fórmula da seção 7 (RN-006). | 🔴 Must |
| **RF-009** | Sistema deve retornar 404 se faltarem dados de consumo ou preço para qualquer dia do intervalo. | 🔴 Must |
| **RF-010** | Sistema deve retornar 500 em erros inesperados de processamento. | 🔴 Must |
| **RF-011** | Sistema deve retornar `{ "price_indexed": <float> }` no corpo do 200. | 🔴 Must |
| **RF-012** | Endpoint `/calculate` **não deve persistir** dados. | 🔴 Must |
| **RF-013** | Sistema deve expor `GET /api/v1/consumptions` com filtro opcional por `start_date`/`end_date`. | 🟡 Should |
| **RF-014** | Sistema deve expor `GET /api/v1/prices` com filtro opcional por `start_date`/`end_date`. | 🟡 Should |
| **RF-015** | Sistema deve expor `GET /api/v1/health` para health check. | 🟢 Nice |
| **RF-016** | Sistema deve suportar paginação nos endpoints GET. | 🟡 Should |

---

## 5. Requisitos Não-Funcionais

| ID | Descrição | Categoria |
|---|---|---|
| **RNF-001** | Backend em PHP 8.3+ com Laravel 11. | Tecnologia |
| **RNF-002** | Banco de dados MySQL 8.0. | Tecnologia |
| **RNF-003** | API REST versionada em `/api/v1/`. | Arquitetura |
| **RNF-004** | Timezone da aplicação: `Europe/Madrid`. | Regionalização |
| **RNF-005** | Respostas em JSON (`Content-Type: application/json`). | Interoperabilidade |
| **RNF-006** | Cobertura de testes ≥ 85% em Services e Repositories. | Qualidade |
| **RNF-007** | Latência P95 de `/calculate` para 1 mês de dados < 300 ms. | Performance |
| **RNF-008** | Containerização via Docker Compose. | Portabilidade |
| **RNF-009** | Logs estruturados em JSON no `stderr` (padrão container). | Observabilidade |
| **RNF-010** | Avaliação segura de fórmula (sem `eval()`). | Segurança |
| **RNF-011** | CORS habilitado para o domínio do frontend. | Segurança |
| **RNF-012** | Deploy público via URL HTTPS. | Deploy |

---

## 6. Regras de Negócio

| ID | Descrição |
|---|---|
| **RN-001** | `date` é chave natural única em `consumptions` e `prices`. |
| **RN-002** | Cada dia possui 25 slots horários (`h1`–`h25`); `h25` cobre fall-back de horário. |
| **RN-003** | O cálculo requer que **todo** dia do intervalo tenha registro em ambas as tabelas. |
| **RN-004** | `formula` deve conter obrigatoriamente `[OMIE_MD]`. |
| **RN-005** | `[OMIE_MD]` é substituído pelo preço da hora correspondente. |
| **RN-006** | `precio_indexado = Σ(fórmula(preço_h) × consumo_h) / Σ(consumo_h)`. |
| **RN-007** | `/calculate` não persiste nada. |
| **RN-008** | Intervalo `[start_date, end_date]` é inclusivo em ambas extremidades. |
| **RN-009** | Valores `NULL` em qualquer h1–h25 são tratados como `0`. |
| **RN-010** | Se `suma_consumos = 0`, retorna 500 com mensagem clara. |
| **RN-011** | Operadores permitidos na fórmula: `+ - * / ( ) . 0-9` e o token `[OMIE_MD]`. |

---

## 7. Modelo de Dados

### Tabela `consumptions`

```sql
CREATE TABLE consumptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    h1 DOUBLE NULL, h2 DOUBLE NULL, h3 DOUBLE NULL, h4 DOUBLE NULL,
    h5 DOUBLE NULL, h6 DOUBLE NULL, h7 DOUBLE NULL, h8 DOUBLE NULL,
    h9 DOUBLE NULL, h10 DOUBLE NULL, h11 DOUBLE NULL, h12 DOUBLE NULL,
    h13 DOUBLE NULL, h14 DOUBLE NULL, h15 DOUBLE NULL, h16 DOUBLE NULL,
    h17 DOUBLE NULL, h18 DOUBLE NULL, h19 DOUBLE NULL, h20 DOUBLE NULL,
    h21 DOUBLE NULL, h22 DOUBLE NULL, h23 DOUBLE NULL, h24 DOUBLE NULL,
    h25 DOUBLE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_consumptions_date (date)
);
```

### Tabela `prices`

Mesma estrutura de `consumptions`, apenas com semântica `€/kWh`.

### Diagrama Lógico

```
┌──────────────────────┐     JOIN por     ┌──────────────────────┐
│    consumptions      │◄─── date ───►│       prices         │
│──────────────────────│                  │──────────────────────│
│ id (PK)              │                  │ id (PK)              │
│ date (UNIQUE)        │                  │ date (UNIQUE)        │
│ h1..h25 (kWh)        │                  │ h1..h25 (€/kWh)      │
└──────────────────────┘                  └──────────────────────┘
```

Não há chave estrangeira física — o vínculo é lógico via `date`.

---

## 8. Endpoints da API (detalhados)

### 8.1. `POST /api/v1/calculate`

**Descrição:** Calcula o preço indexado para o intervalo informado.

**Request:**
```http
POST /api/v1/calculate HTTP/1.1
Content-Type: application/json

{
  "start_date": "2025-03-01",
  "end_date": "2025-03-31",
  "formula": "([OMIE_MD] * 0.6) + 0.88"
}
```

**Response 200:**
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

**Response 400 (validação):**
```json
{
  "message": "The formula field must contain the [OMIE_MD] segment.",
  "errors": {
    "formula": ["The formula field must contain the [OMIE_MD] segment."]
  }
}
```

**Response 404 (dados ausentes):**
```json
{
  "message": "No consumption or price data found for the specified date range.",
  "error_code": "DATA_NOT_FOUND",
  "details": {
    "missing_dates": ["2025-03-15", "2025-03-16"]
  }
}
```

**Response 500 (erro interno):**
```json
{
  "message": "An error occurred while processing the formula.",
  "error_code": "CALCULATION_ERROR"
}
```

---

### 8.2. `GET /api/v1/consumptions`

**Query params:**
- `start_date` (opcional, formato Y-m-d)
- `end_date` (opcional, formato Y-m-d)
- `per_page` (opcional, padrão 30)

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "date": "2025-03-01",
      "hours": {
        "h1": 5.2, "h2": 4.8, "h3": 3.9, "...": "...", "h24": 6.1, "h25": null
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 31,
    "per_page": 30
  }
}
```

---

### 8.3. `GET /api/v1/prices`

Idêntico a `/consumptions`, apenas com semântica de preço (€/kWh).

---

### 8.4. `GET /api/v1/health`

**Response 200:**
```json
{
  "status": "ok",
  "timestamp": "2025-11-01T12:00:00+01:00",
  "database": "ok"
}
```

---

## 9. Critérios de Aceite

### Feature: Cálculo de Preço Indexado

**AC-001** — Cálculo válido com dados completos
- **Dado** que existem consumos e preços para todos os dias do intervalo
- **Quando** faço POST em `/calculate` com fórmula válida
- **Então** recebo 200 com `price_indexed` calculado corretamente

**AC-002** — Fórmula sem `[OMIE_MD]`
- **Dado** um payload com `formula = "0.5 + 0.1"`
- **Quando** faço POST em `/calculate`
- **Então** recebo 400 com erro indicando ausência de `[OMIE_MD]`

**AC-003** — Datas ausentes no intervalo
- **Dado** que falta consumo para `2025-03-15`
- **Quando** faço POST com intervalo `2025-03-01` a `2025-03-31`
- **Então** recebo 404 com `error_code = "DATA_NOT_FOUND"`

**AC-004** — Campos obrigatórios ausentes
- **Dado** um payload sem `start_date`
- **Quando** faço POST em `/calculate`
- **Então** recebo 400 com erro de validação

**AC-005** — Fórmula com sintaxe inválida
- **Dado** `formula = "([OMIE_MD] * 0.6"` (parêntese não fechado)
- **Quando** faço POST em `/calculate`
- **Então** recebo 400 com erro claro sobre a fórmula

**AC-006** — `end_date` anterior a `start_date`
- **Dado** `start_date = "2025-03-31"` e `end_date = "2025-03-01"`
- **Quando** faço POST em `/calculate`
- **Então** recebo 400 com erro de validação

**AC-007** — Consumo total zero (edge case)
- **Dado** que todos os consumos no intervalo são 0
- **Quando** faço POST em `/calculate`
- **Então** recebo 500 com `error_code = "ZERO_CONSUMPTION"`

**AC-008** — Cálculo com valores `NULL` em `h25`
- **Dado** que `h25` é `NULL` para dias comuns
- **Quando** faço POST em `/calculate`
- **Então** `NULL` é tratado como `0` e o cálculo procede

---

### Feature: Visualização de Consumos e Preços

**AC-009** — Listagem paginada de consumos
- **Dado** que existem 100 registros
- **Quando** faço GET em `/consumptions?per_page=10`
- **Então** recebo 10 registros e metadados de paginação

**AC-010** — Filtro por intervalo de datas
- **Dado** que existem consumos entre 2025-01-01 e 2025-12-31
- **Quando** faço GET com `start_date=2025-03-01&end_date=2025-03-07`
- **Então** recebo apenas os 7 registros dessa semana

---

## 10. Fora de Escopo

- ❌ CRUD (POST/PUT/DELETE) de consumos e preços.
- ❌ Autenticação e autorização.
- ❌ Persistência do resultado do cálculo.
- ❌ Integração com API real do OMIE.
- ❌ Multi-tenancy.
- ❌ Notificações e jobs assíncronos.
- ❌ Cache de resultados.
- ❌ Rate limiting agressivo (usar apenas o default do Laravel).

---

## 11. Riscos e Dependências

| Risco | Impacto | Probabilidade | Mitigação |
|---|---|---|---|
| Avaliação insegura de fórmula (RCE) | 🔴 Alto | 🟡 Média | Usar `symfony/expression-language` com whitelist. |
| Divisão por zero | 🟡 Médio | 🟢 Baixa | Validação prévia + erro 500 explícito. |
| Timezone/DST → hora duplicada ou faltante | 🟡 Médio | 🟢 Baixa | Timezone fixa `Europe/Madrid`; testes cobrindo DST. |
| Performance em intervalos longos | 🟢 Baixo | 🟢 Baixa | Índice em `date`; benchmarks para 1 ano. |
| Ambiguidade na spec (h25, 404 parcial) | 🟡 Médio | 🟡 Média | Documentar decisões em ADRs. |
````

---

# 4. ADRs — Architecture Decision Records

## ADR-001: Escolha do Framework Backend — Laravel 11

````markdown
# ADR-001: Uso do Laravel 11 como framework backend

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

O PDF exige backend em PHP e permite qualquer framework. Precisamos de:
- Rápido time-to-market (é um teste técnico).
- ORM produtivo.
- Ferramental de testes maduro.
- Ecossistema ativo.

## Decisão

Usaremos **Laravel 11** com PHP 8.3.

## Consequências

**Positivas:**
- Ecossistema rico (Eloquent, Form Requests, Resources, Sanctum).
- Testabilidade nativa integrada com Pest.
- Grande base de conhecimento e comunidade.
- Suporte de longo prazo.

**Negativas:**
- Overhead maior comparado a Slim para uma API tão pequena.
- Curva de aprendizado para quem não conhece o framework.

## Alternativas Consideradas

- **Slim 4**: mais leve, mas exigiria montar validação, ORM e testes manualmente.
- **Symfony 7**: excelente arquitetura, mas verbosidade maior para o escopo.
- **Laravel Lumen**: descontinuado.
````

---

## ADR-002: Padrão de Arquitetura — Repository + Service Layer

````markdown
# ADR-002: Repository Pattern + Service Layer

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

A lógica de cálculo indexado é o núcleo do sistema. Precisamos isolá-la para:
- Facilitar testes unitários (mock de repositórios).
- Permitir trocar a origem dos dados no futuro (API OMIE real, cache).
- Manter controllers finos.

## Decisão

Adotar:
- **Repositories** para acesso a dados (`ConsumptionRepositoryInterface`, `PriceRepositoryInterface`).
- **Services** para regras de negócio (`IndexedPriceCalculatorService`, `FormulaEvaluator`).
- **Controllers** finos (apenas orquestração).

## Consequências

**Positivas:**
- Alta testabilidade.
- Baixo acoplamento entre camadas.
- Facilita refatorações futuras.

**Negativas:**
- Mais arquivos e boilerplate.
- Pode parecer overengineering para escopo tão pequeno.

## Alternativas Consideradas

- **Active Record puro** (só Eloquent): simples, mas mistura acesso a dados com regras.
- **Actions (Spatie style)**: mais moderno, mas com menos ganho para este caso.
````

---

## ADR-003: Estratégia de Autenticação — Sem Autenticação

````markdown
# ADR-003: API pública sem autenticação

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

O PDF não menciona requisitos de autenticação. O escopo é apenas cálculo e leitura de dados de teste.

## Decisão

Não implementar autenticação. Todos os endpoints são públicos.

## Consequências

**Positivas:**
- Simplicidade de implementação e deploy.
- Facilita testes pelo avaliador.

**Negativas:**
- Não seria adequado para produção real.
- Requer rate limiting básico para evitar abuso.

## Alternativas Consideradas

- **Sanctum**: padrão do Laravel para SPAs, mas sem requisito.
- **API Key simples**: adicionaria fricção sem benefício claro.

## Nota

Deixamos a estrutura pronta para adicionar Sanctum em minutos, caso o avaliador solicite.
````

---

## ADR-004: Estratégia de Testes — Pest PHP

````markdown
# ADR-004: Pest PHP como framework de testes

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

Precisamos de framework de testes moderno, expressivo e produtivo.

## Decisão

Usar **Pest PHP 3.x** (sobre PHPUnit por baixo).

## Consequências

**Positivas:**
- Sintaxe expressiva estilo Jest/RSpec.
- Datasets nativos para testes parametrizados.
- Higher-order testing.
- Cobertura integrada.

**Negativas:**
- Um pouco menos familiar para devs vindos de PHPUnit puro.

## Alternativas Consideradas

- **PHPUnit puro**: mais verboso.
- **Codeception**: mais complexo, orientado a testes end-to-end.
````

---

## ADR-005: Containerização — Docker Compose

````markdown
# ADR-005: Docker Compose para desenvolvimento e deploy

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

O PDF exige deploy público e reprodutibilidade. Docker garante ambiente consistente.

## Decisão

Docker Compose com três serviços:
- `app` (PHP-FPM 8.3)
- `nginx` (reverse proxy)
- `mysql` (MySQL 8)

Dockerfile multi-stage para app.

## Consequências

**Positivas:**
- Setup em uma máquina nova em minutos.
- Paridade dev/prod.
- Simplifica CI/CD.

**Negativas:**
- Curva de aprendizado para quem não usa Docker.
- Overhead de recursos em desenvolvimento.

## Alternativas Consideradas

- **Laravel Sail**: excelente, mas queremos controle total do Dockerfile.
- **Herd + MySQL local**: rápido, mas quebra portabilidade.
````

---

## ADR-006: Versionamento da API — Path-based `/api/v1/`

````markdown
# ADR-006: Versionamento via URL path

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

O PDF não versiona a API, mas versionamento é boa prática desde o dia 1.

## Decisão

Prefixar todas as rotas com `/api/v1/`. Manter também um alias `/calculate` para compatibilidade estrita com o PDF, se necessário.

## Consequências

**Positivas:**
- Facilita evolução sem quebrar clientes.
- Convenção clara e amplamente adotada.

**Negativas:**
- URLs mais longas.
- Overhead mínimo de gerenciamento de versões.

## Alternativas Consideradas

- **Header-based** (`Accept: application/vnd.api.v1+json`): mais RESTful, menos descobrível.
- **Sem versionamento**: risco alto para clientes.
````

---

## ADR-007: Tratamento de Erros e Respostas Padronizadas

````markdown
# ADR-007: Formato padronizado de erros

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

O PDF define quatro códigos (200, 400, 404, 500) mas não especifica o formato do payload de erro.

## Decisão

Adotar formato consistente:

```json
{
  "message": "Descrição amigável",
  "error_code": "MACHINE_READABLE_CODE",
  "errors": { /* opcional: erros de validação por campo */ },
  "details": { /* opcional: contexto adicional */ }
}
```

Códigos padronizados via enum `ErrorCode`:
- `VALIDATION_FAILED` (400)
- `INVALID_FORMULA` (400)
- `DATA_NOT_FOUND` (404)
- `ZERO_CONSUMPTION` (500)
- `CALCULATION_ERROR` (500)
- `INTERNAL_ERROR` (500)

Handler central em `app/Exceptions/Handler.php` (ou `bootstrap/app.php` no Laravel 11).

## Consequências

**Positivas:**
- Clientes conseguem tratar erros programaticamente via `error_code`.
- Mensagens amigáveis para debug.
- Consistência em toda a API.

**Negativas:**
- Um pouco mais de código para manter.

## Alternativas Consideradas

- **Formato Laravel default**: inconsistente entre 422 e 500.
- **RFC 7807 (Problem Details)**: excelente, mas overkill para o escopo.
````

---

## ADR-008: Avaliação de Fórmula — `symfony/expression-language`

````markdown
# ADR-008: Uso de symfony/expression-language para avaliar a fórmula

- **Status:** Accepted
- **Data:** 2025-11-01

## Contexto

A fórmula é fornecida pelo usuário como string. Precisamos avaliá-la sem introduzir vulnerabilidades de execução de código (RCE).

## Decisão

Usar `symfony/expression-language`:
1. Substituir `[OMIE_MD]` por `omie_md` (variável válida).
2. Passar `omie_md` como variável no contexto.
3. Confiar no parser da lib (não usa `eval`).

## Consequências

**Positivas:**
- Zero risco de RCE.
- Sintaxe matemática rica (`+ - * / ( )`, operadores lógicos, etc.).
- Mantida por Symfony (alta qualidade).

**Negativas:**
- Dependência externa.
- Sintaxe pode divergir sutilmente do PHP (aceitável para expressões matemáticas simples).

## Alternativas Consideradas

- **Parser próprio (shunting yard)**: total controle, alto custo de implementação e testes.
- **`eval()` do PHP**: 🚫 nunca — RCE trivial.
- **`nikic/php-parser`**: excelente, mas overkill.
````

---

# 5. Docker & Infraestrutura

## 5.1. Dockerfile (multi-stage)

```dockerfile
# ═══════════════════════════════════════════════════════════════
# Stage 1: Composer dependencies
# ═══════════════════════════════════════════════════════════════
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader

# ═══════════════════════════════════════════════════════════════
# Stage 2: PHP-FPM runtime
# ═══════════════════════════════════════════════════════════════
FROM php:8.3-fpm-alpine AS runtime

# Instalar dependências do sistema e extensões PHP
RUN apk add --no-cache \
        bash \
        git \
        curl \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        mysql-client \
        linux-headers \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        opcache \
        bcmath \
    && rm -rf /var/cache/apk/*

# Criar usuário não-root
RUN addgroup -g 1000 -S laravel \
    && adduser -u 1000 -S laravel -G laravel

WORKDIR /var/www/html

# Copiar dependências do stage anterior
COPY --from=vendor /app/vendor ./vendor

# Copiar aplicação
COPY --chown=laravel:laravel . .

# Copiar config PHP customizada
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Permissões
RUN chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
```

## 5.2. docker-compose.yml

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: prueba-energia-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - prueba-energia
    environment:
      - DB_HOST=mysql
      - DB_PORT=3306

  nginx:
    image: nginx:1.27-alpine
    container_name: prueba-energia-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www/html:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
    networks:
      - prueba-energia

  mysql:
    image: mysql:8.0
    container_name: prueba-energia-mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-prueba_energia}
      MYSQL_USER: ${DB_USERNAME:-laravel}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root_secret}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - prueba-energia

networks:
  prueba-energia:
    driver: bridge

volumes:
  mysql_data:
    driver: local
```

## 5.3. `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 5.4. `.env.example`

```dotenv
APP_NAME=Prueba-Energia
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Europe/Madrid
APP_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=prueba_energia
DB_USERNAME=laravel
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root_secret

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

CORS_ALLOWED_ORIGINS=http://localhost:5173
```

## 5.5. `.dockerignore`

```
.git
.github
.env
.env.*
!.env.example
node_modules
vendor
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests/coverage
*.md
!README.md
docker-compose.override.yml
```

## 5.6. `Makefile`

```makefile
.PHONY: help up down build restart shell logs install key migrate fresh seed test test-coverage pint phpstan

help:
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Sobe os containers
	docker compose up -d --build

down: ## Derruba os containers
	docker compose down

build: ## Rebuild dos containers
	docker compose build --no-cache

restart: down up ## Reinicia os containers

shell: ## Abre shell no container app
	docker compose exec app bash

logs: ## Tail dos logs
	docker compose logs -f

install: ## Instala dependências Composer
	docker compose exec app composer install

key: ## Gera APP_KEY
	docker compose exec app php artisan key:generate

migrate: ## Roda migrations
	docker compose exec app php artisan migrate

fresh: ## Recria banco e roda seeders
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Roda seeders
	docker compose exec app php artisan db:seed

test: ## Roda testes
	docker compose exec app ./vendor/bin/pest

test-coverage: ## Roda testes com cobertura
	docker compose exec app ./vendor/bin/pest --coverage --min=85

pint: ## Formatação com Laravel Pint
	docker compose exec app ./vendor/bin/pint

phpstan: ## Análise estática
	docker compose exec app ./vendor/bin/phpstan analyse

artisan: ## Executa comando artisan (uso: make artisan cmd="route:list")
	docker compose exec app php artisan $(cmd)

tinker: ## Abre Tinker
	docker compose exec app php artisan tinker
```

---

# 6. Estrutura de Código Sugerida

```
prueba-energia-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── CalculateController.php
│   │   │           ├── ConsumptionController.php
│   │   │           ├── PriceController.php
│   │   │           └── HealthController.php
│   │   ├── Requests/
│   │   │   └── V1/
│   │   │       ├── CalculateRequest.php
│   │   │       └── DateRangeRequest.php
│   │   ├── Resources/
│   │   │   └── V1/
│   │   │       ├── CalculateResource.php
│   │   │       ├── ConsumptionResource.php
│   │   │       └── PriceResource.php
│   │   └── Middleware/
│   │       └── ForceJsonResponse.php
│   ├── Models/
│   │   ├── Consumption.php
│   │   └── Price.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── ConsumptionRepositoryInterface.php
│   │   │   └── PriceRepositoryInterface.php
│   │   └── Eloquent/
│   │       ├── EloquentConsumptionRepository.php
│   │       └── EloquentPriceRepository.php
│   ├── Services/
│   │   ├── Formula/
│   │   │   ├── FormulaEvaluator.php
│   │   │   └── FormulaValidator.php
│   │   └── IndexedPrice/
│   │       ├── IndexedPriceCalculatorService.php
│   │       └── DTOs/
│   │           ├── CalculateInput.php
│   │           └── CalculateResult.php
│   ├── Exceptions/
│   │   ├── Api/
│   │   │   ├── DataNotFoundException.php
│   │   │   ├── InvalidFormulaException.php
│   │   │   └── ZeroConsumptionException.php
│   │   └── Handler.php (se Laravel 10) ou bootstrap/app.php (Laravel 11)
│   ├── Enums/
│   │   └── ErrorCode.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 2025_11_01_000001_create_consumptions_table.php
│   │   └── 2025_11_01_000002_create_prices_table.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── ConsumptionSeeder.php
│   │   └── PriceSeeder.php
│   └── factories/
│       ├── ConsumptionFactory.php
│       └── PriceFactory.php
├── routes/
│   ├── api.php
│   └── console.php
├── tests/
│   ├── Feature/
│   │   ├── CalculateEndpointTest.php
│   │   ├── ConsumptionEndpointTest.php
│   │   └── PriceEndpointTest.php
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── FormulaEvaluatorTest.php
│   │   │   ├── FormulaValidatorTest.php
│   │   │   └── IndexedPriceCalculatorServiceTest.php
│   │   └── Repositories/
│   │       ├── ConsumptionRepositoryTest.php
│   │       └── PriceRepositoryTest.php
│   ├── Pest.php
│   └── TestCase.php
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   ├── php.ini
│   │   └── php-fpm.conf
│   └── mysql/
│       └── init.sql
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── .env.example
├── .dockerignore
├── composer.json
├── phpunit.xml
├── pint.json
├── phpstan.neon
└── README.md
```

## 6.1. Exemplos de Código-Chave

### `routes/api.php`

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CalculateController;
use App\Http\Controllers\Api\V1\ConsumptionController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/calculate', [CalculateController::class, 'store']);
    Route::get('/consumptions', [ConsumptionController::class, 'index']);
    Route::get('/prices', [PriceController::class, 'index']);
    Route::get('/health', HealthController::class);
});

// Alias direto conforme PDF (opcional)
Route::post('/calculate', [CalculateController::class, 'store']);
```

### `app/Http/Controllers/Api/V1/CalculateController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CalculateRequest;
use App\Http\Resources\V1\CalculateResource;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\IndexedPriceCalculatorService;
use Illuminate\Http\JsonResponse;

final class CalculateController extends Controller
{
    public function __construct(
        private readonly IndexedPriceCalculatorService $calculator,
    ) {}

    public function store(CalculateRequest $request): JsonResponse
    {
        $input = new CalculateInput(
            startDate: $request->getStartDate(),
            endDate: $request->getEndDate(),
            formula: $request->getFormula(),
        );

        $result = $this->calculator->calculate($input);

        return CalculateResource::make($result)
            ->response()
            ->setStatusCode(200);
    }
}
```

### `app/Http/Requests/V1/CalculateRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class CalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'formula'    => ['required', 'string', 'min:1', 'max:500', 'regex:/\[OMIE_MD\]/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'formula.regex' => 'The formula field must contain the [OMIE_MD] segment.',
        ];
    }

    public function getStartDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->string('start_date')->value())
            ->startOfDay();
    }

    public function getEndDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->string('end_date')->value())
            ->startOfDay();
    }

    public function getFormula(): string
    {
        return $this->string('formula')->value();
    }
}
```

### `app/Services/IndexedPrice/IndexedPriceCalculatorService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\IndexedPrice;

use App\Exceptions\Api\DataNotFoundException;
use App\Exceptions\Api\ZeroConsumptionException;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Services\Formula\FormulaEvaluator;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\DTOs\CalculateResult;

final class IndexedPriceCalculatorService
{
    private const HOURS = 25;

    public function __construct(
        private readonly ConsumptionRepositoryInterface $consumptions,
        private readonly PriceRepositoryInterface $prices,
        private readonly FormulaEvaluator $evaluator,
    ) {}

    public function calculate(CalculateInput $input): CalculateResult
    {
        $consumptions = $this->consumptions->getBetween($input->startDate, $input->endDate);
        $prices = $this->prices->getBetween($input->startDate, $input->endDate);

        $this->assertAllDatesPresent($input, $consumptions, $prices);

        $sumImportes = 0.0;
        $sumConsumos = 0.0;

        foreach ($consumptions as $date => $consumption) {
            $price = $prices[$date];

            for ($h = 1; $h <= self::HOURS; $h++) {
                $consumoHora = (float) ($consumption["h{$h}"] ?? 0);
                $precioHora  = (float) ($price["h{$h}"] ?? 0);

                if ($consumoHora === 0.0) {
                    continue;
                }

                $importeHora = $this->evaluator->evaluate($input->formula, $precioHora) * $consumoHora;

                $sumImportes += $importeHora;
                $sumConsumos += $consumoHora;
            }
        }

        if ($sumConsumos === 0.0) {
            throw new ZeroConsumptionException();
        }

        return new CalculateResult(
            priceIndexed: round($sumImportes / $sumConsumos, 4),
            startDate: $input->startDate,
            endDate: $input->endDate,
            formula: $input->formula,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $consumptions
     * @param array<string, array<string, mixed>> $prices
     */
    private function assertAllDatesPresent(
        CalculateInput $input,
        array $consumptions,
        array $prices,
    ): void {
        $missing = [];

        for ($date = $input->startDate; $date->lte($input->endDate); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            if (!isset($consumptions[$key]) || !isset($prices[$key])) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new DataNotFoundException($missing);
        }
    }
}
```

### `app/Services/Formula/FormulaEvaluator.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Formula;

use App\Exceptions\Api\InvalidFormulaException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class FormulaEvaluator
{
    private const PLACEHOLDER = '[OMIE_MD]';
    private const VARIABLE    = 'omie_md';

    public function __construct(
        private readonly ExpressionLanguage $expression = new ExpressionLanguage(),
    ) {}

    public function evaluate(string $formula, float $omieMdValue): float
    {
        $normalized = str_replace(self::PLACEHOLDER, self::VARIABLE, $formula);

        try {
            $result = $this->expression->evaluate($normalized, [
                self::VARIABLE => $omieMdValue,
            ]);
        } catch (SyntaxError $e) {
            throw new InvalidFormulaException($e->getMessage(), previous: $e);
        }

        if (!is_numeric($result)) {
            throw new InvalidFormulaException('Formula did not evaluate to a numeric value.');
        }

        return (float) $result;
    }
}
```

### Migration `create_consumptions_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consumptions', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();

            for ($h = 1; $h <= 25; $h++) {
                $table->double("h{$h}")->nullable();
            }

            $table->timestamps();

            $table->index('date', 'idx_consumptions_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumptions');
    }
};
```

---

# 7. Plano de Testes (Pest PHP)

## 7.1. Lista de Testes Unitários

### `tests/Unit/Services/FormulaEvaluatorTest.php`
- Avalia fórmula simples com um número.
- Avalia fórmula com múltiplas operações.
- Lança `InvalidFormulaException` para sintaxe inválida.
- Lança `InvalidFormulaException` para resultado não-numérico.
- Substitui corretamente `[OMIE_MD]`.

### `tests/Unit/Services/FormulaValidatorTest.php`
- Detecta ausência de `[OMIE_MD]`.
- Detecta caracteres não permitidos.

### `tests/Unit/Services/IndexedPriceCalculatorServiceTest.php`
- Calcula corretamente para 1 dia, 1 hora.
- Calcula corretamente para 1 dia, 24 horas.
- Trata `NULL` como zero.
- Lança `DataNotFoundException` quando falta dia.
- Lança `ZeroConsumptionException` quando soma de consumos = 0.
- Ignora horas com consumo zero.

### `tests/Unit/Repositories/ConsumptionRepositoryTest.php`
- Retorna consumos indexados por data (string).
- Retorna array vazio quando não há dados.
- Filtra corretamente por intervalo.

## 7.2. Lista de Testes de Feature

### `tests/Feature/CalculateEndpointTest.php`
- 200 com cálculo correto.
- 400 sem `start_date`.
- 400 sem `end_date`.
- 400 sem `formula`.
- 400 com formula sem `[OMIE_MD]`.
- 400 com `end_date` anterior a `start_date`.
- 400 com formula sintaticamente inválida.
- 404 com dias faltando.
- 500 com consumo total zero.

### `tests/Feature/ConsumptionEndpointTest.php`
- 200 lista paginada.
- 200 com filtro de range.
- 200 vazio quando sem dados.

### `tests/Feature/PriceEndpointTest.php`
- Análogo ao de consumptions.

## 7.3. Exemplos de Código

### Exemplo 1: Feature — Sucesso no `/calculate`

```php
<?php

declare(strict_types=1);

use App\Models\Consumption;
use App\Models\Price;

it('calcula preço indexado corretamente para 1 dia', function () {
    Consumption::create([
        'date' => '2025-03-01',
        'h1'   => 5.0,
        'h2'   => 3.0,
        'h3'   => 2.0,
    ]);

    Price::create([
        'date' => '2025-03-01',
        'h1'   => 0.10,
        'h2'   => 0.20,
        'h3'   => 0.15,
    ]);

    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date'   => '2025-03-01',
        'formula'    => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    // Cálculo esperado:
    // h1: (0.10*0.6 + 0.88) * 5 = 0.94 * 5 = 4.70
    // h2: (0.20*0.6 + 0.88) * 3 = 1.00 * 3 = 3.00
    // h3: (0.15*0.6 + 0.88) * 2 = 0.97 * 2 = 1.94
    // soma_importes = 9.64
    // soma_consumos = 10
    // precio_indexado = 0.964

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.price_indexed', 0.964);
});
```

### Exemplo 2: Feature — Erro 400 por fórmula inválida

```php
<?php

declare(strict_types=1);

it('retorna 400 quando formula não contém [OMIE_MD]', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date'   => '2025-03-31',
        'formula'    => '0.5 + 0.1',
    ]);

    $response
        ->assertStatus(400)
        ->assertJsonPath('errors.formula.0', 'The formula field must contain the [OMIE_MD] segment.');
});

it('retorna 400 quando falta start_date', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'end_date' => '2025-03-31',
        'formula'  => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    $response
        ->assertStatus(400)
        ->assertJsonValidationErrors(['start_date']);
});
```

### Exemplo 3: Unit — `FormulaEvaluator`

```php
<?php

declare(strict_types=1);

use App\Exceptions\Api\InvalidFormulaException;
use App\Services\Formula\FormulaEvaluator;

it('avalia fórmula com um valor de OMIE_MD', function () {
    $evaluator = new FormulaEvaluator();

    $result = $evaluator->evaluate('([OMIE_MD] * 0.6) + 0.88', 0.10);

    expect($result)->toBe(0.94);
});

it('lança InvalidFormulaException para sintaxe inválida', function () {
    $evaluator = new FormulaEvaluator();

    $evaluator->evaluate('([OMIE_MD] * 0.6', 0.10);
})->throws(InvalidFormulaException::class);

it('resolve corretamente precedência de operadores', function () {
    $evaluator = new FormulaEvaluator();

    // 2 + 3 * 4 = 14, não 20
    $result = $evaluator->evaluate('[OMIE_MD] + 3 * 4', 2);

    expect($result)->toBe(14.0);
});
```

### Exemplo 4: Unit — `IndexedPriceCalculatorService` com mocks

```php
<?php

declare(strict_types=1);

use App\Exceptions\Api\DataNotFoundException;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Services\Formula\FormulaEvaluator;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\IndexedPriceCalculatorService;
use Carbon\CarbonImmutable;

it('lança DataNotFoundException quando falta dia', function () {
    $consumptionRepo = Mockery::mock(ConsumptionRepositoryInterface::class);
    $priceRepo       = Mockery::mock(PriceRepositoryInterface::class);
    $evaluator       = new FormulaEvaluator();

    $consumptionRepo->shouldReceive('getBetween')->andReturn([
        '2025-03-01' => ['h1' => 5.0],
    ]);
    $priceRepo->shouldReceive('getBetween')->andReturn([
        '2025-03-01' => ['h1' => 0.10],
        // 2025-03-02 ausente!
    ]);

    $service = new IndexedPriceCalculatorService($consumptionRepo, $priceRepo, $evaluator);

    $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate:   CarbonImmutable::parse('2025-03-02'),
        formula:   '[OMIE_MD]',
    ));
})->throws(DataNotFoundException::class);
```

### 7.4. Configuração `tests/Pest.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeApproximately', function (float $expected, float $tolerance = 0.0001) {
    return $this->toBeGreaterThanOrEqual($expected - $tolerance)
                ->toBeLessThanOrEqual($expected + $tolerance);
});
```

---

✅ **PARTE 1 concluída.** Pronto para gerar a **PARTE 2 — Guia Frontend (Vue.js SPA)** quando solicitado.
