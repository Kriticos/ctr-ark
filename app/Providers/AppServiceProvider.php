<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar Carbon para português do Brasil
        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese');

        // Gate para verificar acesso a rotas
        Gate::define('access-route', function (\App\Models\User $user, string $permission) {
            return $user->hasPermissionTo($permission);
        });

        // Gate para Laravel Pulse - apenas admins
        Gate::define('viewPulse', function ($user) {
            return $user->hasRole('admin');
        });

        // Configurar exibição de usuários no Laravel Pulse
        Pulse::user(fn ($user) => [
            'name' => $user->name,
            'extra' => $user->email,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
        ]);
    }
}
