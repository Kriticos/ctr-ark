@extends('layouts.admin')

@section('title', 'Detalhes do Usuário')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para lista
        </a>
    </div>

    <!-- Perfil do Usuário -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="flex items-center space-x-6">
                <div class="shrink-0 relative">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">
                    @else
                        <div class="w-24 h-24 rounded-full bg-linear-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold border-4 border-blue-500">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <x-online-indicator :user="$user" size="lg" />
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                    <div class="flex items-center flex-wrap gap-x-4 gap-y-2 mt-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->getStatusColorClass() }}">
                            {{ $user->getStatusText() }}
                        </span>
                        <span class="text-sm text-gray-400">•</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Cadastrado em {{ $user->created_at->format('d/m/Y') }}
                        </span>
                        @if($user->last_login_at)
                        <span class="text-sm text-gray-400">•</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Último acesso: {{ $user->last_login_at->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="flex space-x-2">
                    @can('access-route', 'admin.users.edit')
                    <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Editar</span>
                    </a>
                    @endcan
                    @can('access-route', 'admin.users.destroy')
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Excluir</span>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas de Controle de Acesso -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total de Roles -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Roles Atribuídas</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalRoles }}</p>
                </div>
            </div>
        </div>

        <!-- Total de Permissões -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Permissões</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalPermissions }}</p>
                </div>
            </div>
        </div>

        <!-- Módulos com Acesso -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Módulos com Acesso</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalModules }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos de Auditoria -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gráfico: Permissões por Role -->
        @if($permissionsByRole->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribuição de Permissões por Role</h3>
            <div style="max-width: 280px; margin: 0 auto;">
                <canvas id="permissionsByRoleChart"></canvas>
            </div>
        </div>
        @endif

        <!-- Gráfico: Permissões por Módulo -->
        @if($permissionsByModule->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acesso por Módulo</h3>
            <div class="space-y-3">
                @foreach($permissionsByModule->take(8) as $item)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <div class="flex items-center">
                            <x-module-icon :icon="$item['icon']" class="text-blue-600 dark:text-blue-400 mr-2" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $item['module'] }}</span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['count'] }} permissões</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-linear-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-300"
                             style="width: {{ $totalPermissions > 0 ? ($item['count'] / $totalPermissions * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Roles do Usuário -->
    @if($user->roles->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Roles Atribuídas ({{ $totalRoles }})</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($user->roles as $role)
                <a href="{{ route('admin.roles.show', $role) }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $role->name }}</h4>
                            <code class="text-xs text-gray-500 dark:text-gray-400">{{ $role->slug }}</code>
                            @if($role->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ Str::limit($role->description, 60) }}</p>
                            @endif
                        </div>
                        <div class="ml-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $role->permissions->count() }} permissões
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Módulos com Acesso -->
    @if($modules->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Módulos que o Usuário Possui Acesso ({{ $totalModules }})</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($modules as $module)
                <a href="{{ route('admin.modules.show', $module) }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors bg-linear-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-700/50">
                    <div class="flex items-center">
                        <x-module-icon :icon="$module->icon" class="text-blue-600 dark:text-blue-400 mr-3" size="lg" />
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $module->name }}</h4>
                            @if($module->description)
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ Str::limit($module->description, 40) }}</p>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Log de Atividades -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Histórico de Atividades</h3>
        </div>
        <div class="p-6">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($activityLog as $index => $activity)
                    <li>
                        <div class="relative pb-8">
                            @if($index < count($activityLog) - 1)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                        {{ $activity['color'] === 'green' ? 'bg-green-500' : '' }}
                                        {{ $activity['color'] === 'blue' ? 'bg-blue-500' : '' }}
                                        {{ $activity['color'] === 'purple' ? 'bg-purple-500' : '' }}">
                                        @if($activity['icon'] === 'login')
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                        </svg>
                                        @elseif($activity['icon'] === 'edit')
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        @elseif($activity['icon'] === 'plus')
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action'] }}</p>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                        @if($activity['date'])
                                        <time datetime="{{ $activity['date']->toIso8601String() }}">
                                            {{ $activity['date']->format('d/m/Y H:i') }}
                                        </time>
                                        <div class="text-xs text-gray-400">
                                            {{ $activity['date']->diffForHumans() }}
                                        </div>
                                        @else
                                        <span class="text-gray-400">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Informações de Data -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div>
                    <span class="font-medium">Criado em:</span> {{ $user->created_at->format('d/m/Y H:i') }}
                    <span class="text-gray-400 ml-2">({{ $user->created_at->diffForHumans() }})</span>
                </div>
                <div>
                    <span class="font-medium">Atualizado em:</span> {{ $user->updated_at->format('d/m/Y H:i') }}
                    <span class="text-gray-400 ml-2">({{ $user->updated_at->diffForHumans() }})</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($permissionsByRole->count() > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('permissionsByRoleChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    const data = {
        labels: {!! json_encode($permissionsByRole->pluck('role')) !!},
        datasets: [{
            label: 'Permissões',
            data: {!! json_encode($permissionsByRole->pluck('count')) !!},
            backgroundColor: [
                'rgba(139, 92, 246, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(236, 72, 153, 0.8)',
            ],
            borderColor: [
                'rgb(139, 92, 246)',
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
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
