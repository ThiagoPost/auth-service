#!/bin/sh

# Script para executar comandos artisan no container
# Uso: ./docker/scripts/artisan.sh migrate
# ou: docker-compose exec app php artisan migrate

if [ -z "$1" ]; then
    echo "❌ Erro: Forneça um comando artisan"
    echo "Uso: ./docker/scripts/artisan.sh <comando>"
    echo "Exemplo: ./docker/scripts/artisan.sh migrate"
    exit 1
fi

# Verificar se está rodando via docker-compose
if command -v docker-compose >/dev/null 2>&1; then
    docker-compose exec app php artisan "$@"
elif command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    docker compose exec app php artisan "$@"
else
    echo "❌ Docker Compose não encontrado"
    exit 1
fi

