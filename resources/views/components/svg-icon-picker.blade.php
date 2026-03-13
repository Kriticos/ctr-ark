@props([
    'name' => 'icon',
    'value' => '',
    'label' => 'Ícone',
    'required' => false,
])

<div x-data="iconPickerMenu('{{ $name }}', @js($value))" x-init="init()" class="space-y-4">
    <!-- Label -->
    @if($label)
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    @endif

    <!-- Campo Hidden com o SVG -->
    <input type="hidden" :name="name" x-model="selectedIcon" {{ $required ? 'required' : '' }}>

    <!-- Preview do Ícone Atual -->
    <div class="p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                    <template x-if="selectedIcon && isHtmlIcon(selectedIcon)">
                        <div x-html="selectedIcon" class="text-gray-700 dark:text-gray-300"></div>
                    </template>
                    <template x-if="selectedIcon && !isHtmlIcon(selectedIcon) && selectedIcon.includes('heroicon-')">
                        <span x-html="getHeroiconSvg(selectedIcon.replace('heroicon-', ''))"></span>
                    </template>
                    <template x-if="selectedIcon && !isHtmlIcon(selectedIcon) && !selectedIcon.includes('heroicon-')">
                        <i :class="selectedIcon" class="text-3xl text-gray-700 dark:text-gray-300"></i>
                    </template>
                    <template x-if="!selectedIcon">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-show="selectedIcon" x-text="getIconLabel()"></span>
                    <span x-show="!selectedIcon" class="text-gray-400">Nenhum ícone selecionado</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Clique em um ícone abaixo para selecionar
                </p>
            </div>
            <button
                type="button"
                @click="clearSelection()"
                x-show="selectedIcon"
                class="shrink-0 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
            >
                Limpar
            </button>
        </div>
    </div>

    <!-- Tabs de Fornecedor -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-2" aria-label="Tabs">
            <template x-for="(provider, key) in providers" :key="key">
                <button
                    type="button"
                    @click="currentProvider = key; searchTerm = ''"
                    :class="{
                        'border-blue-500 text-blue-600 dark:text-blue-400': currentProvider === key,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': currentProvider !== key
                    }"
                    class="whitespace-nowrap py-2 px-4 border-b-2 font-medium text-sm transition-colors"
                    x-text="provider.name"
                ></button>
            </template>
        </nav>
    </div>

    <!-- Campo de Busca -->
    <div>
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
                        'bg-blue-500 text-white ring-2 ring-blue-500': isSelected(icon),
                        'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500': !isSelected(icon)
                    }"
                    class="aspect-square flex items-center justify-center rounded-lg transition-all p-2"
                    :title="icon"
                >
                    <template x-if="currentProvider === 'heroicons'">
                        <span x-html="getHeroiconSvg(icon)"></span>
                    </template>
                    <template x-if="currentProvider !== 'heroicons'">
                        <i :class="getIconClass(icon)" class="text-xl"></i>
                    </template>
                </button>
            </template>
        </div>

        <!-- Mensagem quando não há resultados -->
        <div x-show="filteredIcons.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p>Nenhum ícone encontrado</p>
        </div>
    </div>

    <!-- Contador -->
    <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
        <span x-text="filteredIcons.length"></span> ícones disponíveis
    </div>
</div>

@once
@push('scripts')
<script>
function iconPickerMenu(name, initialValue) {
    return {
        name: name,
        selectedIcon: initialValue || '',
        currentProvider: 'fontawesome',
        searchTerm: '',
        providers: {},

        async init() {
            await this.loadProviders();
            this.detectInitialProvider();
        },

        async loadProviders() {
            try {
                const response = await fetch('/data/icons.json');
                this.providers = await response.json();

                // Adicionar Heroicons manualmente
                this.providers.heroicons = {
                    name: 'Heroicons',
                    prefix: 'heroicon-',
                    icons: [
                        'home', 'users', 'user', 'lock', 'chart', 'cog',
                        'folder', 'document', 'menu', 'puzzle', 'shield', 'key',
                        'lightning', 'bell', 'mail', 'calendar', 'clock',
                        'camera', 'heart', 'star', 'search'
                    ]
                };
            } catch (error) {
                console.error('Erro ao carregar ícones:', error);
                this.providers = {
                    fontawesome: {
                        name: 'Font Awesome',
                        prefix: 'fa-',
                        icons: ['home', 'users', 'cog', 'chart-bar', 'file', 'envelope']
                    },
                    bootstrap: {
                        name: 'Bootstrap Icons',
                        prefix: 'bi-',
                        icons: ['house', 'people', 'gear', 'bar-chart', 'file-earmark', 'envelope']
                    },
                    heroicons: {
                        name: 'Heroicons',
                        prefix: 'heroicon-',
                        icons: ['home', 'users', 'lock', 'chart', 'cog', 'folder']
                    }
                };
            }
        },

        detectInitialProvider() {
            // Se o valor inicial é HTML (SVG), não precisa detectar provider
            if (this.isHtmlIcon(this.selectedIcon)) {
                return;
            }

            // Detectar provider baseado no ícone inicial
            if (this.selectedIcon) {
                if (this.selectedIcon.includes('heroicon-')) {
                    this.currentProvider = 'heroicons';
                } else if (this.selectedIcon.includes('bi-') || this.selectedIcon.includes('bi ')) {
                    this.currentProvider = 'bootstrap';
                } else {
                    this.currentProvider = 'fontawesome';
                }
            }
        },

        get filteredIcons() {
            const icons = this.providers[this.currentProvider]?.icons || [];

            if (!this.searchTerm) {
                return icons;
            }

            const search = this.searchTerm.toLowerCase();
            return icons.filter(icon => icon.toLowerCase().includes(search));
        },

        getIconClass(icon) {
            const prefix = this.providers[this.currentProvider]?.prefix || '';

            if (this.currentProvider === 'fontawesome') {
                return `fas fa-${icon}`;
            }

            if (this.currentProvider === 'bootstrap') {
                return `bi bi-${icon}`;
            }

            if (this.currentProvider === 'heroicons') {
                return `heroicon-${icon}`;
            }

            return icon;
        },

        selectIcon(icon) {
            const iconClass = this.getIconClass(icon);
            this.selectedIcon = iconClass;
        },

        isSelected(icon) {
            const iconClass = this.getIconClass(icon);
            return this.selectedIcon === iconClass;
        },

        isHtmlIcon(icon) {
            return icon && (icon.includes('<svg') || icon.includes('<i '));
        },

        getIconLabel() {
            if (this.isHtmlIcon(this.selectedIcon)) {
                return 'Ícone SVG Personalizado';
            }
            return `Ícone: ${this.selectedIcon}`;
        },

        clearSelection() {
            this.selectedIcon = '';
        },

        getHeroiconSvg(iconName) {
            const svgs = {
                'home': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                'users': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                'user': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
                'lock': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>',
                'chart': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                'cog': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                'folder': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
                'document': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                'menu': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>',
                'puzzle': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>',
                'shield': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                'key': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>',
                'lightning': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                'bell': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                'mail': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                'calendar': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                'clock': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'camera': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                'heart': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
                'star': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
                'search': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
            };
            return svgs[iconName] || '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
        }
    }
}
</script>
@endpush
@endonce
