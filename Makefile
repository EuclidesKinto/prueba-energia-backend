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
