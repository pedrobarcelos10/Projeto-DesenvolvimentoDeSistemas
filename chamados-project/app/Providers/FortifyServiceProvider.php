<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

/**
 * Autenticação do Prisma.
 *
 * Login por e-mail e senha apenas. Não há cadastro público, 2FA ou passkeys:
 * as contas são criadas e ativadas/desativadas pelo administrador.
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configurePasswordConfirmation();
    }

    /**
     * Require a recent password confirmation for Livewire requests from pages that require one.
     */
    private function configurePasswordConfirmation(): void
    {
        Livewire::addPersistentMiddleware([
            RequirePassword::class,
        ]);
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Usuários desativados pelo administrador não entram no sistema.
     * O aviso de conta desativada só aparece quando a senha está correta,
     * para não revelar quais e-mails existem.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $usuario = User::query()
                ->where(Fortify::username(), Str::lower((string) $request->input(Fortify::username())))
                ->first();

            if ($usuario === null || ! Hash::check((string) $request->input('password'), $usuario->password)) {
                return null;
            }

            if (! $usuario->ativo) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Sua conta está desativada. Procure o administrador do sistema.',
                ]);
            }

            return $usuario;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::resetPasswordView(fn () => view('auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
