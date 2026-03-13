<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = \App\Models\Role::all();
        $sectors = Sector::orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'sectors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Hash da senha
        $data['password'] = Hash::make($data['password']);

        // Upload do avatar (base64)
        if ($request->filled('avatar')) {
            $data['avatar'] = $this->handleAvatarUpload($request->avatar);
        }

        $user = User::create($data);

        // Sincronizar roles se fornecidas
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        $this->syncUserSectorAccess($user, $data['sector_access'] ?? []);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        // Carregar roles com permissões e módulos
        $user->load(['roles.permissions.module']);

        // Estatísticas de Controle de Acesso
        $totalRoles = $user->roles->count();
        $totalPermissions = $user->roles->flatMap->permissions->unique('id')->count();

        // Coletar todos os módulos únicos que o usuário tem acesso através das permissões
        $modules = $user->roles
            ->flatMap->permissions
            ->pluck('module')
            ->filter()
            ->unique('id')
            ->sortBy('order');

        $totalModules = $modules->count();

        // Permissões agrupadas por Role (para gráfico de pizza)
        $permissionsByRole = $user->roles->map(function ($role) {
            return [
                'role' => $role->name,
                'count' => $role->permissions->count(),
            ];
        })->sortByDesc('count');

        // Permissões agrupadas por Módulo (para análise de acesso)
        $permissionsByModule = $user->roles
            ->flatMap->permissions
            ->groupBy('module_id')
            ->map(function ($permissions) {
                $module = $permissions->first()->module;

                return [
                    'module' => $module ? $module->name : 'Sem Módulo',
                    'icon' => $module?->icon,
                    'count' => $permissions->unique('id')->count(),
                ];
            })
            ->sortByDesc('count');

        // Últimas atividades (baseado em timestamps)
        $activityLog = [
            [
                'action' => 'Último Login',
                'date' => $user->last_login_at,
                'icon' => 'login',
                'color' => 'green',
            ],
            [
                'action' => 'Última Atualização de Perfil',
                'date' => $user->updated_at,
                'icon' => 'edit',
                'color' => 'blue',
            ],
            [
                'action' => 'Conta Criada',
                'date' => $user->created_at,
                'icon' => 'plus',
                'color' => 'purple',
            ],
        ];

        return view('admin.users.show', compact(
            'user',
            'totalRoles',
            'totalPermissions',
            'totalModules',
            'modules',
            'permissionsByRole',
            'permissionsByModule',
            'activityLog'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = \App\Models\Role::all();
        $sectors = Sector::orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();
        $userSectorAccess = $user->sectors->mapWithKeys(
            fn (Sector $sector): array => [$sector->id => $sector->pivot?->role]
        );

        return view('admin.users.edit', compact('user', 'roles', 'userRoles', 'sectors', 'userSectorAccess'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Atualizar senha apenas se fornecida
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Remover avatar dos dados se não foi enviado novo avatar
        if (! $request->filled('avatar')) {
            unset($data['avatar']);
        }

        // Upload do novo avatar (base64)
        if ($request->filled('avatar')) {
            // Deletar avatar antigo
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $this->handleAvatarUpload($request->avatar);
        }

        $user->update($data);

        // Sincronizar roles se fornecidas
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        $this->syncUserSectorAccess($user, $data['sector_access'] ?? []);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevenir que o usuário exclua a si mesmo
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não pode excluir seu próprio usuário!');
        }

        // Deletar avatar se existir
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário excluído com sucesso!');
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(User $user): RedirectResponse
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Foto removida com sucesso!');
    }

    /**
     * Handle avatar upload from base64.
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
     * @param  array<int, array{sector_id:int, role?:string|null}>  $sectorAccess
     */
    private function syncUserSectorAccess(User $user, array $sectorAccess): void
    {
        $syncData = [];

        foreach ($sectorAccess as $item) {
            if (! empty($item['role'])) {
                $syncData[(int) $item['sector_id']] = ['role' => $item['role']];
            }
        }

        $user->sectors()->sync($syncData);
    }
}
