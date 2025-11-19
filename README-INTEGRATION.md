# 🔗 Guia Rápido de Integração

## Como Outros Serviços Validam Tokens

### Opção 1: Validação via API (Recomendado)

Outros serviços fazem uma requisição HTTP para este serviço de autenticação:

```bash
GET http://auth-service/api/auth/user
Authorization: Bearer {token}
```

**Resposta (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    }
  }
}
```

**Resposta (401):**
```json
{
  "success": false,
  "message": "Token inválido ou expirado."
}
```

### Opção 2: Endpoint Completo

Para obter informações completas do token:

```bash
GET http://auth-service/api/auth/validate
Authorization: Bearer {token}
```

Retorna dados do usuário + informações do token (expiração, abilities, etc).

## Exemplo Prático

### Laravel

```php
use Illuminate\Support\Facades\Http;

$token = $request->bearerToken();

$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get('http://auth-service/api/auth/user');

if ($response->json('success')) {
    $user = $response->json('data.user');
    // Token válido - prosseguir
} else {
    // Token inválido - retornar 401
    return response()->json(['error' => 'Unauthorized'], 401);
}
```

### Node.js/Express

```javascript
const axios = require('axios');

async function validateToken(token) {
    try {
        const response = await axios.get('http://auth-service/api/auth/user', {
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });
        
        return response.data.success ? response.data.data.user : null;
    } catch (error) {
        return null;
    }
}
```

## Documentação Completa

- **[Guia Completo](docs/INTEGRATION-GUIDE.md)** - Documentação detalhada
- **[Exemplos](docs/EXAMPLES.md)** - Exemplos em múltiplas linguagens

## Configuração

Configure a URL do serviço de autenticação:

```env
AUTH_SERVICE_URL=http://localhost
```

---

**Endpoints disponíveis:**
- `GET /api/auth/validate` - Validação completa
- `GET /api/auth/user` - Validação simplificada

