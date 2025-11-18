.PHONY: help build up down restart logs shell test migrate fresh seed install clean

# Variáveis
COMPOSE = docker-compose
COMPOSE_PROD = docker-compose -f docker-compose.prod.yml

help: ## Mostra esta mensagem de ajuda
	@echo "Comandos disponíveis:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construir as imagens Docker
	$(COMPOSE) build

up: ## Iniciar os containers
	$(COMPOSE) up -d
	@echo "✅ Containers iniciados!"
	@echo "🌐 Aplicação disponível em: http://localhost"

down: ## Parar os containers
	$(COMPOSE) down

restart: ## Reiniciar os containers
	$(COMPOSE) restart

logs: ## Ver logs de todos os containers
	$(COMPOSE) logs -f

logs-app: ## Ver logs do container PHP-FPM
	$(COMPOSE) logs -f app

logs-nginx: ## Ver logs do container Nginx
	$(COMPOSE) logs -f nginx

logs-postgres: ## Ver logs do container PostgreSQL
	$(COMPOSE) logs -f postgres

shell: ## Acessar shell do container PHP-FPM
	$(COMPOSE) exec app sh

shell-root: ## Acessar shell do container PHP-FPM como root
	$(COMPOSE) exec -u root app sh

test: ## Executar testes
	$(COMPOSE) exec app php artisan test

migrate: ## Executar migrations
	$(COMPOSE) exec app php artisan migrate

migrate-fresh: ## Executar migrations com fresh (apaga e recria)
	$(COMPOSE) exec app php artisan migrate:fresh

migrate-rollback: ## Reverter última migration
	$(COMPOSE) exec app php artisan migrate:rollback

seed: ## Executar seeders
	$(COMPOSE) exec app php artisan db:seed

fresh: ## Executar migrate:fresh + seed
	$(COMPOSE) exec app php artisan migrate:fresh --seed

install: ## Instalar dependências e configurar projeto
	$(COMPOSE) exec app sh docker/scripts/init.sh

clean: ## Limpar cache e otimizar
	$(COMPOSE) exec app php artisan optimize:clear
	$(COMPOSE) exec app php artisan config:cache
	$(COMPOSE) exec app php artisan route:cache
	$(COMPOSE) exec app php artisan view:cache

# Comandos de produção
build-prod: ## Construir imagens para produção
	$(COMPOSE_PROD) build

up-prod: ## Iniciar containers em produção
	$(COMPOSE_PROD) up -d

down-prod: ## Parar containers em produção
	$(COMPOSE_PROD) down

logs-prod: ## Ver logs em produção
	$(COMPOSE_PROD) logs -f

# Comandos de manutenção
ps: ## Listar containers em execução
	$(COMPOSE) ps

exec: ## Executar comando no container (uso: make exec CMD="php artisan tinker")
	$(COMPOSE) exec app $(CMD)

