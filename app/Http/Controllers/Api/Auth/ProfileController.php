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
     * Obter dados do usuário autenticado
     * 
     * Retorna os dados completos do usuário atualmente autenticado.
     * 
     * @group Perfil
     * @authenticated
     * 
     * @response 200 scenario="Dados recuperados com sucesso" {
     *   "success": true,
     *   "message": "Dados do usuário recuperados com sucesso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@example.com",
     *       "email_verified_at": null,
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Não autenticado" {
     *   "success": false,
     *   "message": "Usuário não autenticado.",
     *   "data": null,
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
     * Atualizar perfil do usuário
     * 
     * Atualiza os dados do perfil do usuário autenticado. Você pode atualizar nome e/ou email.
     * O email deve ser único no sistema.
     * 
     * @group Perfil
     * @authenticated
     * 
     * @bodyParam name string Nome completo do usuário. Example: João Santos
     * @bodyParam email string Email do usuário (deve ser único). Example: joao.santos@example.com
     * 
     * @response 200 scenario="Perfil atualizado com sucesso" {
     *   "success": true,
     *   "message": "Perfil atualizado com sucesso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Santos",
     *       "email": "joao.santos@example.com",
     *       "email_verified_at": null,
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   },
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Não autenticado" {
     *   "success": false,
     *   "message": "Usuário não autenticado.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "email": ["Este email já está cadastrado."]
     *   }
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
     * Alterar senha do usuário
     * 
     * Altera a senha do usuário autenticado. Requer a senha atual para confirmação.
     * Após alterar a senha, todos os tokens do usuário são revogados por segurança,
     * sendo necessário fazer login novamente.
     * 
     * @group Perfil
     * @authenticated
     * 
     * @bodyParam current_password string required Senha atual do usuário. Example: OldPassword123!
     * @bodyParam password string required Nova senha (mínimo 8 caracteres, maiúsculas, minúsculas e números). Example: NewPassword123!
     * @bodyParam password_confirmation string required Confirmação da nova senha. Example: NewPassword123!
     * 
     * @response 200 scenario="Senha alterada com sucesso" {
     *   "success": true,
     *   "message": "Senha alterada com sucesso. Faça login novamente.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 400 scenario="Senha atual incorreta" {
     *   "success": false,
     *   "message": "Senha atual incorreta.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Não autenticado" {
     *   "success": false,
     *   "message": "Usuário não autenticado.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "password": ["A senha deve ter no mínimo 8 caracteres."]
     *   }
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

