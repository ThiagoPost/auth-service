# 📚 Documentação da API - Serviço de Autenticação

## 🌐 Acessar a Documentação

A documentação interativa da API está disponível em:

**URL:** `http://localhost/docs/api`

A documentação é gerada automaticamente usando [Laravel Scramble](https://scramble.dedoc.co/) e exibida com interface moderna e interativa.

## 🚀 Como Usar a Documentação

### 1. Navegação

- **Menu Lateral**: Navegue pelos grupos de endpoints (Autenticação, Perfil, Recuperação de Senha)
- **Busca**: Use a busca para encontrar endpoints específicos
- **Filtros**: Filtre por tags/grupos para encontrar endpoints relacionados

### 2. Testar Endpoints (Try It Out)

A documentação permite testar endpoints diretamente na interface:

1. **Selecione um endpoint** na lista
2. **Clique em "Try It Out"**
3. **Preencha os parâmetros** necessários
4. **Clique em "Execute"** para enviar a requisição
5. **Veja a resposta** em tempo real

### 3. Autenticação nos Testes

Para testar endpoints protegidos:

1. **Faça login primeiro** usando o endpoint `/api/auth/login`
2. **Copie o token** retornado na resposta
3. **Clique no botão "Authorize"** no topo da página
4. **Cole o token** no campo "Bearer Token"
5. **Agora você pode testar** todos os endpoints protegidos

**Formato do Token:**
```
Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## 📋 Grupos de Endpoints

### 🔐 Autenticação

Endpoints para registro, login e logout:

- `POST /api/auth/register` - Registrar novo usuário
- `POST /api/auth/login` - Fazer login
- `POST /api/auth/logout` - Fazer logout (requer autenticação)
- `POST /api/auth/refresh` - Renovar token (requer autenticação)

### 👤 Perfil

Endpoints para gestão de perfil do usuário:

- `GET /api/auth/me` - Obter dados do usuário (requer autenticação)
- `PUT /api/auth/profile` - Atualizar perfil (requer autenticação)
- `POST /api/auth/password/change` - Alterar senha (requer autenticação)

### 🔑 Recuperação de Senha

Endpoints para recuperação de senha:

- `POST /api/auth/password/forgot` - Solicitar reset de senha
- `POST /api/auth/password/validate-token` - Validar token de recuperação
- `POST /api/auth/password/reset` - Redefinir senha com token

### Validação de Tokens (para outros serviços)

- `GET /api/auth/validate` - Validar token e retornar dados completos
- `GET /api/auth/user` - Validar token e retornar apenas dados do usuário

## 🔒 Autenticação

### Como Obter um Token

1. Faça uma requisição POST para `/api/auth/login` com email e senha
2. A resposta incluirá um token no formato:
   ```json
   {
     "success": true,
     "data": {
       "token": "1|abc123def456..."
     }
   }
   ```

### Como Usar o Token

Inclua o token no header `Authorization` de todas as requisições protegidas:

```http
Authorization: Bearer 1|abc123def456...
```

### Validade do Token

- Tokens expiram após **24 horas** de inatividade
- Use o endpoint `/api/auth/refresh` para renovar o token antes de expirar

## 📝 Formato de Resposta Padrão

Todas as respostas seguem o formato:

```json
{
  "success": true|false,
  "message": "Mensagem descritiva",
  "data": {},
  "errors": []
}
```

### Exemplos de Resposta

**Sucesso:**
```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    }
  },
  "errors": []
}
```

**Erro:**
```json
{
  "success": false,
  "message": "Dados de validação inválidos",
  "data": null,
  "errors": {
    "email": ["O campo email é obrigatório."]
  }
}
```

## ⚡ Rate Limiting

A API possui rate limiting para proteger contra abuso:

| Endpoint | Limite |
|----------|--------|
| Login/Registro | 5 tentativas por minuto |
| Recuperação de Senha | 5 tentativas por minuto |
| Validação de Token | 10 tentativas por minuto |
| Outras rotas | 60 requisições por minuto |

**Resposta quando excedido:**
```json
{
  "message": "Too Many Attempts."
}
```

## 📖 Códigos de Status HTTP

| Código | Significado |
|--------|-------------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Requisição inválida |
| 401 | Não autenticado |
| 403 | Não autorizado |
| 404 | Não encontrado |
| 422 | Erro de validação |
| 429 | Rate limit excedido |
| 500 | Erro interno do servidor |

## 🔍 Validações

### Senha

A senha deve atender aos seguintes requisitos:
- Mínimo de 8 caracteres
- Pelo menos uma letra maiúscula
- Pelo menos uma letra minúscula
- Pelo menos um número
- Pelo menos um símbolo (opcional, mas recomendado)

### Email

- Deve ser um endereço de email válido
- Deve ser único no sistema (para registro)

## 💡 Exemplos de Uso

### cURL

**Login:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "Password123!"
  }'
```

