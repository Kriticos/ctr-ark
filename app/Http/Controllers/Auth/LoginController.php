<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Registrar último login e atualizar atividade
            Auth::user()->update([
                'last_login_at' => now(),
                'last_activity_at' => now(),
            ]);

            return redirect()->intended($this->resolveHomeRoute(Auth::user()));
        }

        throw ValidationException::withMessages([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        // Marcar usuário como offline definindo last_activity_at para um tempo passado
        // Isso garante que ele será considerado offline imediatamente
        if (Auth::check()) {
            Auth::user()->update([
                'last_activity_at' => now()->subMinutes(10), // 10 minutos atrás = garantidamente offline
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function resolveHomeRoute(?User $user): string
    {
        if (! $user) {
            return route('login');
        }

        if ($user->isAdmin() || $user->hasPermissionTo('admin.dashboard')) {
            return route('admin.dashboard');
        }

        if ($user->hasPermissionTo('admin.procedures.index')) {
            return route('admin.procedures.index');
        }

        return route('login');
    }
}
