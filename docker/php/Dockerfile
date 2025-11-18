# Multi-stage build para otimizar tamanho da imagem
FROM php:8.2-fpm-alpine AS base

# Instalar dependências do sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev

# Instalar extensões PHP necessárias para Laravel
# Nota: json, ctype, xml e tokenizer já vêm compiladas no PHP 8.2
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Instalar Redis extension (opcional, para cache)
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário não-root para executar a aplicação
RUN addgroup -g 1000 laravel && \
    adduser -D -s /bin/sh -u 1000 -G laravel laravel

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos de configuração PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Stage de produção
FROM base AS production

# Copiar código da aplicação
COPY --chown=laravel:laravel . /var/www/html

# Instalar dependências de produção (sem dev)
USER laravel
RUN composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

# Voltar para root para ajustar permissões
USER root

# Ajustar permissões
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Stage de desenvolvimento
FROM base AS development

# Copiar código da aplicação
COPY --chown=laravel:laravel . /var/www/html

# Instalar dependências (incluindo dev)
USER laravel
RUN composer install --optimize-autoloader --no-interaction --prefer-dist

# Voltar para root para ajustar permissões
USER root

# Ajustar permissões
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copiar entrypoint script
COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor porta do PHP-FPM
EXPOSE 9000

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Comando padrão
CMD ["php-fpm"]

