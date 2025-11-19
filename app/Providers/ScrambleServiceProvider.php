<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     * 
     * Configura o Scramble para documentação da API.
     * O Scramble detecta automaticamente o middleware auth:sanctum
     * e configura a autenticação Bearer Token.
     */
    public function boot(): void
    {
        // O Scramble detecta automaticamente Sanctum quando encontra
        // o middleware 'auth:sanctum' nas rotas
        // Não é necessário configuração adicional para autenticação
    }
}
