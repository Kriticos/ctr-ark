@extends('layouts.admin')

@section('title', 'Detalhes do Menu')

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

    <!-- Header com Ações -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 flex justify-between items-start">
            <div>
                <div class="flex items-center">
                    @if($menu->icon)
                        <div class="mr-3 w-12 h-12 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <x-module-icon :icon="$menu->icon" class="text-blue-600 dark:text-blue-400 text-2xl" />
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                            {{ $menu->title }}
                            @if($menu->badge)
                                <span class="ml-3 px-2 py-1 text-xs font-medium rounded {{ $menu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $menu->badge }}
                                </span>
                            @endif
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            @if($menu->parent)
                                Submenu de: {{ $menu->parent->title }}
                            @else
                                Menu Principal
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex space-x-2">
                @can('access-route', 'admin.menus.edit')
                    <a href="{{ route('admin.menus.edit', $menu) }}"
                       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Editar
                    </a>
                @endcan
                @can('access-route', 'admin.menus.destroy')
                    <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este menu?')"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                            Excluir
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <!-- Cards de Informações -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $menu->is_active ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }}">
                    <svg class="w-6 h-6 {{ $menu->is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</p>
                    <p class="text-lg font-semibold {{ $menu->is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $menu->is_active ? 'Ativo' : 'Inativo' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tipo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tipo</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($menu->is_divider)
                            Divisor
                        @elseif($menu->parent_id)
                            Submenu
                        @else
                            Menu Principal
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Submenus -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Submenus</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $menu->children->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalhes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Informações do Link -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informações do Link</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Nome da Rota</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ $menu->route_name ?: '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">URL</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ $menu->url ?: '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">URL Gerada</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        @if($menu->route_name && \Illuminate\Support\Facades\Route::has($menu->route_name))
                            {{ route($menu->route_name) }}
                        @elseif($menu->url)
                            {{ url($menu->url) }}
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Alvo</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ $menu->target ?? '_self' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Permissões e Módulo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissões e Módulo</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Módulo</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        @if($menu->module)
                            <a href="{{ route('admin.modules.show', $menu->module) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $menu->module->name }}
                            </a>
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Permissão Requerida</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ $menu->permission_name ?: 'Nenhuma (Público)' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ordem</p>
                    <p class="text-gray-900 dark:text-white mt-1">{{ $menu->order ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Descrição</p>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ $menu->description ?: '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Submenus -->
    @if($menu->children->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Submenus ({{ $menu->children->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Menu
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Rota/URL
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ordem
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($menu->children->sortBy('order') as $submenu)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($submenu->icon)
                                    <div class="shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded mr-2">
                                        <x-module-icon :icon="$submenu->icon" class="text-gray-600 dark:text-gray-400" />
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $submenu->title }}
                                    </div>
                                    @if($submenu->badge)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded {{ $submenu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $submenu->badge }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $submenu->route_name ?: $submenu->url ?: '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($submenu->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    Ativo
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                    Inativo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $submenu->order ?? 0 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                @can('access-route', 'admin.menus.show')
                                    <a href="{{ route('admin.menus.show', $submenu) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Ver
                                    </a>
                                @endcan
                                @can('access-route', 'admin.menus.edit')
                                    <a href="{{ route('admin.menus.edit', $submenu) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Editar
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Metadados -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações do Sistema</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-600 dark:text-gray-400">Criado em:</p>
                <p class="text-gray-900 dark:text-white font-medium">{{ $menu->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-600 dark:text-gray-400">Atualizado em:</p>
                <p class="text-gray-900 dark:text-white font-medium">{{ $menu->updated_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-600 dark:text-gray-400">ID:</p>
                <p class="text-gray-900 dark:text-white font-medium">{{ $menu->id }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
