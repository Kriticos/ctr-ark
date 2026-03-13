@extends('layouts.admin')

@section('title', 'Editar Menu')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.menus.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Menus
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Menu</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Atualize as informações do menu</p>
        </div>

        <form action="{{ route('admin.menus.update', $menu) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Informações Básicas -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações Básicas</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Título do Menu <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $menu->title) }}" required
                               placeholder="Ex: Dashboard, Usuários, Relatórios"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="module_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Módulo
                        </label>
                        <select name="module_id" id="module_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Nenhum</option>
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}" {{ old('module_id', $menu->module_id) == $module->id ? 'selected' : '' }}>
                                    {{ $module->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Vincule a um módulo do sistema ACL</p>
                        @error('module_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Menu Pai
                        </label>
                        <select name="parent_id" id="parent_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Nenhum (Menu Principal)</option>
                            @foreach($parentMenus as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $menu->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Deixe vazio para criar um menu principal</p>
                        @error('parent_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Link e Permissão -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Link e Permissão</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="route_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nome da Rota
                        </label>
                        <input type="text" name="route_name" id="route_name" value="{{ old('route_name', $menu->route_name) }}"
                               placeholder="Ex: admin.users.index"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Recomendado: use nomes de rotas Laravel</p>
                        @error('route_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            URL Alternativa
                        </label>
                        <input type="text" name="url" id="url" value="{{ old('url', $menu->url) }}"
                               placeholder="Ex: /painel/dashboard"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Usado se não houver nome de rota</p>
                        @error('url')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="permission_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Permissão Requerida
                        </label>
                        <select name="permission_name" id="permission_name"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Nenhuma (Acesso Público)</option>
                            @foreach($permissions as $permission)
                                <option value="{{ $permission->name }}" {{ old('permission_name', $menu->permission_name) == $permission->name ? 'selected' : '' }}>
                                    {{ $permission->name }}
                                    @if($permission->module)
                                        - {{ $permission->module->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Menu visível apenas para usuários com esta permissão</p>
                        @error('permission_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Alvo do Link
                        </label>
                        <select name="target" id="target"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="_self" {{ old('target', $menu->target) == '_self' ? 'selected' : '' }}>Mesma aba (_self)</option>
                            <option value="_blank" {{ old('target', $menu->target) == '_blank' ? 'selected' : '' }}>Nova aba (_blank)</option>
                        </select>
                        @error('target')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Aparência -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aparência</h3>

                <div class="space-y-6">
                    <div>
                        <x-svg-icon-picker
                            name="icon"
                            :value="old('icon', $menu->icon)"
                            label="Selecione um Ícone"
                        />
                        @error('icon')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="badge" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Badge (Texto)
                            </label>
                            <input type="text" name="badge" id="badge" value="{{ old('badge', $menu->badge) }}"
                                   placeholder="Ex: Novo, Beta"
                                   maxlength="50"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('badge')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="badge_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Badge (Cor Tailwind)
                            </label>
                            <input type="text" name="badge_color" id="badge_color" value="{{ old('badge_color', $menu->badge_color) }}"
                                   placeholder="Ex: bg-blue-100 text-blue-800"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('badge_color')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ordem
                            </label>
                            <input type="number" name="order" id="order" value="{{ old('order', $menu->order) }}" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('order')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outras Opções -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Outras Opções</h3>

                <div class="space-y-4">
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descrição/Tooltip
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Descrição ou tooltip que aparecerá ao passar o mouse"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $menu->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-start space-x-6">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   {{ old('is_active', $menu->is_active) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Menu Ativo
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_divider" id="is_divider" value="1"
                                   {{ old('is_divider', $menu->is_divider) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="is_divider" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                É um Divisor
                            </label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        * Divisores são linhas separadoras visuais no menu
                    </p>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end space-x-3 pt-6">
                <a href="{{ route('admin.menus.index') }}"
                   class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Atualizar Menu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
