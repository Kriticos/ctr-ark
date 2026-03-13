<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $permissions = Permission::query()
            ->with('module')
            ->withCount('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $modules = Module::orderBy('order')->orderBy('name')->get();

        return view('admin.permissions.create', compact('modules'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request): RedirectResponse
    {
        Permission::create($request->validated());

        return $this->redirectWithSuccess('Permissão criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): View
    {
        $permission->load(['roles.users', 'module']);

        // Total de usuários com esta permissão (através das roles)
        $totalUsers = $permission->roles->pluck('users')->flatten()->unique('id')->count();

        // Usuários únicos que possuem esta permissão
        $users = $permission->roles
            ->pluck('users')
            ->flatten()
            ->unique('id')
            ->sortBy('name')
            ->take(10); // Limitar a 10 usuários

        // Distribuição de usuários por role
        $usersByRole = $permission->roles->map(function ($role) {
            return [
                'role' => $role->name,
                'count' => $role->users->count(),
            ];
        })->sortByDesc('count')->values();

        // Total de roles
        $totalRoles = $permission->roles->count();

        return view('admin.permissions.show', compact(
            'permission',
            'totalUsers',
            'users',
            'usersByRole',
            'totalRoles'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission): View
    {
        $modules = Module::orderBy('order')->orderBy('name')->get();

        return view('admin.permissions.edit', compact('permission', 'modules'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update($request->validated());

        return $this->redirectWithSuccess('Permissão atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permissão excluída com sucesso!');
    }

    /**
     * Método auxiliar para remover a duplicação dos redirects e strings.
     */
    private function redirectWithSuccess(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.permissions.index')
            ->with('success', $message);
    }
}
