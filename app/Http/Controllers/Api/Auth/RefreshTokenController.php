<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para refresh de token
 * 
 * @group Autenticação
 */
class RefreshTokenController extends Controller
{
    /**
     * Construtor - injeta o AuthService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Renova o token de acesso do usuário
     * 
     * Revoga o token atual e cria um novo token válido por 24 horas.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Token renovado com sucesso",
     *   "data": {
     *     "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "errors": []
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            $token = $this->authService->refreshToken($user);

            return response()->json([
                'success' => true,
                'message' => 'Token renovado com sucesso.',
                'data' => [
                    'token' => $token,
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao renovar token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao renovar token.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

