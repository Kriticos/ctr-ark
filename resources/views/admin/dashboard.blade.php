@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $proceduresBySectorLabels = $proceduresBySector->pluck('name');
    $proceduresBySectorTotals = $proceduresBySector->pluck('total');
    $statusLabels = $statusDistribution->pluck('status');
    $statusTotals = $statusDistribution->pluck('total');
    $topViewedLabels = $topViewedProcedures->pluck('title');
    $topViewedTotals = $topViewedProcedures->pluck('views');

    $activityLabels = [
        'created' => 'Procedimento criado',
        'updated' => 'Nova versao criada',
        'submitted_for_review' => 'Enviado para aprovacao',
        'approved' => 'Procedimento aprovado',
        'rejected' => 'Procedimento reprovado',
        'published' => 'Procedimento publicado',
        'version_restored' => 'Versao restaurada',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Painel de Procedimentos</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Acompanhamento operacional do acervo, aprovacoes e uso do conhecimento.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Procedimentos cadastrados</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $totalProcedures }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Base total visivel para o seu perfil</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Setores com procedimentos</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $sectorCoverage }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Setores ativos com conteudo em andamento</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Novos procedimentos</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $newProcedures }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Criados nos ultimos 30 dias</p>
        </div>

        <a href="{{ route('admin.procedures.index', ['status' => 'review']) }}" class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Sem aprovacao</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $pendingApproval }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Rascunho + em revisao</p>
        </a>

        <a href="{{ route('admin.procedures.index', ['status' => 'in_review']) }}" class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Em revisao</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $inReviewProcedures }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Aguardando aprovacao formal</p>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center">
            <div class="w-full">
                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400">Publicados</p>
                <p class="mt-1 text-2xl font-medium text-gray-900 dark:text-white leading-none">{{ $publishedProcedures }}</p>
            </div>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-5">Disponiveis para consulta no momento</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_1fr] gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Distribuicao por setor</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Procedimentos vinculados a cada setor</span>
            </div>
            <div class="h-[320px]">
                <canvas id="proceduresBySectorChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Status do acervo</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Panorama atual dos procedimentos</span>
            </div>
            <div class="h-[320px]">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.25fr_1fr] gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Procedimentos mais acessados</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Ranking de visualizacoes registradas</span>
            </div>
            @if($topViewedProcedures->isNotEmpty())
                <div class="h-[320px]">
                    <canvas id="topViewedChart"></canvas>
                </div>
            @else
                <div class="h-[320px] flex items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                    Ainda nao existem visualizacoes suficientes para montar o ranking.
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Lider por setor</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Procedimento com maior uso em cada setor</span>
            </div>
            <div class="space-y-3">
                @forelse($topViewedBySector as $item)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['sector'] }}</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $item['procedure'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $item['views'] }} views
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-4 text-sm text-gray-500 dark:text-gray-400">
                        Ainda nao ha dados de visualizacao por setor.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.1fr_1fr] gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Usuarios online agora</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $onlineUsers->count() }} ativos</span>
            </div>

            @if($onlineUsers->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($onlineUsers->take(8) as $onlineUser)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    @if($onlineUser->avatar)
                                        <img src="{{ Storage::url($onlineUser->avatar) }}" alt="{{ $onlineUser->name }}" class="w-11 h-11 rounded-full object-cover">
                                    @else
                                        <div class="w-11 h-11 rounded-full bg-linear-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($onlineUser->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-0.5 -right-0.5 block w-3 h-3 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-800"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $onlineUser->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $onlineUser->email }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-4 text-sm text-gray-500 dark:text-gray-400">
                    Nenhum usuario com atividade recente no momento.
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Movimentacoes recentes</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Auditoria dos procedimentos</span>
            </div>
            <div class="space-y-4">
                @forelse($recentActivities as $activity)
                    <div class="flex items-start gap-3">
                        <span class="mt-2 h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ $activityLabels[$activity->action] ?? ucfirst(str_replace('_', ' ', $activity->action)) }}
                                @if($activity->procedure)
                                    <span class="font-medium">- {{ $activity->procedure->title }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $activity->user?->name ?? 'Sistema' }} • {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma movimentacao recente encontrada.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const chartText = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827';
    const chartGrid = document.documentElement.classList.contains('dark') ? 'rgba(75, 85, 99, 0.35)' : 'rgba(209, 213, 219, 0.7)';

    const proceduresBySectorCtx = document.getElementById('proceduresBySectorChart');
    if (proceduresBySectorCtx) {
        new Chart(proceduresBySectorCtx, {
            type: 'bar',
            data: {
                labels: @json($proceduresBySectorLabels),
                datasets: [{
                    label: 'Procedimentos',
                    data: @json($proceduresBySectorTotals),
                    backgroundColor: ['#3b82f6', '#06b6d4', '#14b8a6', '#22c55e', '#f59e0b', '#f97316'],
                    borderRadius: 8,
                    maxBarThickness: 48,
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: chartText },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: chartText, precision: 0 },
                        grid: { color: chartGrid }
                    }
                }
            }
        });
    }

    const statusDistributionCtx = document.getElementById('statusDistributionChart');
    if (statusDistributionCtx) {
        new Chart(statusDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: @json($statusLabels),
                datasets: [{
                    data: @json($statusTotals),
                    backgroundColor: ['#64748b', '#f59e0b', '#22c55e', '#3b82f6'],
                    borderWidth: 0,
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: chartText }
                    }
                }
            }
        });
    }

    const topViewedCtx = document.getElementById('topViewedChart');
    if (topViewedCtx) {
        new Chart(topViewedCtx, {
            type: 'bar',
            data: {
                labels: @json($topViewedLabels),
                datasets: [{
                    label: 'Views',
                    data: @json($topViewedTotals),
                    backgroundColor: '#8b5cf6',
                    borderRadius: 8,
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: chartText, precision: 0 },
                        grid: { color: chartGrid }
                    },
                    y: {
                        ticks: { color: chartText },
                        grid: { display: false }
                    }
                }
            }
        });
    }
})();
</script>
@endsection
