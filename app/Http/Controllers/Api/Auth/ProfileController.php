<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gestão de perfil do usuário
 * 
 * @group Autenticação
 */
class ProfileController extends Controller
{
    /**
     * Construtor - injeta o AuthService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Retorna os dados do usuário autenticado
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Dados do usuário recuperados com sucesso",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com"
     *     }
     *   },
     *   "errors": []
     * }
     */
    public function me(Request $request): JsonResponse
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

            return response()->json([
                'success' => true,
                'message' => 'Dados do usuário recuperados com sucesso.',
                'data' => [
                    'user' => new UserResource($user),
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar dados do usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar dados do usuário.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Atualiza o perfil do usuário autenticado
     * 
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Perfil atualizado com sucesso",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com"
     *     }
     *   },
     *   "errors": []
     * }
     */
    public function update(UpdateProfileRequest $request): JsonResponse
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

            $updatedUser = $this->authService->updateProfile($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso.',
                'data' => [
                    'user' => new UserResource($updatedUser),
                ],
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Altera a senha do usuário autenticado
     * 
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Senha alterada com sucesso",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Senha atual incorreta",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
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

            $validated = $request->validated();
            $success = $this->authService->changePassword(
                $user,
                $validated['current_password'],
                $validated['password']
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta.',
                    'data' => null,
                    'errors' => [],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso. Faça login novamente.',
                'data' => null,
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

