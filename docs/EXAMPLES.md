# 📚 Exemplos de Integração - Validação de Tokens

Exemplos práticos de como integrar a validação de tokens em diferentes linguagens e frameworks.

## 🔧 Configuração Inicial

### Variável de Ambiente

Configure a URL do serviço de autenticação:

```env
AUTH_SERVICE_URL=http://localhost
# ou em produção:
AUTH_SERVICE_URL=https://auth.seudominio.com
```

## 🐘 PHP/Laravel

### 1. Service Class

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AuthTokenValidator
{
    private string $authServiceUrl;

    public function __construct()
    {
        $this->authServiceUrl = config('services.auth_service_url', 'http://localhost');
    }

    /**
     * Valida token e retorna dados do usuário
     */
    public function validate(string $token): ?array
    {
        // Cache por 5 minutos
        return Cache::remember("auth_token_{$token}", 300, function () use ($token) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json',
                    ])
                    ->get("{$this->authServiceUrl}/api/auth/user");

                if ($response->successful() && $response->json('success')) {
                    return $response->json('data.user');
                }

                return null;
            } catch (\Exception $e) {
                \Log::error('Erro ao validar token', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }
}
```

### 2. Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthTokenValidator;

class ValidateAuthToken
{
    public function __construct(
        private AuthTokenValidator $validator
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token não fornecido'], 401);
        }

        $user = $this->validator->validate($token);

        if (!$user) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        // Adicionar usuário à requisição
        $request->merge(['user' => $user]);

        return $next($request);
    }
}
```

### 3. Uso no Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        // Usuário já está disponível via middleware
        $userId = $request->user['id'];
        $userEmail = $request->user['email'];

        // Criar pedido para o usuário autenticado
        // ...
    }
}
```

## 🟢 Node.js/Express

### 1. Service Class

```javascript
const axios = require('axios');
const NodeCache = require('node-cache');

const cache = new NodeCache({ stdTTL: 300 }); // 5 minutos

class AuthTokenValidator {
    constructor(authServiceUrl) {
        this.authServiceUrl = authServiceUrl || process.env.AUTH_SERVICE_URL || 'http://localhost';
        this.client = axios.create({
            baseURL: this.authServiceUrl,
            timeout: 5000,
        });
    }

    async validate(token) {
        const cacheKey = `auth_token_${token}`;
        
        // Verificar cache
        const cached = cache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const response = await this.client.get('/api/auth/user', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                },
            });

            if (response.data.success) {
                const userData = response.data.data.user;
                // Armazenar no cache
                cache.set(cacheKey, userData);
                return userData;
            }

            return null;
        } catch (error) {
            if (error.response?.status === 401) {
                return null;
            }
            console.error('Erro ao validar token:', error.message);
            return null;
        }
    }
}

module.exports = AuthTokenValidator;
```

### 2. Middleware

```javascript
const AuthTokenValidator = require('./services/AuthTokenValidator');

const authValidator = new AuthTokenValidator(process.env.AUTH_SERVICE_URL);

async function validateAuthToken(req, res, next) {
    const token = req.headers.authorization?.replace('Bearer ', '');

    if (!token) {
        return res.status(401).json({
            success: false,
            message: 'Token não fornecido.',
        });
    }

    const user = await authValidator.validate(token);

    if (!user) {
        return res.status(401).json({
            success: false,
            message: 'Token inválido ou expirado.',
        });
    }

    // Adicionar usuário à requisição
    req.user = user;

    next();
}

module.exports = validateAuthToken;
```

### 3. Uso nas Rotas

```javascript
const express = require('express');
const router = express.Router();
const validateAuthToken = require('./middleware/validateAuthToken');

router.post('/orders', validateAuthToken, async (req, res) => {
    const userId = req.user.id;
    const userEmail = req.user.email;

    // Criar pedido para o usuário autenticado
    // ...
    
    res.json({ success: true, order: orderData });
});
```

## 🐍 Python/Flask

### 1. Service Class

```python
import requests
from functools import lru_cache
from typing import Optional, Dict
import os

