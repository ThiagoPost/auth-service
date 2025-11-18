#!/bin/sh

# Entrypoint script para o container PHP-FPM
# Executa inicialização automática na primeira inicialização

set -e

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
until php -r "try { \$pdo = new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-laravel_auth}', '${DB_USERNAME:-laravel_user}', '${DB_PASSWORD:-secret}'); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "   PostgreSQL ainda não está pronto, aguardando..."
    sleep 2
done

echo "✅ PostgreSQL está pronto!"

# Executar inicialização se necessário
if [ ! -f "/var/www/html/.docker-initialized" ]; then
    echo "🚀 Executando inicialização..."
    
    # Instalar dependências se necessário
    if [ ! -d "/var/www/html/vendor" ]; then
        echo "📦 Instalando dependências do Composer..."
        composer install --optimize-autoloader --no-interaction
    fi
    
    # Gerar chave se não existir
    if [ ! -f "/var/www/html/.env" ]; then
        echo "📝 Criando arquivo .env..."
        if [ -f "/var/www/html/.env.example" ]; then
            cp /var/www/html/.env.example /var/www/html/.env
        fi
    fi
    
    # Executar migrations
    if php artisan migrate:status >/dev/null 2>&1; then
        echo "🗄️  Executando migrations..."
        php artisan migrate --force || true
    fi
    
    # Marcar como inicializado
    touch /var/www/html/.docker-initialized
    echo "✅ Inicialização concluída!"
fi

# Executar comando padrão
exec "$@"

