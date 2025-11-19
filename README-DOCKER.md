# 🐳 Documentação Docker - Serviço de Autenticação Laravel

Este documento contém todas as informações necessárias para executar o serviço de autenticação Laravel usando Docker.

## 📋 Índice

- [Arquitetura](#arquitetura)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e Configuração](#instalação-e-configuração)
- [Comandos Úteis](#comandos-úteis)
- [Desenvolvimento](#desenvolvimento)
- [Produção](#produção)
- [Troubleshooting](#troubleshooting)
- [Estrutura de Arquivos](#estrutura-de-arquivos)

## 🏗️ Arquitetura

O projeto utiliza uma arquitetura de **3 containers**:

1. **PHP-FPM (app)**: Container com PHP 8.2-FPM executando a aplicação Laravel
2. **PostgreSQL (postgres)**: Banco de dados PostgreSQL 15
3. **Nginx (nginx)**: Servidor web e reverse proxy

```
┌─────────────┐
│   Nginx     │ (Porta 80/443)
│  (Web)      │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  PHP-FPM    │
│  (Laravel)  │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ PostgreSQL  │ (Porta 5432 interno)
│  (Database) │
└─────────────┘
```

## 📦 Pré-requisitos

- **Docker** >= 20.10
- **Docker Compose** >= 2.0
- **Git**
- **Make** (opcional, mas recomendado)

### Verificar instalação

```bash
docker --version
docker compose version
```

## 🚀 Instalação e Configuração

### 1. Clonar o repositório

```bash
git clone <repository-url>
cd servico-autenticacao
```

### 2. Configurar variáveis de ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` e ajuste as variáveis conforme necessário:

```env
# Database (usar nomes dos serviços Docker)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_auth
DB_USERNAME=laravel_user
DB_PASSWORD=secret

# Aplicação
APP_URL=http://localhost
APP_ENV=local
APP_DEBUG=true
```

### 3. Construir e iniciar containers

```bash
# Usando Make (recomendado)
make build
make up

# Ou usando docker-compose diretamente
docker-compose build
docker-compose up -d
```

### 4. Instalar dependências e executar migrations

```bash
# Usando Make
make install

# Ou manualmente
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 5. Acessar a aplicação

A aplicação estará disponível em: **http://localhost**

## 🛠️ Comandos Úteis

### Usando Makefile (Recomendado)

```bash
make help          # Ver todos os comandos disponíveis
make build         # Construir imagens
make up            # Iniciar containers
make down          # Parar containers
make restart       # Reiniciar containers
make logs          # Ver logs de todos os containers
make logs-app      # Ver logs do PHP-FPM
make logs-nginx    # Ver logs do Nginx
make logs-postgres # Ver logs do PostgreSQL
make shell         # Acessar shell do container PHP-FPM
make migrate       # Executar migrations
make test          # Executar testes
make clean         # Limpar e otimizar cache
```

### Usando Docker Compose

```bash
# Gerenciamento de containers
docker-compose up -d              # Iniciar em background
docker-compose down               # Parar e remover containers
docker-compose restart            # Reiniciar containers
docker-compose ps                 # Listar containers

# Logs
docker-compose logs -f            # Ver todos os logs
docker-compose logs -f app        # Logs do PHP-FPM
docker-compose logs -f nginx      # Logs do Nginx
docker-compose logs -f postgres   # Logs do PostgreSQL

# Executar comandos
docker-compose exec app sh        # Acessar shell
docker-compose exec app php artisan migrate
docker-compose exec app composer install
```

### Scripts auxiliares

```bash
# Inicializar projeto
docker-compose exec app sh docker/scripts/init.sh

# Executar comandos artisan
./docker/scripts/artisan.sh migrate
./docker/scripts/artisan.sh db:seed

# Ver logs
./docker/scripts/logs.sh
./docker/scripts/logs.sh app
```

## 💻 Desenvolvimento

### Workflow de desenvolvimento

1. **Iniciar containers**:
   ```bash
   make up
   ```

2. **Instalar dependências** (primeira vez):
   ```bash
   make install
   ```

3. **Desenvolver**: O código é montado como volume, então alterações são refletidas automaticamente

4. **Executar migrations**:
   ```bash
   make migrate
   ```

5. **Ver logs**:
   ```bash
   make logs-app
   ```

### Acessar containers

```bash
# Shell do PHP-FPM
make shell
# ou
docker-compose exec app sh

# Shell como root (para instalar pacotes)
make shell-root
# ou
docker-compose exec -u root app sh

# Acessar PostgreSQL
docker-compose exec postgres psql -U laravel_user -d laravel_auth
```

### Executar testes

```bash
make test
# ou
docker-compose exec app php artisan test
```

### Executar migrations e seeders

```bash
# Apenas migrations
make migrate

# Migrations + Seeders
make fresh

# Reverter última migration
make migrate-rollback
```

## 🚢 Produção

### Build para produção

```bash
# Construir imagens otimizadas
make build-prod

# Iniciar containers em produção
make up-prod
```

### Configurações de produção

1. **Ajustar `.env`**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://seu-dominio.com
   ```

2. **Configurar SSL**:
   - Descomente a seção SSL em `docker/nginx/default.conf`
   - Adicione certificados em `docker/nginx/ssl/`
   - Ajuste `docker-compose.prod.yml` para montar o volume SSL

3. **Otimizar**:
   ```bash
   docker-compose -f docker-compose.prod.yml exec app php artisan optimize
   ```

### Health Checks

Todos os containers possuem health checks configurados:

```bash
# Verificar status dos health checks
docker-compose ps
```

### Backup do banco de dados

```bash
# Backup
docker-compose exec postgres pg_dump -U laravel_user laravel_auth > backup.sql

# Restore
docker-compose exec -T postgres psql -U laravel_user laravel_auth < backup.sql
```

## 🔧 Troubleshooting

### Problemas comuns

#### 1. Porta já em uso

**Erro**: `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solução**: Altere a porta no `.env`:
```env
APP_PORT=8080
```

#### 2. Permissões de storage

**Erro**: `The stream or file "/var/www/html/storage/logs/laravel.log" could not be opened`

**Solução**:
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R laravel:laravel storage bootstrap/cache
```

#### 3. PostgreSQL não conecta

**Erro**: `SQLSTATE[08006] [7] could not connect to server`

**Solução**:
- Verifique se o container está rodando: `docker-compose ps`
- Verifique as variáveis de ambiente no `.env`
- Aguarde o health check: `docker-compose logs postgres`

#### 4. Composer install falha

**Solução**:
```bash
docker-compose exec app composer clear-cache
docker-compose exec app composer install --no-interaction
```

#### 5. Cache não limpa

**Solução**:
```bash
make clean
# ou
docker-compose exec app php artisan optimize:clear
```

### Verificar logs

```bash
# Logs do Laravel
docker-compose exec app tail -f storage/logs/laravel.log

# Logs do PHP-FPM
docker-compose logs -f app

# Logs do Nginx
docker-compose logs -f nginx

# Logs do PostgreSQL
docker-compose logs -f postgres
```

### Reconstruir containers

```bash
# Parar e remover containers
docker-compose down

# Remover volumes (CUIDADO: apaga dados do banco)
docker-compose down -v

# Reconstruir
docker-compose build --no-cache
docker-compose up -d
```

## 📁 Estrutura de Arquivos

```
project-root/
├── docker/
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   └── php-fpm.conf
│   ├── postgres/
│   │   └── init.sql
│   └── scripts/
│       ├── init.sh
│       ├── artisan.sh
│       └── logs.sh
├── docker-compose.yml          # Desenvolvimento
├── docker-compose.prod.yml     # Produção
├── .dockerignore
├── Makefile
└── README-DOCKER.md
```

## 🔒 Segurança

### Boas práticas

1. **Não commitar `.env`**: Já está no `.gitignore`
2. **Usar secrets em produção**: Configure variáveis de ambiente no servidor
3. **Não expor PostgreSQL**: Em produção, remova o mapeamento de porta
4. **SSL/TLS**: Configure certificados SSL em produção
5. **Firewall**: Configure firewall para permitir apenas portas necessárias

### Variáveis sensíveis

Nunca commite:
- `APP_KEY`
- `DB_PASSWORD`
- Credenciais de email
- Tokens de API

## 📊 Monitoramento

### Health Checks

Todos os serviços possuem health checks:

- **Nginx**: `http://localhost/up`
- **PHP-FPM**: Verifica processo PHP-FPM
- **PostgreSQL**: `pg_isready`

### Verificar status

```bash
docker-compose ps
```

## 🎯 Performance

### Otimizações aplicadas

- **PHP OPcache**: Habilitado e otimizado
- **Nginx**: Compressão gzip, cache de assets
- **PostgreSQL**: Configurações de performance otimizadas
- **Multi-stage builds**: Imagens menores

### Ajustar recursos

Edite `docker-compose.yml` para ajustar limites de recursos:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 512M
```

## 📞 Suporte

Para problemas ou dúvidas:

1. Verifique os logs: `make logs`
2. Consulte a seção [Troubleshooting](#troubleshooting)
3. Abra uma issue no repositório

## 📝 Changelog

- **v1.0.0**: Configuração inicial com 3 containers
- Suporte para desenvolvimento e produção
- Health checks configurados
- Scripts de automação

---

**Última atualização**: 2024

