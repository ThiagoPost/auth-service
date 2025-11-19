<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recuperação de senha
 * 
 * @group Recuperação de Senha
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
     * Solicitar reset de senha
     * 
     * Envia um email com token de recuperação para o endereço fornecido.
     * Por segurança, sempre retorna sucesso mesmo se o email não estiver cadastrado,
     * prevenindo enumeração de emails.
     * 
     * **Rate Limit:** 5 tentativas por minuto
     * 
     * @unauthenticated
     * 
     * @bodyParam email string required Email do usuário que deseja recuperar a senha. Example: user@example.com
     * 
     * @response 200 scenario="Email enviado (ou não cadastrado)" {
     *   "success": true,
     *   "message": "Se o email estiver cadastrado, você receberá um link de recuperação.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "email": ["O campo email é obrigatório."]
     *   }
     * }
     * 
     * @response 429 scenario="Rate limit excedido" {
     *   "message": "Too Many Attempts."
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
     * Validar token de recuperação
     * 
     * Verifica se o token de recuperação fornecido é válido e ainda não expirou.
     * Use este endpoint antes de permitir que o usuário redefina a senha.
     * 
     * **Rate Limit:** 10 tentativas por minuto
     * 
     * @unauthenticated
     * 
     * @bodyParam email string required Email do usuário. Example: user@example.com
     * @bodyParam token string required Token de recuperação recebido por email. Example: abc123def456
     * 
     * @response 200 scenario="Token válido" {
     *   "success": true,
     *   "message": "Token válido.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 400 scenario="Token inválido ou expirado" {
     *   "success": false,
     *   "message": "Token inválido ou expirado.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 422 scenario="Validação falhou" {
     *   "success": false,
     *   "message": "Dados de validação inválidos.",
     *   "errors": {
     *     "email": ["O campo email é obrigatório."],
     *     "token": ["O token é obrigatório."]
     *   }
     * }
     */
    public function validateToken(Request $request): JsonResponse
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
     * Redefinir senha
     * 
     * Redefine a senha do usuário usando o token de recuperação válido.
     * Após redefinir a senha, todos os tokens do usuário são revogados por segurança.
     * 
     * **Rate Limit:** 5 tentativas por minuto
     * 
     * @unauthenticated
     * 
     * @bodyParam email string required Email do usuário. Example: user@example.com
     * @bodyParam token string required Token de recuperação recebido por email. Example: abc123def456
     * @bodyParam password string required Nova senha (mínimo 8 caracteres, maiúsculas, minúsculas e números). Example: NewPassword123!
     * @bodyParam password_confirmation string required Confirmação da nova senha. Example: NewPassword123!
     * 
     * @response 200 scenario="Senha redefinida com sucesso" {
     *   "success": true,
     *   "message": "Senha redefinida com sucesso.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 400 scenario="Token inválido ou expirado" {
     *   "success": false,
     *   "message": "Token inválido ou expirado.",
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
     * 
     * @response 429 scenario="Rate limit excedido" {
     *   "message": "Too Many Attempts."
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
