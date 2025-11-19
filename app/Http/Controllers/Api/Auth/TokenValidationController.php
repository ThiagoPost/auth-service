<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Controller para validação de tokens para outros serviços
 * 
 * @group Validação de Tokens
 */
class TokenValidationController extends Controller
{
    /**
     * Validar token e retornar dados do usuário
     * 
     * Endpoint para validação de tokens Sanctum por outros serviços.
     * Este endpoint permite que microserviços validem tokens gerados por este serviço
     * de autenticação e obtenham informações do usuário autenticado.
     * 
     * **Uso:** Outros serviços devem fazer uma requisição para este endpoint
     * passando o token no header Authorization para validar se o token é válido
     * e obter os dados do usuário.
     * 
     * **Rate Limit:** 60 requisições por minuto
     * 
     * @unauthenticated
     * 
     * @header Authorization Bearer {token} Token Sanctum a ser validado
     * 
     * @response 200 scenario="Token válido" {
     *   "success": true,
     *   "message": "Token válido.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com",
     *       "email_verified_at": null,
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "token": {
     *       "id": 1,
     *       "name": "auth-token",
     *       "abilities": ["*"],
     *       "expires_at": "2024-01-02T00:00:00.000000Z"
     *     }
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Token inválido ou expirado" {
     *   "success": false,
     *   "message": "Token inválido ou expirado.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Token não fornecido" {
     *   "success": false,
     *   "message": "Token não fornecido.",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            // Obter token do header Authorization
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token não fornecido.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            // Buscar token no banco de dados
            $accessToken = PersonalAccessToken::findToken($token);

            if (!$accessToken) {
                Log::warning('Tentativa de validação com token inválido', [
                    'token_prefix' => substr($token, 0, 10) . '...',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido ou expirado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            // Verificar se o token expirou
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                Log::info('Token expirado', [
                    'token_id' => $accessToken->id,
                    'user_id' => $accessToken->tokenable_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token expirado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            // Obter usuário associado ao token
            $user = $accessToken->tokenable;

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            // Retornar dados do usuário e informações do token
            return response()->json([
                'success' => true,
                'message' => 'Token válido.',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => [
                        'id' => $accessToken->id,
                        'name' => $accessToken->name,
                        'abilities' => $accessToken->abilities,
                        'expires_at' => $accessToken->expires_at?->toISOString(),
                        'last_used_at' => $accessToken->last_used_at?->toISOString(),
                    ],
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao validar token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar token.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Obter apenas dados do usuário (validação simplificada)
     * 
     * Versão simplificada que retorna apenas os dados do usuário se o token for válido.
     * Útil quando você só precisa verificar se o token é válido e obter o ID do usuário.
     * 
     * @unauthenticated
     * 
     * @header Authorization Bearer {token} Token Sanctum a ser validado
     * 
     * @response 200 scenario="Token válido" {
     *   "success": true,
     *   "message": "Token válido.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com"
     *     }
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Token inválido" {
     *   "success": false,
     *   "message": "Token inválido ou expirado.",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token não fornecido.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            $accessToken = PersonalAccessToken::findToken($token);

            if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido ou expirado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            $user = $accessToken->tokenable;

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                    'data' => null,
                    'errors' => [],
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token válido.',
                'data' => [
                    'user' => new UserResource($user),
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao obter usuário do token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar token.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

