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
     * Autentica um usuário e retorna token de acesso
     * 
     * Realiza login com email e senha, retornando um token Sanctum válido por 24 horas.
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Login realizado com sucesso",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com"
     *     },
     *     "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Credenciais inválidas",
     *   "data": null,
     *   "errors": []
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

