<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service para lógica de negócio de autenticação
 * 
 * Centraliza a lógica de autenticação, registro e gestão de senhas
 */
class AuthService
{
    /**
     * Realiza o login do usuário e retorna o token
     *
     * @param array<string, string> $credentials
     * @return array<string, mixed>|null
     */
    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            Log::warning('Tentativa de login falhou', ['email' => $credentials['email']]);
            return null;
        }

        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        // Revoga tokens antigos (opcional - para permitir apenas um token ativo por vez)
        // $user->tokens()->delete();

        // Cria novo token com expiração de 24 horas
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        Log::info('Login realizado com sucesso', ['user_id' => $user->id]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Registra um novo usuário
     *
     * @param array<string, string> $data
     * @return User
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Log::info('Novo usuário registrado', ['user_id' => $user->id, 'email' => $user->email]);

        return $user;
    }

    /**
     * Realiza logout revogando o token atual
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        // Revoga o token atual
        $user->currentAccessToken()?->delete();

        Log::info('Logout realizado', ['user_id' => $user->id]);
    }

    /**
     * Revoga todos os tokens do usuário
     *
     * @param User $user
     * @return void
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();

        Log::info('Todos os tokens revogados', ['user_id' => $user->id]);
    }

    /**
     * Cria um novo token para o usuário (refresh)
     *
     * @param User $user
     * @return string
     */
    public function refreshToken(User $user): string
    {
        // Revoga o token atual
        $user->currentAccessToken()?->delete();

        // Cria novo token
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        Log::info('Token renovado', ['user_id' => $user->id]);

        return $token;
    }

    /**
     * Atualiza o perfil do usuário
     *
     * @param User $user
     * @param array<string, string> $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        Log::info('Perfil atualizado', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Altera a senha do usuário
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            Log::warning('Tentativa de alteração de senha com senha atual incorreta', ['user_id' => $user->id]);
            return false;
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoga todos os tokens após alteração de senha (segurança)
        $this->logoutAll($user);

        Log::info('Senha alterada com sucesso', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Envia email de recuperação de senha
     *
     * @param string $email
     * @return string
     */
    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(
            ['email' => $email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Link de recuperação de senha enviado', ['email' => $email]);
            return 'Link de recuperação enviado com sucesso.';
        }

        Log::warning('Falha ao enviar link de recuperação', ['email' => $email]);
        return 'Não foi possível enviar o link de recuperação.';
    }

    /**
     * Valida o token de recuperação de senha
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function validateResetToken(string $email, string $token): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        // Verifica se existe um token válido na tabela password_reset_tokens
        $passwordReset = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$passwordReset) {
            return false;
        }

        // Verifica se o token hash corresponde
        return \Illuminate\Support\Facades\Hash::check($token, $passwordReset->token);
    }

    /**
     * Redefine a senha usando o token
     *
     * @param array<string, string> $credentials
     * @return string
     */
    public function resetPassword(array $credentials): string
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();

                // Revoga todos os tokens após reset de senha
                $this->logoutAll($user);

                Log::info('Senha redefinida via token', ['user_id' => $user->id]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return 'Senha redefinida com sucesso.';
        }

        if ($status === Password::INVALID_TOKEN) {
            return 'Token inválido ou expirado.';
        }

        return 'Não foi possível redefinir a senha.';
    }
}

