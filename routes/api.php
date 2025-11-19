<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\TokenValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aqui estão definidas as rotas da API de autenticação.
| Todas as rotas são prefixadas com /api
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('auth')->group(function () {
    // Registro de novo usuário
    Route::post('/register', RegisterController::class)
        ->middleware('throttle:5,1'); // 5 tentativas por minuto

    // Login
    Route::post('/login', LoginController::class)
        ->middleware('throttle:5,1'); // 5 tentativas por minuto

    // Recuperação de senha
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:5,1'); // 5 tentativas por minuto

    Route::post('/password/validate-token', [PasswordResetController::class, 'validateToken'])
        ->middleware('throttle:10,1');

    Route::post('/password/reset', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1'); // 5 tentativas por minuto

    // Validação de tokens para outros serviços
    Route::get('/validate', [TokenValidationController::class, 'validate'])
        ->middleware('throttle:60,1'); // 60 requisições por minuto
    
    Route::get('/user', [TokenValidationController::class, 'user'])
        ->middleware('throttle:60,1'); // 60 requisições por minuto
});

// Rotas protegidas (requerem autenticação)
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', LogoutController::class);

    // Refresh token
    Route::post('/refresh', RefreshTokenController::class);

    // Dados do usuário autenticado
    Route::get('/me', [ProfileController::class, 'me']);

    // Atualizar perfil
    Route::put('/profile', [ProfileController::class, 'update']);

    // Alterar senha
    Route::post('/password/change', [ProfileController::class, 'changePassword']);
});
