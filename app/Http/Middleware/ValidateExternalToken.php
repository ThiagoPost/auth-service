<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar tokens de outros serviços
 * 
 * Este middleware pode ser usado em outros serviços Laravel para validar
 * tokens gerados por este serviço de autenticação.
 */
class ValidateExternalToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido.',
                'data' => null,
                'errors' => [],
            ], 401);
        }

        // URL do serviço de autenticação
        $authServiceUrl = config('services.auth_service_url', env('AUTH_SERVICE_URL', 'http://localhost'));

        try {
            // Validar token no serviço de autenticação
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])
                ->get("{$authServiceUrl}/api/auth/user");

            if ($response->successful() && $response->json('success')) {
                // Adicionar dados do usuário à requisição
                $userData = $response->json('data.user');
                
                $request->merge([
                    'auth_user' => $userData,
                    'auth_user_id' => $userData['id'] ?? null,
                ]);

                return $next($request);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao validar token externo', [
                'error' => $e->getMessage(),
                'auth_service_url' => $authServiceUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token inválido ou expirado.',
            'data' => null,
            'errors' => [],
        ], 401);
    }
}

