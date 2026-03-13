<?php

namespace App\Livewire\Admin;

use App\Models\Module;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ModulesTable extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $reorderMode = false;

    protected array $queryString = ['search'];

    public function updatingSearch(): void
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
            Module::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->dispatch('order-updated', [
            'message' => 'Ordem dos módulos atualizada com sucesso!',
        ]);
    }

    public function delete(int $moduleId): void
    {
        $module = Module::findOrFail($moduleId);

        // Verificar se o módulo tem permissões
        if ($module->permissions()->count() > 0) {
            $this->dispatch('delete-error', [
                'message' => 'Não é possível excluir um módulo que possui permissões vinculadas!',
            ]);

            return;
        }

        $module->delete();

        $this->dispatch('module-deleted', [
            'message' => 'Módulo excluído com sucesso!',
        ]);
    }

    public function render(): View
    {
        $modules = Module::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->withCount('permissions')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.modules-table', [
            'modules' => $modules,
        ]);
    }
}
