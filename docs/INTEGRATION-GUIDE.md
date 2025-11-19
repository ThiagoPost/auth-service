# 🔗 Guia de Integração - Validação de Tokens

Este guia explica como outros serviços podem validar tokens gerados por este serviço de autenticação.

## 📋 Visão Geral

Este serviço de autenticação gera tokens usando **Laravel Sanctum**. Os tokens são armazenados no banco de dados e podem ser validados por outros serviços através de endpoints dedicados.

## 🔐 Endpoints de Validação

### 1. Validar Token Completo

**Endpoint:** `GET /api/auth/validate`

**Descrição:** Valida o token e retorna dados completos do usuário e informações do token.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Token válido.",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "email_verified_at": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": {
      "id": 1,
      "name": "auth-token",
      "abilities": ["*"],
      "expires_at": "2024-01-02T00:00:00.000000Z",
      "last_used_at": null
    }
  },
  "errors": []
}
```

**Resposta de Erro (401):**
```json
{
  "success": false,
  "message": "Token inválido ou expirado.",
  "data": null,
  "errors": []
}
```

### 2. Obter Dados do Usuário (Simplificado)

**Endpoint:** `GET /api/auth/user`

**Descrição:** Versão simplificada que retorna apenas os dados do usuário.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Token válido.",
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

## 💻 Exemplos de Implementação

### PHP (Guzzle)

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AuthServiceClient
{
    private $client;
    private $authServiceUrl;

    public function __construct(string $authServiceUrl)
    {
        $this->authServiceUrl = rtrim($authServiceUrl, '/');
        $this->client = new Client([
            'base_uri' => $this->authServiceUrl,
            'timeout' => 5.0,
        ]);
    }

    /**
     * Valida um token e retorna dados do usuário
     */
    public function validateToken(string $token): ?array
    {
        try {
            $response = $this->client->get('/api/auth/validate', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success'] ?? false) {
                return $data['data'];
            }

            return null;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                // Token inválido ou expirado
                return null;
            }
            throw $e;
        }
    }

    /**
     * Obtém apenas dados do usuário (versão simplificada)
     */
    public function getUserFromToken(string $token): ?array
    {
        try {
            $response = $this->client->get('/api/auth/user', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success'] ?? false) {
                return $data['data']['user'] ?? null;
            }

            return null;
        } catch (ClientException $e) {
            return null;
        }
    }
}

// Uso
$authClient = new AuthServiceClient('http://localhost');
$userData = $authClient->validateToken($token);

if ($userData) {
    $userId = $userData['user']['id'];
    $userEmail = $userData['user']['email'];
    // Token válido, prosseguir com a requisição
} else {
    // Token inválido, retornar 401
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### JavaScript/Node.js (Axios)

```javascript
const axios = require('axios');

class AuthServiceClient {
    constructor(authServiceUrl) {
        this.baseURL = authServiceUrl.replace(/\/$/, '');
        this.client = axios.create({
            baseURL: this.baseURL,
            timeout: 5000,
        });
    }

    /**
     * Valida um token e retorna dados do usuário
     */
    async validateToken(token) {
        try {
            const response = await this.client.get('/api/auth/validate', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                },
            });

            if (response.data.success) {
                return response.data.data;
            }

            return null;
        } catch (error) {
            if (error.response?.status === 401) {
                return null; // Token inválido
            }
            throw error;
        }
    }

    /**
     * Obtém apenas dados do usuário (versão simplificada)
     */
    async getUserFromToken(token) {
        try {
            const response = await this.client.get('/api/auth/user', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                },
            });

            if (response.data.success) {
                return response.data.data.user;
            }

            return null;
        } catch (error) {
            return null;
        }
    }
}

// Uso
const authClient = new AuthServiceClient('http://localhost');

async function validateRequest(req, res, next) {
    const token = req.headers.authorization?.replace('Bearer ', '');

    if (!token) {
        return res.status(401).json({ error: 'Token não fornecido' });
    }

    const userData = await authClient.validateToken(token);

    if (!userData) {
        return res.status(401).json({ error: 'Token inválido ou expirado' });
    }

    // Adicionar dados do usuário à requisição
    req.user = userData.user;
    req.token = userData.token;

    next();
}
```

### Python (Requests)

```python
import requests
from typing import Optional, Dict

