<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para logout de usuários
 * 
 * @group Autenticação
 */
class LogoutController extends Controller
{
    /**
     * Logout do usuário
     * 
     * Revoga o token de acesso atual do usuário autenticado, invalidando-o para futuras requisições.
     * Após o logout, o token não poderá mais ser usado para acessar endpoints protegidos.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Logout bem-sucedido" {
     *   "success": true,
     *   "message": "Logout realizado com sucesso.",
     *   "data": null,
     *   "errors": []
     * }
     * 
     * @response 401 scenario="Não autenticado" {
     *   "success": false,
     *   "message": "Não autenticado.",
     *   "data": null,
     *   "errors": []
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                // Revoga o token atual
                $user->currentAccessToken()?->delete();

                Log::info('Logout realizado', ['user_id' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso.',
                'data' => null,
                'errors' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao realizar logout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar logout.',
                'data' => null,
                'errors' => [],
            ], 500);
        }
    }
}
