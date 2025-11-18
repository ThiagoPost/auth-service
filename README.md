# Serviço de Autenticação Laravel com Sanctum

API RESTful completa para autenticação de usuários usando Laravel Sanctum. Este serviço fornece endpoints centralizados para registro, login, gestão de perfil e recuperação de senha.

## 📋 Requisitos

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 12.x

## 🚀 Instalação

1. Clone o repositório:
```bash
git clone <repository-url>
cd servico-autenticacao
```

2. Instale as dependências:
```bash
composer install
```

3. Configure o arquivo `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure as variáveis de ambiente no `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=servico_autenticacao
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

5. Execute as migrations:
```bash
php artisan migrate
```

6. Inicie o servidor:
```bash
php artisan serve
```

## 📚 Endpoints da API

### Autenticação

#### POST `/api/auth/register`
Registra um novo usuário.

**Body:**
```json
{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```

**Resposta (201):**
```json
{
    "success": true,
    "message": "Usuário registrado com sucesso.",
    "data": {
        "user": {
            "id": 1,
            "name": "João Silva",
            "email": "joao@example.com"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    },
    "errors": []
}
```

#### POST `/api/auth/login`
Realiza login e retorna token de acesso.

**Body:**
```json
{
    "email": "joao@example.com",
    "password": "Password123!"
}
```

**Resposta (200):**
```json
{
    "success": true,
    "message": "Login realizado com sucesso.",
    "data": {
        "user": {
            "id": 1,
            "name": "João Silva",
            "email": "joao@example.com"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    },
    "errors": []
}
```

#### POST `/api/auth/logout`
Realiza logout revogando o token atual.

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta (200):**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso.",
    "data": null,
    "errors": []
}
```

#### POST `/api/auth/refresh`
Renova o token de acesso.

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta (200):**
```json
{
    "success": true,
    "message": "Token renovado com sucesso.",
    "data": {
        "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    },
    "errors": []
}
```

### Perfil

#### GET `/api/auth/me`
Retorna os dados do usuário autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

#### PUT `/api/auth/profile`
Atualiza o perfil do usuário.

**Headers:**
```
Authorization: Bearer {token}
```

**Body:**
```json
{
    "name": "João Santos",
    "email": "joao.santos@example.com"
}
```

#### POST `/api/auth/password/change`
Altera a senha do usuário autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Body:**
```json
{
    "current_password": "Password123!",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
```

### Recuperação de Senha

#### POST `/api/auth/password/forgot`
Solicita reset de senha enviando email com token.

**Body:**
```json
{
    "email": "joao@example.com"
}
```

#### POST `/api/auth/password/validate-token`
Valida se o token de recuperação é válido.

**Body:**
```json
{
    "email": "joao@example.com",
    "token": "token_aqui"
}
```

#### POST `/api/auth/password/reset`
Redefine a senha usando o token válido.

**Body:**
```json
{
    "email": "joao@example.com",
    "token": "token_aqui",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
```

## 🔒 Segurança

- **Rate Limiting**: 5 tentativas por minuto para login, registro e recuperação de senha
- **Validação de Senha**: Mínimo 8 caracteres, incluindo maiúsculas, minúsculas e números
- **Tokens**: Expiração de 24 horas de inatividade
- **Hash de Senhas**: Usando bcrypt
- **CORS**: Configurado para aceitar requisições de outras aplicações

## 🧪 Testes

Execute os testes com:

```bash
php artisan test
```

Ou para um arquivo específico:

```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

## 📦 Coleção Postman

Uma coleção completa do Postman está disponível no arquivo `postman_collection.json`. 

Para importar:
1. Abra o Postman
2. Clique em "Import"
3. Selecione o arquivo `postman_collection.json`
4. Configure a variável `base_url` (padrão: `http://localhost:8000`)

## 📁 Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Auth/
│   │           ├── LoginController.php
│   │           ├── RegisterController.php
│   │           ├── LogoutController.php
│   │           ├── ProfileController.php
│   │           └── PasswordResetController.php
│   ├── Requests/
│   │   └── Auth/
│   │       ├── LoginRequest.php
│   │       ├── RegisterRequest.php
│   │       ├── UpdateProfileRequest.php
│   │       ├── ChangePasswordRequest.php
│   │       ├── ForgotPasswordRequest.php
│   │       └── ResetPasswordRequest.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
└── Services/
    └── AuthService.php
```

## 🔧 Configuração

### Sanctum

A configuração do Sanctum está em `config/sanctum.php`. Tokens expiram após 24 horas (1440 minutos).

### CORS

O CORS está configurado no `bootstrap/app.php` para permitir requisições de outras aplicações.

### Rate Limiting

O rate limiting está configurado nas rotas:
- Login, Registro, Recuperação de Senha: 5 tentativas por minuto
- Validação de Token: 10 tentativas por minuto

## 📝 Padrão de Resposta

Todas as respostas seguem o formato:

```json
{
    "success": true|false,
    "message": "Mensagem descritiva",
    "data": {},
    "errors": []
}
```

## 🐛 Tratamento de Erros

O sistema possui tratamento global de erros que retorna respostas JSON padronizadas para:
- Erros de validação (422)
- Erros de autenticação (401)
- Endpoints não encontrados (404)
- Erros do servidor (500)

## 📄 Licença

Este projeto está sob a licença MIT.

## 👨‍💻 Desenvolvimento

Para contribuir com o projeto:

1. Faça um fork
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📞 Suporte

Para dúvidas ou problemas, abra uma issue no repositório.