class AuthTokenValidator:
    def __init__(self, auth_service_url: str = None):
        self.base_url = auth_service_url or os.getenv('AUTH_SERVICE_URL', 'http://localhost')
        self.timeout = 5

    def validate(self, token: str) -> Optional[Dict]:
        """
        Valida token e retorna dados do usuário
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
        except requests.exceptions.RequestException as e:
            print(f'Erro ao validar token: {e}')
            return None

# Instância global
auth_validator = AuthTokenValidator()
```

### 2. Decorator

```python
from functools import wraps
from flask import request, jsonify
from services.auth_validator import auth_validator

def require_auth(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        token = request.headers.get('Authorization', '').replace('Bearer ', '')
        
        if not token:
            return jsonify({
                'success': False,
                'message': 'Token não fornecido.'
            }), 401
        
        user = auth_validator.validate(token)
        
        if not user:
            return jsonify({
                'success': False,
                'message': 'Token inválido ou expirado.'
            }), 401
        
        # Adicionar usuário ao contexto
        request.user = user
        
        return f(*args, **kwargs)
    return decorated_function
```

### 3. Uso nas Rotas

```python
from flask import Flask, request, jsonify
from decorators.auth import require_auth

app = Flask(__name__)

@app.route('/orders', methods=['POST'])
@require_auth
def create_order():
    user_id = request.user['id']
    user_email = request.user['email']
    
    # Criar pedido para o usuário autenticado
    # ...
    
    return jsonify({'success': True, 'order': order_data})
```

## 🔄 Go

### 1. Service

```go
package auth

import (
    "encoding/json"
    "fmt"
    "net/http"
    "time"
)

type AuthServiceClient struct {
    BaseURL string
    Client  *http.Client
}

func NewAuthServiceClient(baseURL string) *AuthServiceClient {
    return &AuthServiceClient{
        BaseURL: baseURL,
        Client: &http.Client{
            Timeout: 5 * time.Second,
        },
    }
}

type User struct {
    ID    int    `json:"id"`
    Name  string `json:"name"`
    Email string `json:"email"`
}

type ValidateResponse struct {
    Success bool `json:"success"`
    Data    struct {
        User User `json:"user"`
    } `json:"data"`
}

func (c *AuthServiceClient) ValidateToken(token string) (*User, error) {
    req, err := http.NewRequest("GET", c.BaseURL+"/api/auth/user", nil)
    if err != nil {
        return nil, err
    }

    req.Header.Set("Authorization", "Bearer "+token)
    req.Header.Set("Accept", "application/json")

    resp, err := c.Client.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()

    if resp.StatusCode != http.StatusOK {
        return nil, fmt.Errorf("token inválido")
    }

    var result ValidateResponse
    if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
        return nil, err
    }

    if !result.Success {
        return nil, fmt.Errorf("token inválido")
    }

    return &result.Data.User, nil
}
```

### 2. Middleware

```go
package middleware

import (
    "net/http"
    "strings"
    "your-app/auth"
)

func ValidateAuthToken(authClient *auth.AuthServiceClient) func(http.Handler) http.Handler {
    return func(next http.Handler) http.Handler {
        return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
            authHeader := r.Header.Get("Authorization")
            if authHeader == "" {
                http.Error(w, "Token não fornecido", http.StatusUnauthorized)
                return
            }

            token := strings.TrimPrefix(authHeader, "Bearer ")
            if token == authHeader {
                http.Error(w, "Token inválido", http.StatusUnauthorized)
                return
            }

            user, err := authClient.ValidateToken(token)
            if err != nil {
                http.Error(w, "Token inválido ou expirado", http.StatusUnauthorized)
                return
            }

            // Adicionar usuário ao contexto
            ctx := context.WithValue(r.Context(), "user", user)
            next.ServeHTTP(w, r.WithContext(ctx))
        })
    }
}
```

## 📝 Resumo de Integração

### Passo a Passo

1. **Configure a URL do serviço de autenticação**
   ```env
   AUTH_SERVICE_URL=http://localhost
   ```

2. **Crie um cliente de validação**
   - Faça requisição GET para `/api/auth/user`
   - Envie token no header `Authorization: Bearer {token}`

3. **Implemente middleware/guard**
   - Extraia token do header
   - Valide com o serviço de autenticação
   - Adicione dados do usuário à requisição

4. **Use nos endpoints protegidos**
   - Aplique middleware nas rotas que precisam autenticação
   - Acesse dados do usuário via `request.user` ou similar

### Fluxo de Validação

```
Cliente → Seu Serviço → Serviço de Autenticação
   |           |                    |
   |           |-- Token ---------->|
   |           |<-- User Data ------|
   |           |                    |
   |<-- Response ------------------|
```

---

Para mais detalhes, consulte [INTEGRATION-GUIDE.md](INTEGRATION-GUIDE.md)

