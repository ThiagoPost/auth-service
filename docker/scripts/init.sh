#!/bin/sh

# Script de inicialização do projeto Laravel no Docker
# Executa composer install, migrations e configurações iniciais

set -e

echo "🚀 Inicializando projeto Laravel..."

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
until php artisan db:show --quiet 2>/dev/null; do
    echo "   PostgreSQL ainda não está pronto, aguardando..."
    sleep 2
done

echo "✅ PostgreSQL está pronto!"

# Instalar dependências do Composer (se necessário)
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    composer install --optimize-autoloader --no-interaction
fi

# Gerar chave da aplicação (se não existir)
if [ ! -f ".env" ]; then
    echo "📝 Criando arquivo .env..."
    cp .env.example .env
    php artisan key:generate
fi

# Executar migrations
echo "🗄️  Executando migrations..."
php artisan migrate --force

# Limpar e otimizar cache
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção (se APP_ENV=production)
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Otimizando para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Ajustar permissões
echo "🔐 Ajustando permissões..."
chmod -R 775 storage bootstrap/cache
chown -R laravel:laravel storage bootstrap/cache || true

echo "✅ Inicialização concluída!"

