<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'string', 'regex:/^data:image\/[^;]+;base64,.+/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Atualizar nome e email
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Atualizar avatar se enviado (base64)
        if ($request->filled('avatar') && is_string($request->avatar) && str_starts_with($request->avatar, 'data:image')) {
            // Deletar avatar antigo se existir
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Processar imagem base64
            $user->avatar = $this->handleAvatarUpload($request->avatar);
        }

        // Atualizar senha se fornecida
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    public function deleteAvatar(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Foto removida com sucesso!');
    }

    /**
     * Handle avatar upload from base64 or file.
     */
    private function handleAvatarUpload(string $avatar): string
    {
        // Dados já validados como base64 data URI
        preg_match('/data:image\/(\w+);base64,/', $avatar, $matches);
        $extension = $matches[1] ?? 'jpg';
        $imageData = substr($avatar, strpos($avatar, ',') + 1);
        $imageData = base64_decode($imageData);

        $filename = 'avatars/'.uniqid().'.'.$extension;
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }

    /**
     * Atualizar preferência de tema do usuário.
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
        ]);

        $user = Auth::user();
        $user->theme_preference = $validated['theme'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferência de tema atualizada com sucesso!',
            'theme' => $validated['theme'],
        ]);
    }
}
