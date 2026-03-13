@props([
    'name' => 'icon',
    'value' => '',
    'provider' => 'fontawesome',
    'required' => false,
    'label' => 'Ícone',
])

<div x-data="iconPicker({
    name: '{{ $name }}',
    value: '{{ $value }}',
    provider: '{{ $provider }}'
})" class="icon-picker-component">

    <!-- Label -->
    @if($label)
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    @endif

    <!-- Campo Hidden com o valor do ícone -->
    <input type="hidden" :name="name" x-model="selectedIcon" {{ $required ? 'required' : '' }}>

    <!-- Seletor de Fornecedor -->
    <div class="mb-4">
        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Fornecedor de Ícones</label>
        <div class="flex gap-2">
            <template x-for="(providerData, key) in providers" :key="key">
                <button
                    type="button"
                    @click="switchProvider(key)"
                    :class="{
                        'bg-blue-500 text-white': currentProvider === key,
                        'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300': currentProvider !== key
                    }"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80"
                    x-text="providerData.name"
                ></button>
            </template>
        </div>
    </div>

    <!-- Preview do Ícone Selecionado -->
    <div class="mb-4 p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                    <template x-if="selectedIcon">
                        <i :class="getIconClass(selectedIcon)" class="text-3xl text-gray-700 dark:text-gray-300"></i>
                    </template>
                    <template x-if="!selectedIcon">
                        <span class="text-gray-400 dark:text-gray-500 text-sm">?</span>
                    </template>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-show="selectedIcon">Ícone Selecionado: <span class="font-mono text-blue-600 dark:text-blue-400" x-text="selectedIcon"></span></span>
                    <span x-show="!selectedIcon" class="text-gray-400">Nenhum ícone selecionado</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Clique em um ícone abaixo para selecionar
                </p>
            </div>
            <button
                type="button"
                @click="clearSelection"
                x-show="selectedIcon"
                class="shrink-0 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
            >
                Limpar
            </button>
        </div>
    </div>

    <!-- Campo de Busca -->
    <div class="mb-4">
        <input
            type="text"
            x-model="searchTerm"
            placeholder="Buscar ícone..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
    </div>

    <!-- Grid de Ícones -->
    <div class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 p-4 max-h-96 overflow-y-auto">
        <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 gap-2">
            <template x-for="icon in filteredIcons" :key="icon">
                <button
                    type="button"
                    @click="selectIcon(icon)"
                    :class="{
                        'bg-blue-500 text-white ring-2 ring-blue-500': selectedIcon === icon,
                        'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500': selectedIcon !== icon
                    }"
                    class="aspect-square flex items-center justify-center rounded-lg transition-all"
                    :title="icon"
                >
                    <i :class="getIconClass(icon)" class="text-xl"></i>
                </button>
            </template>
        </div>

        <!-- Mensagem quando não há resultados -->
        <div x-show="filteredIcons.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <i class="fas fa-search text-3xl mb-2"></i>
            <p>Nenhum ícone encontrado</p>
        </div>
    </div>

    <!-- Contador de ícones -->
    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-right">
        <span x-text="filteredIcons.length"></span> ícones disponíveis
    </div>
</div>

@once
@push('scripts')
<script>
function iconPicker(config) {
    return {
        name: config.name,
        selectedIcon: config.value || '',
        currentProvider: config.provider || 'fontawesome',
        providers: {},
        searchTerm: '',

        async init() {
            await this.loadProviders();
        },

        async loadProviders() {
            try {
                const response = await fetch('/data/icons.json');
                this.providers = await response.json();
            } catch (error) {
                console.error('Erro ao carregar ícones:', error);
                // Fallback com alguns ícones básicos
                this.providers = {
                    fontawesome: {
                        name: 'Font Awesome',
                        prefix: 'fa-',
                        icons: ['home', 'users', 'cog', 'chart-bar', 'file', 'envelope']
                    }
                };
            }
        },

        get currentProviderData() {
            return this.providers[this.currentProvider] || { icons: [], prefix: '' };
        },

        get filteredIcons() {
            const icons = this.currentProviderData.icons || [];
            if (!this.searchTerm) {
                return icons;
            }

            const search = this.searchTerm.toLowerCase();
            return icons.filter(icon => icon.toLowerCase().includes(search));
        },

        getIconClass(icon) {
            const prefix = this.currentProviderData.prefix || '';

            // Font Awesome
            if (this.currentProvider === 'fontawesome') {
                return `fas ${prefix}${icon}`;
            }

            // Bootstrap Icons
            if (this.currentProvider === 'bootstrap') {
                return `bi ${prefix}${icon}`;
            }

            return icon;
        },

        selectIcon(icon) {
            this.selectedIcon = icon;
        },

        clearSelection() {
            this.selectedIcon = '';
        },

        switchProvider(provider) {
            this.currentProvider = provider;
            this.searchTerm = '';
            // Limpa seleção ao trocar de fornecedor
            this.selectedIcon = '';
        }
    }
}
</script>
@endpush
@endonce
