# 🚀 Quick Start - Docker

## Início Rápido

### 1. Configurar ambiente
```bash
cp .env.example .env
# Edite .env e ajuste as variáveis se necessário
```

### 2. Iniciar containers
```bash
# Usando Make (recomendado)
make build
make up

# Ou usando docker-compose
docker-compose build
docker-compose up -d
```

### 3. Instalar dependências e executar migrations
```bash
make install
# ou
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 4. Acessar aplicação
🌐 **http://localhost**

## Comandos Essenciais

```bash
make up          # Iniciar containers
make down        # Parar containers
make logs        # Ver logs
make shell       # Acessar shell do container
make migrate     # Executar migrations
make test        # Executar testes
```

## Estrutura

- **app** (PHP-FPM): Container da aplicação Laravel
- **nginx**: Servidor web e reverse proxy
- **postgres**: Banco de dados PostgreSQL

## Documentação Completa

Consulte [README-DOCKER.md](README-DOCKER.md) para documentação completa.

