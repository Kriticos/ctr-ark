<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $roles = Role::query()
            ->withCount('users', 'permissions')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.roles.index', compact('roles', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $modules = Module::with(['permissions' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('order')->orderBy('name')->get();

        return view('admin.roles.create', compact('modules'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        // Sincronizar permissões
        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): View
    {
        $role->load(['permissions.module', 'users']);

        // Total de usuários
        $totalUsers = $role->users->count();

        // Total de permissões
        $totalPermissions = $role->permissions->count();

        // Permissões agrupadas por módulo
        $permissionsByModule = $role->permissions->groupBy('module_id')->map(function ($permissions) {
            $module = $permissions->first()->module;

            return [
                'module' => $module ? $module->name : 'Sem Módulo',
                'count' => $permissions->count(),
            ];
        })->sortByDesc('count')->values();

        // Total de módulos com permissões
        $totalModules = $role->permissions->pluck('module_id')->filter()->unique()->count();

        return view('admin.roles.show', compact(
            'role',
            'totalUsers',
            'totalPermissions',
            'totalModules',
            'permissionsByModule'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): View
    {
        $modules = Module::with(['permissions' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('order')->orderBy('name')->get();

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        $role->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        // Sincronizar permissões
        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        } else {
            $role->permissions()->sync([]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Não permite excluir role admin
        if ($role->slug === 'admin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Não é possível excluir a role de Admin!');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role excluída com sucesso!');
    }
}