class AuthServiceClient:
    def __init__(self, auth_service_url: str):
        self.base_url = auth_service_url.rstrip('/')
        self.timeout = 5

    def validate_token(self, token: str) -> Optional[Dict]:
        """
        Valida um token e retorna dados do usuário
        """
        try:
            response = requests.get(
                f'{self.base_url}/api/auth/validate',
                headers={
                    'Authorization': f'Bearer {token}',
                    'Accept': 'application/json',
                },
                timeout=self.timeout
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    return data.get('data')
            
            return None
        except requests.exceptions.RequestException:
            return None

    def get_user_from_token(self, token: str) -> Optional[Dict]:
        """
        Obtém apenas dados do usuário (versão simplificada)
        """
        try:
            response = requests.get(
                f'{self.base_url}/api/auth/user',
                headers={
                    'Authorization': f'Bearer {token}',
                    'Accept': 'application/json',
                },
                timeout=self.timeout
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    return data.get('data', {}).get('user')
            
            return None
        except requests.exceptions.RequestException:
            return None

# Uso
auth_client = AuthServiceClient('http://localhost')

def validate_request(request):
    token = request.headers.get('Authorization', '').replace('Bearer ', '')
    
    if not token:
        return None, {'error': 'Token não fornecido'}, 401
    
    user_data = auth_client.validate_token(token)
    
    if not user_data:
        return None, {'error': 'Token inválido ou expirado'}, 401
    
    return user_data['user'], None, None
```

## 🛡️ Middleware para Outros Serviços

### Laravel (Middleware)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ValidateAuthToken
{
    private $authServiceUrl;

    public function __construct()
    {
        $this->authServiceUrl = config('services.auth_service_url', 'http://localhost');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido.',
            ], 401);
        }

        // Validar token no serviço de autenticação
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])
                ->get("{$this->authServiceUrl}/api/auth/user");

            if ($response->successful() && $response->json('success')) {
                // Adicionar dados do usuário à requisição
                $request->merge([
                    'auth_user' => $response->json('data.user'),
                ]);

                return $next($request);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao validar token', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token inválido ou expirado.',
        ], 401);
    }
}
```

### Express.js (Node.js)

```javascript
const axios = require('axios');

const AUTH_SERVICE_URL = process.env.AUTH_SERVICE_URL || 'http://localhost';

async function validateAuthToken(req, res, next) {
    const token = req.headers.authorization?.replace('Bearer ', '');

    if (!token) {
        return res.status(401).json({
            success: false,
            message: 'Token não fornecido.',
        });
    }

    try {
        const response = await axios.get(`${AUTH_SERVICE_URL}/api/auth/user`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            timeout: 5000,
        });

        if (response.data.success) {
            // Adicionar dados do usuário à requisição
            req.user = response.data.data.user;
            return next();
        }
    } catch (error) {
        if (error.response?.status === 401) {
            return res.status(401).json({
                success: false,
                message: 'Token inválido ou expirado.',
            });
        }

        console.error('Erro ao validar token:', error.message);
    }

    return res.status(401).json({
        success: false,
        message: 'Token inválido ou expirado.',
    });
}

module.exports = validateAuthToken;
```

## ⚙️ Configuração

### Variáveis de Ambiente

Configure a URL do serviço de autenticação nos seus outros serviços:

**Laravel (.env):**
```env
AUTH_SERVICE_URL=http://localhost
```

**Node.js (.env):**
```env
AUTH_SERVICE_URL=http://localhost
```

**Python (.env):**
```env
AUTH_SERVICE_URL=http://localhost
```

### Cache de Validação (Opcional)

Para melhorar performance, você pode implementar cache local:

```php
// Exemplo com cache (Laravel)
$cacheKey = "auth_token_{$token}";

$userData = Cache::remember($cacheKey, 60, function () use ($token) {
    return $authClient->validateToken($token);
});
```

## 🔒 Segurança

### Boas Práticas

1. **HTTPS em Produção**: Sempre use HTTPS para comunicação entre serviços
2. **Timeout**: Configure timeout adequado (5 segundos recomendado)
3. **Rate Limiting**: O endpoint tem rate limit de 60 requisições/minuto
4. **Cache**: Implemente cache local para reduzir chamadas ao serviço de autenticação
5. **Logging**: Registre tentativas de validação falhadas para monitoramento

### Tratamento de Erros

```php
try {
    $userData = $authClient->validateToken($token);
    
    if (!$userData) {
        // Token inválido - retornar 401
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    // Prosseguir com a requisição
} catch (\Exception $e) {
    // Erro de conexão - decidir se permite ou não
    // Em produção, geralmente retorna 503 (Service Unavailable)
    \Log::error('Erro ao validar token', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Auth service unavailable'], 503);
}
```

## 📊 Performance

### Otimizações

1. **Cache Local**: Cache tokens válidos por 1-5 minutos
2. **Connection Pooling**: Reutilize conexões HTTP
3. **Async Validation**: Valide tokens de forma assíncrona quando possível
4. **Circuit Breaker**: Implemente circuit breaker para evitar sobrecarga

### Exemplo com Cache

```php
class CachedAuthValidator
{
    private $authClient;
    private $cache;

    public function validateToken(string $token): ?array
    {
        $cacheKey = "token_validation_" . md5($token);
        
        return $this->cache->remember($cacheKey, 300, function () use ($token) {
            return $this->authClient->validateToken($token);
        });
    }
}
```

## 🧪 Testes

### Exemplo de Teste

```php
public function test_token_validation()
{
    // 1. Fazer login para obter token
    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    $token = $response->json('data.token');

    // 2. Validar token
    $validationResponse = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get('http://auth-service/api/auth/validate');

    $this->assertTrue($validationResponse->json('success'));
    $this->assertNotNull($validationResponse->json('data.user'));
}
```

## 📝 Resumo

1. **Obter token**: Cliente faz login e recebe token
2. **Enviar token**: Cliente envia token no header `Authorization: Bearer {token}`
3. **Validar token**: Seu serviço chama `/api/auth/validate` ou `/api/auth/user`
4. **Processar resposta**: Se válido, prosseguir; se inválido, retornar 401

## 🔗 URLs dos Endpoints

- **Validação completa**: `GET /api/auth/validate`
- **Validação simplificada**: `GET /api/auth/user`
- **Base URL**: Configure via variável de ambiente `AUTH_SERVICE_URL`

---

**Última atualização:** 2024

