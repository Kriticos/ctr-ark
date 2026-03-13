<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MenusTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $moduleFilter = '';

    public bool $reorderMode = false;

    protected array $queryString = ['search', 'moduleFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingModuleFilter(): void
    {
        $this->resetPage();
    }

    public function toggleReorderMode(): void
    {
        $this->reorderMode = ! $this->reorderMode;

        if ($this->reorderMode) {
            $this->dispatch('reorder-enabled');
        } else {
            $this->dispatch('reorder-disabled');
        }
    }

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Menu::where('id', $id)->update(['order' => $index]);
        }

        // Limpar cache de menus
        Cache::flush();

        $this->dispatch('order-updated', [
            'message' => 'Ordem dos menus atualizada com sucesso!',
        ]);
    }

    public function delete(int $menuId): void
    {
        $menu = Menu::findOrFail($menuId);

        // Verificar se o menu tem submenus
        if ($menu->children()->count() > 0) {
            $this->dispatch('delete-error', [
                'message' => 'Não é possível excluir um menu que possui submenus!',
            ]);

            return;
        }

        $menu->delete();

        $this->dispatch('menu-deleted', [
            'message' => 'Menu excluído com sucesso!',
        ]);
    }

    public function render(): View
    {
        $modules = Module::orderBy('name')->get();

        $menusQuery = Menu::query()
            ->with(['module', 'children'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('route_name', 'like', "%{$this->search}%")
                        ->orWhere('url', 'like', "%{$this->search}%")
                        ->orWhere('permission_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->moduleFilter, function ($query) {
                $query->where('module_id', $this->moduleFilter);
            })
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('title');

        $menus = $menusQuery->paginate(15);

        return view('livewire.admin.menus-table', [
            'menus' => $menus,
            'modules' => $modules,
        ]);
    }
}
