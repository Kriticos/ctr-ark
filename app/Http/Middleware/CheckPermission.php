<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Se não estiver autenticado, redireciona para login
        if (! $user) {
            return redirect()->route('login');
        }

        // Obtém o nome da rota atual
        $routeName = Route::currentRouteName();

        // Se não houver nome de rota ou for admin, permite acesso
        if (! $routeName || $user->isAdmin()) {
            return $next($request);
        }

        // Verifica se o usuário tem permissão
        if (! $user->hasPermissionTo($routeName)) {
            abort(403, 'Você não tem permissão para acessar este recurso.');
        }

        return $next($request);
    }
}
