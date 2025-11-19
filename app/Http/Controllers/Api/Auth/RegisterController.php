<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para registro de novos usuários
 * 
 * @group Autenticação
 */
class RegisterController extends Controller
{
    /**
     * Construtor - injeta o AuthService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Registro de novo usuário
     * 
     * Cria uma nova conta de usuário no sistema e retorna automaticamente um token de autenticação.
     * A senha deve atender aos requisitos de segurança: mínimo 8 caracteres, incluindo letras
     * maiúsculas, minúsculas e números.
     * 
     * **Rate Limit:** 5 tentativas por minuto
     * 
     * @unauthenticated
     * 
     * @bodyParam name string required Nome completo do usuário. Example: João Silva
     * @bodyParam email string required Email único do usuário. Example: joao@example.com
     * @bodyParam password string required Senha do usuário (mínimo 8 caracteres, maiúsculas, minúsculas e números). Example: Password123!
     * @bodyParam password_confirmation string required Confirmação da senha (deve ser igual a password). Example: Password123!
     * 
     * @response 201 scenario="Usuário criado com sucesso" {
     *   "success": true,
     *   "message": "Usuário registrado com sucesso.",
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
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "email": ["Este email já está cadastrado."],
     *     "password": ["A senha deve ter no mínimo 8 caracteres."]
     *   }
     * }
     * 
     * @response 429 scenario="Rate limit excedido" {
     *   "message": "Too Many Attempts."
     * }
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            // Registra o usuário
            $user = $this->authService->register($request->validated());

            // Cria token de autenticação
            $token = $user->createToken(
                'auth-token',
                ['*'],
                now()->addHours(24)
            )->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso.',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
                'errors' => [],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário. Tente novamente.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

