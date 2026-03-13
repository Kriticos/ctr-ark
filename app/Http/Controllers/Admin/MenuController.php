<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuRequest;
use App\Http\Requests\Admin\UpdateMenuRequest;
use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Clear menu cache
     * Remove o cache de menus para forçar recarga na próxima requisição.
     */
    protected function clearMenuCache(): void
    {
        Cache::flush();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $moduleFilter = $request->get('module');

        $menus = Menu::query()
            ->with(['module', 'parent', 'children'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%")
                        ->orWhere('permission_name', 'like', "%{$search}%");
                });
            })
            ->when($moduleFilter, function ($query, $moduleFilter) {
                $query->where('module_id', $moduleFilter);
            })
            ->whereNull('parent_id') // Apenas menus principais
            ->orderBy('order')
            ->paginate(15)
            ->withQueryString();

        $modules = Module::orderBy('name')->get();

        return view('admin.menus.index', compact('menus', 'search', 'moduleFilter', 'modules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $modules = Module::orderBy('name')->get();
        $parentMenus = Menu::whereNull('parent_id')->orderBy('order')->get();
        $permissions = Permission::with('module')->orderBy('name')->get();

        return view('admin.menus.create', compact('modules', 'parentMenus', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Definir a ordem como último + 1 se não especificada
        if (! isset($data['order'])) {
            $lastOrder = Menu::where('parent_id', $data['parent_id'] ?? null)->max('order') ?? 0;
            $data['order'] = $lastOrder + 1;
        }

        Menu::create($data);

        // Limpar cache de menus
        $this->clearMenuCache();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu): View
    {
        $menu->load(['module', 'parent', 'children']);

        $stats = [
            'submenus_count' => $menu->children()->count(),
            'active_submenus' => $menu->children()->where('is_active', true)->count(),
        ];

        return view('admin.menus.show', compact('menu', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu): View
    {
        $modules = Module::orderBy('name')->get();
        $parentMenus = Menu::whereNull('parent_id')
            ->where('id', '!=', $menu->id)
            ->orderBy('order')
            ->get();
        $permissions = Permission::with('module')->orderBy('name')->get();

        return view('admin.menus.edit', compact('menu', 'modules', 'parentMenus', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();

        // Prevenir que um menu seja pai de si mesmo
        if (isset($data['parent_id']) && $data['parent_id'] == $menu->id) {
            return redirect()
                ->back()
                ->with('error', 'Um menu não pode ser pai de si mesmo!')
                ->withInput();
        }

        $menu->update($data);

        // Limpar cache de menus
        $this->clearMenuCache();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        // Verificar se tem filhos
        if ($menu->hasChildren()) {
            return redirect()
                ->back()
                ->with('error', 'Não é possível excluir um menu que possui submenus!');
        }

        $menu->delete();

        // Limpar cache de menus
        $this->clearMenuCache();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu excluído com sucesso!');
    }

    /**
     * Update order of menus.
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menus,id',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            Menu::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        // Limpar cache de menus
        $this->clearMenuCache();

        return response()->json(['success' => true, 'message' => 'Ordem atualizada com sucesso!']);
    }
}
