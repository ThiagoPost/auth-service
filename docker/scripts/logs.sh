#!/bin/sh

# Script para visualizar logs dos containers
# Uso: ./docker/scripts/logs.sh [serviço]
# Exemplos:
#   ./docker/scripts/logs.sh          # Todos os logs
#   ./docker/scripts/logs.sh app      # Logs do PHP-FPM
#   ./docker/scripts/logs.sh nginx    # Logs do Nginx
#   ./docker/scripts/logs.sh postgres # Logs do PostgreSQL

if [ -z "$1" ]; then
    # Mostrar logs de todos os serviços
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose logs -f
    elif command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        docker compose logs -f
    else
        echo "❌ Docker Compose não encontrado"
        exit 1
    fi
else
    # Mostrar logs de um serviço específico
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose logs -f "$1"
    elif command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        docker compose logs -f "$1"
    else
        echo "❌ Docker Compose não encontrado"
        exit 1
    fi
fi

