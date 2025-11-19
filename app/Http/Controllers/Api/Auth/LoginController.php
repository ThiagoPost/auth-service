<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para autenticação de usuários
 * 
 * @group Autenticação
 */
class LoginController extends Controller
{
    /**
     * Construtor - injeta o AuthService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Login de usuário
     * 
     * Autentica um usuário com email e senha, retornando um token de acesso Sanctum válido por 24 horas.
     * 
     * Este endpoint permite que usuários façam login no sistema. Após autenticação bem-sucedida,
     * um token Bearer é retornado e deve ser usado em requisições subsequentes que requerem autenticação.
     * 
     * **Rate Limit:** 5 tentativas por minuto
     * 
     * @unauthenticated
     * 
     * @bodyParam email string required Email do usuário cadastrado. Example: user@example.com
     * @bodyParam password string required Senha do usuário (mínimo 8 caracteres). Example: Password123!
     * 
     * @response 200 scenario="Login bem-sucedido" {
     *   "success": true,
     *   "message": "Login realizado com sucesso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com",
     *       "email_verified_at": null,
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Credenciais inválidas" {
     *   "success": false,
     *   "message": "Credenciais inválidas.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "email": ["O campo email é obrigatório."],
     *     "password": ["O campo senha é obrigatório."]
     *   }
     * }
     * 
     * @response 429 scenario="Rate limit excedido" {
     *   "message": "Too Many Attempts."
     * }
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso.',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao realizar login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar login. Tente novamente.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

