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
     * Registra um novo usuário no sistema
     * 
     * Cria uma nova conta de usuário e retorna um token de autenticação.
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Usuário registrado com sucesso",
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

