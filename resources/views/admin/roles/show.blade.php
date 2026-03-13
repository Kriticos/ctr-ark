@extends('layouts.admin')

@section('title', 'Detalhes da Role')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Roles
        </a>
    </div>

    <!-- Cabeçalho -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $role->name }}</h2>
                    <div class="flex items-center mt-2 space-x-2">
                        <code class="px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded text-gray-800 dark:text-gray-200">{{ $role->slug }}</code>
                        @if($role->slug === 'admin')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                            Acesso Total
                        </span>
                        @endif
                    </div>
                    @if($role->description)
                    <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $role->description }}</p>
                    @endif
                </div>
                <div class="flex space-x-2">
                    @can('access-route', 'admin.roles.edit')
                    <a href="{{ route('admin.roles.edit', $role) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total de Usuários -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Usuários</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalUsers }}</p>
                </div>
            </div>
        </div>

        <!-- Total de Permissões -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Permissões</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalPermissions }}</p>
                </div>
            </div>
        </div>

        <!-- Módulos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Módulos</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalModules }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gráfico: Permissões por Módulo -->
        @if($permissionsByModule->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribuição de Permissões por Módulo</h3>
            <div style="max-width: 280px; margin: 0 auto;">
                <canvas id="permissionsByModuleChart"></canvas>
            </div>
        </div>
        @endif

        <!-- Top 5 Módulos -->
        @if($permissionsByModule->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Módulos com Mais Permissões</h3>
            <div class="space-y-4">
                @foreach($permissionsByModule->take(5) as $item)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $item['module'] }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['count'] }} permissões</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                             style="width: {{ $totalPermissions > 0 ? ($item['count'] / $totalPermissions * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Permissões -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissões da Role ({{ $totalPermissions }})</h3>
        </div>
        <div class="p-6">
            @if($role->permissions->count() > 0)
                @php
                    $permissionsByModuleGroup = $role->permissions->groupBy(function($permission) {
                        return $permission->module_id ?? 0;
                    })->sortBy(function($permissions, $moduleId) {
                        return $permissions->first()->module->order ?? 999;
                    });
                @endphp

                <div class="space-y-6">
                    @foreach($permissionsByModuleGroup as $moduleId => $permissions)
                        @php
                            $module = $permissions->first()->module;
                        @endphp

                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <!-- Header do Módulo -->
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                <div class="flex items-center">
                                    <x-module-icon :icon="$module?->icon" class="text-blue-600 dark:text-blue-400 mr-3" />
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $module ? $module->name : 'Sem Módulo' }}
                                    </h4>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                        ({{ $permissions->count() }} {{ $permissions->count() === 1 ? 'permissão' : 'permissões' }})
                                    </span>
                                </div>
                                @if($module && $module->description)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $module->description }}</p>
                                @endif
                            </div>

                            <!-- Permissões do Módulo -->
                            <div class="p-4 bg-white dark:bg-gray-800">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($permissions->sortBy('name') as $permission)
                                        <div class="flex items-start rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-700/50">
                                            <svg class="mr-2 h-5 w-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <div class="flex-1 min-w-0">
                                                @if($permission->description)
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $permission->description }}
                                                    </p>
                                                    <code class="text-xs text-gray-500 dark:text-gray-400 block truncate">
                                                        {{ $permission->name }}
                                                    </code>
                                                @else
                                                    <code class="text-sm font-medium text-gray-900 dark:text-gray-100 block truncate">
                                                        {{ $permission->name }}
                                                    </code>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <p class="mt-2">Nenhuma permissão atribuída a esta role</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Usuários -->
    @if($role->users->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Usuários com esta Role</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($role->users as $user)
                <x-user-card :user="$user" />
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Informações de Data -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <p>Criado em: {{ $role->created_at->format('d/m/Y H:i') }}</p>
                <p>Atualizado em: {{ $role->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($permissionsByModule->count() > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('permissionsByModuleChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    const data = {
        labels: {!! json_encode($permissionsByModule->pluck('module')) !!},
        datasets: [{
            label: 'Permissões',
            data: {!! json_encode($permissionsByModule->pluck('count')) !!},
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
            ],
            borderColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)',
                'rgb(236, 72, 153)',
            ],
            borderWidth: 2
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: isDark ? 'rgb(209, 213, 219)' : 'rgb(55, 65, 81)',
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} permissões (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endif
@endpush
@endsection
