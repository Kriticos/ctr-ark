<div x-data="modulesSortable" lang="pt-BR">
    <style>
        .sortable-ghost {
            opacity: 0.4;
            background: #dbeafe !important;
        }
        .dark .sortable-ghost {
            background: #1e3a8a !important;
        }
        .sortable-chosen {
            cursor: move !important;
        }
    </style>

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Módulos</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Organizar permissões em módulos do sistema</p>
        </div>
        <div class="flex gap-3">
            <!-- Botão Reordenar -->
            <button
                wire:click="toggleReorderMode"
                type="button"
                class="px-4 py-2 font-medium rounded-lg transition-colors flex items-center space-x-2 {{ $reorderMode ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span>{{ $reorderMode ? 'Salvar Ordem' : 'Reordenar' }}</span>
            </button>

            @can('access-route', 'admin.modules.create')
            <a href="{{ route('admin.modules.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Novo Módulo</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Search Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex gap-4">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nome, slug ou descrição..."
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            @if($search)
            <button
                wire:click="$set('search', '')"
                type="button"
                class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
            >
                Limpar
            </button>
            @endif
        </div>
    </div>

    <!-- Alert de Modo Reordenar -->
    @if($reorderMode)
    <div class="mb-6 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800">
        <div class="flex">
            <div class="shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    <strong>Modo Reordenar Ativo:</strong> Arraste os itens para alterar a ordem. Clique em "Salvar Ordem" quando terminar.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Modules Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" translate="no">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        @if($reorderMode)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ordem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permissões</th>
                        @if(!$reorderMode)
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody
                    id="modules-sortable"
                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                >
                    @forelse($modules as $module)
                    <tr
                        data-id="{{ $module->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $reorderMode ? 'cursor-move' : '' }}"
                    >
                        @if($reorderMode)
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 dark:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $module->order }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($module->icon)
                                    <div class="shrink-0 w-10 h-10 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <x-module-icon :icon="$module->icon" class="text-blue-600 dark:text-blue-400 text-lg" />
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $module->name }}</div>
                                    @if($module->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($module->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" translate="no">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                {{ $module->slug }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $module->permissions_count }}
                        </td>
                        @if(!$reorderMode)
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                @can('access-route', 'admin.modules.show')
                                <a href="{{ route('admin.modules.show', $module) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @endcan
                                @can('access-route', 'admin.modules.edit')
                                <a href="{{ route('admin.modules.edit', $module) }}" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endcan
                                @can('access-route', 'admin.modules.destroy')
                                <button
                                    onclick="confirmDelete({{ $module->id }}, '{{ $module->name }}')"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $reorderMode ? '6' : '5' }}" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhum módulo encontrado</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if($search)
                                Tente ajustar sua busca ou limpar os filtros.
                                @else
                                Comece criando seu primeiro módulo.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $modules->links() }}
    </div>

    @script
    <script>
        Alpine.data('modulesSortable', () => ({
            sortable: null,

            init() {
                // Escutar eventos do Livewire
                this.$wire.on('reorder-enabled', () => {
                    this.enableSortable();
                });

                this.$wire.on('reorder-disabled', () => {
                    this.disableSortable();
                });

                // Toasts de sucesso/erro
                this.$wire.on('order-updated', (event) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: event[0].message,
                        timer: 3000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                    });
                });

                this.$wire.on('module-deleted', (event) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: event[0].message,
                        timer: 3000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                    });
                });

                this.$wire.on('delete-error', (event) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: event[0].message,
                        timer: 5000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                    });
                });
            },

            enableSortable() {
                const el = document.getElementById('modules-sortable');
                if (el && !this.sortable && typeof Sortable !== 'undefined') {
                    this.sortable = new Sortable(el, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onEnd: (evt) => {
                            // Coletar IDs na nova ordem
                            const orderedIds = Array.from(el.querySelectorAll('tr[data-id]'))
                                .map(row => row.getAttribute('data-id'));

                            // Enviar para o Livewire
                            this.$wire.updateOrder(orderedIds);
                        }
                    });
                }
            },

            disableSortable() {
                if (this.sortable) {
                    this.sortable.destroy();
                    this.sortable = null;
                }
            }
        }));

        // Função global para confirmação de exclusão
        window.confirmDelete = function(id, name) {
            Swal.fire({
                title: 'Tem certeza?',
                text: `Você está prestes a excluir o módulo "${name}". Esta ação não pode ser desfeita!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('delete', id);
                }
            });
        };
    </script>
    @endscript
</div>
