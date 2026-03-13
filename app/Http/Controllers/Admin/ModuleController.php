<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $modules = Module::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->withCount('permissions')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.modules.index', compact('modules', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.modules.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        Module::create($request->validated());

        return redirect()->route('admin.modules.index')
            ->with('success', 'Módulo criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Module $module): View
    {
        $module->load(['permissions.roles']);

        // Estatísticas do módulo
        $totalPermissions = $module->permissions->count();
        $totalRoles = $module->permissions->pluck('roles')->flatten()->unique('id')->count();

        // Roles vinculadas (através das permissões)
        $roles = $module->permissions
            ->pluck('roles')
            ->flatten()
            ->unique('id')
            ->sortBy('name');

        // Dados para gráfico de permissões por role
        $permissionsByRole = $roles->map(function ($role) use ($module) {
            return [
                'role' => $role->name,
                'count' => $role->permissions()->whereIn('permissions.id', $module->permissions->pluck('id'))->count(),
            ];
        })->sortByDesc('count')->values();

        // Permissões mais utilizadas (com mais roles vinculadas)
        $topPermissions = $module->permissions
            ->sortByDesc(fn ($p) => $p->roles->count())
            ->take(5);

        return view('admin.modules.show', compact(
            'module',
            'totalPermissions',
            'totalRoles',
            'roles',
            'permissionsByRole',
            'topPermissions'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Module $module): View
    {
        return view('admin.modules.edit', compact('module'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        return redirect()->route('admin.modules.index')
            ->with('success', 'Módulo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module): RedirectResponse
    {
        // Verificar se o módulo tem permissões
        if ($module->permissions()->count() > 0) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Não é possível excluir um módulo que possui permissões vinculadas!');
        }

        $module->delete();

        return redirect()->route('admin.modules.index')
            ->with('success', 'Módulo excluído com sucesso!');
    }
}
