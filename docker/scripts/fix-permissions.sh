#!/bin/sh

# Script para corrigir permissões do storage e bootstrap/cache
# Útil quando há problemas de permissão no Docker

echo "🔐 Ajustando permissões do Laravel..."

# Criar diretórios se não existirem
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Ajustar ownership
chown -R laravel:laravel /var/www/html/storage /var/www/html/bootstrap/cache

# Ajustar permissões
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Criar arquivo de log se não existir
touch /var/www/html/storage/logs/laravel.log
chown laravel:laravel /var/www/html/storage/logs/laravel.log
chmod 664 /var/www/html/storage/logs/laravel.log

echo "✅ Permissões ajustadas com sucesso!"