**Obter Perfil (com autenticação):**
```bash
curl -X GET http://localhost/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abc123def456..."
```

### JavaScript (Fetch)

**Login:**
```javascript
const response = await fetch('http://localhost/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'Password123!'
  })
});

const data = await response.json();
const token = data.data.token;
```

**Obter Perfil:**
```javascript
const response = await fetch('http://localhost/api/auth/me', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
});

const profile = await response.json();
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost']);

// Login
$response = $client->post('/api/auth/login', [
    'json' => [
        'email' => 'user@example.com',
        'password' => 'Password123!'
    ]
]);

$data = json_decode($response->getBody(), true);
$token = $data['data']['token'];

// Obter Perfil
$response = $client->get('/api/auth/me', [
    'headers' => [
        'Authorization' => "Bearer {$token}"
    ]
]);

$profile = json_decode($response->getBody(), true);
```

## 🛠️ Exportar Documentação

### OpenAPI JSON

A especificação OpenAPI pode ser exportada em:

**URL:** `http://localhost/api.json`

Isso permite:
- Importar no Postman/Insomnia
- Usar com outras ferramentas de documentação
- Integrar com ferramentas de teste

### Postman Collection

Você pode importar a especificação OpenAPI no Postman:

1. Abra o Postman
2. Clique em "Import"
3. Selecione "Link"
4. Cole a URL: `http://localhost/api.json`
5. Clique em "Continue" e "Import"

## 🐛 Troubleshooting

### Erro 401 (Não Autenticado)

- Verifique se o token está no header `Authorization`
- Verifique se o token não expirou (tokens expiram em 24h)
- Faça login novamente para obter um novo token

### Erro 422 (Validação Falhou)

- Verifique se todos os campos obrigatórios foram enviados
- Verifique se os tipos de dados estão corretos
- Veja a seção `errors` na resposta para detalhes

### Erro 429 (Rate Limit)

- Aguarde 1 minuto antes de tentar novamente
- Reduza a frequência de requisições

### Documentação não carrega

- Verifique se os containers Docker estão rodando
- Verifique se a rota `/docs/api` está acessível
- Limpe o cache: `php artisan config:clear`

## 🔗 Integração com Outros Serviços

Este serviço de autenticação pode ser usado por outros microserviços para validar tokens.

### Endpoints de Validação

**Validar Token Completo:**
```bash
GET /api/auth/validate
Authorization: Bearer {token}
```

**Obter Dados do Usuário (Simplificado):**
```bash
GET /api/auth/user
Authorization: Bearer {token}
```

### Documentação de Integração

Para detalhes completos sobre como integrar este serviço em outros projetos, consulte:

- **[Guia de Integração](docs/INTEGRATION-GUIDE.md)** - Guia completo de integração
- **[Exemplos de Código](docs/EXAMPLES.md)** - Exemplos em PHP, Node.js, Python, Go

### Exemplo Rápido

```php
// Em outro serviço Laravel
$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get('http://auth-service/api/auth/user');

if ($response->json('success')) {
    $user = $response->json('data.user');
    // Token válido, prosseguir
}
```

## 📞 Suporte

Para dúvidas ou problemas:

1. Consulte a documentação interativa em `/docs/api`
2. Verifique os logs do Laravel
3. Consulte a [Documentação de Integração](docs/INTEGRATION-GUIDE.md)
4. Abra uma issue no repositório

## 🔄 Atualizações

A documentação é gerada automaticamente a partir do código. Sempre que você:

- Adicionar novos endpoints
- Modificar parâmetros
- Alterar respostas

A documentação será atualizada automaticamente ao acessar `/docs/api`.

---

**Última atualização:** Documentação gerada automaticamente pelo Laravel Scramble

