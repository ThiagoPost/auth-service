<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recuperação de senha
 * 
 * @group Autenticação
 */
class PasswordResetController extends Controller
{
    /**
     * Construtor - injeta o AuthService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Solicita reset de senha
     * 
     * Envia um email com token para recuperação de senha.
     * 
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Link de recuperação enviado com sucesso",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $email = $request->validated()['email'];
            $message = $this->authService->sendPasswordResetLink($email);

            // Por segurança, sempre retorna sucesso mesmo se o email não existir
            // Isso previne enumeração de emails
            return response()->json([
                'success' => true,
                'message' => 'Se o email estiver cadastrado, você receberá um link de recuperação.',
                'data' => null,
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao solicitar reset de senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao solicitar reset de senha.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Valida o token de recuperação de senha
     * 
     * Verifica se o token fornecido é válido antes de permitir o reset.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     * 
     * @bodyParam email string required Email do usuário
     * @bodyParam token string required Token de recuperação
     */
    public function validateToken(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'token' => ['required', 'string'],
            ]);

            $isValid = $this->authService->validateResetToken(
                $request->input('email'),
                $request->input('token')
            );

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido ou expirado.',
                    'data' => null,
                    'errors' => [],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token válido.',
                'data' => null,
                'errors' => [],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos.',
                'errors' => $e->errors(),
            ], 422);
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
     * Redefine a senha usando o token
     * 
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Senha redefinida com sucesso",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Token inválido ou expirado",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $message = $this->authService->resetPassword([
                'email' => $validated['email'],
                'token' => $validated['token'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
            ]);

            if (str_contains($message, 'sucesso')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => null,
                    'errors' => [],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => [],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erro ao redefinir senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao redefinir senha.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}

