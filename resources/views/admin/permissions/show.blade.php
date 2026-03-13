@extends('layouts.admin')

@section('title', 'Detalhes da Permissão')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Permissões
        </a>
    </div>

    <!-- Cabeçalho -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    @if($permission->module)
                    <div class="flex items-center mb-2">
                        <x-module-icon :icon="$permission->module->icon" class="text-2xl text-gray-500 dark:text-gray-400 mr-2" />
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $permission->module->name }}</span>
                    </div>
                    @endif
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $permission->name }}</h2>
                    @if($permission->description)
                    <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $permission->description }}</p>
                    @endif
                </div>
                <div class="flex space-x-2">
                    @can('access-route', 'admin.permissions.edit')
                    <a href="{{ route('admin.permissions.edit', $permission) }}"
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

        <!-- Total de Roles -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Roles Vinculadas</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalRoles }}</p>
                </div>
            </div>
        </div>

        <!-- Módulo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    @if($permission->module)
                        <x-module-icon :icon="$permission->module->icon" class="text-3xl text-purple-600 dark:text-purple-400" />
                    @else
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    @endif
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Módulo</h3>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $permission->module->name ?? 'Sem módulo' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gráfico: Usuários por Role -->
        @if($usersByRole->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribuição de Usuários por Role</h3>
            <div style="max-width: 280px; margin: 0 auto;">
                <canvas id="usersByRoleChart"></canvas>
            </div>
        </div>
        @endif

        <!-- Roles Vinculadas -->
        @if($permission->roles->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Roles com esta Permissão</h3>
            <div class="space-y-3">
                @foreach($permission->roles->sortBy('name') as $role)
                <a href="{{ route('admin.roles.show', $role) }}"
                   class="block p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $role->users->count() }} {{ $role->users->count() === 1 ? 'usuário' : 'usuários' }}
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Usuários com esta Permissão -->
    @if($users->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Usuários com esta Permissão</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Exibindo até 10 usuários</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($users as $user)
                <x-user-card :user="$user" />
                @endforeach
            </div>
            @if($totalUsers > 10)
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    E mais {{ $totalUsers - 10 }} {{ $totalUsers - 10 === 1 ? 'usuário' : 'usuários' }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Informações de Data -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <p>Criado em: {{ $permission->created_at->format('d/m/Y H:i') }}</p>
                <p>Atualizado em: {{ $permission->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($usersByRole->count() > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('usersByRoleChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    const data = {
        labels: {!! json_encode($usersByRole->pluck('role')) !!},
        datasets: [{
            label: 'Usuários',
            data: {!! json_encode($usersByRole->pluck('count')) !!},
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
                            return `${label}: ${value} usuários (${percentage}%)`;
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
