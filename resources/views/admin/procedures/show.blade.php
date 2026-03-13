@extends('layouts.admin')

@section('title', 'Procedimento')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $procedure->title }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Setores: {{ $procedure->sectors->pluck('name')->implode(', ') ?: $procedure->sector?->name }} | Status: {{ $procedure->status }} | Versão: v{{ $procedure->currentVersion?->version_number }}
            </p>
        </div>
        <div class="flex gap-2">
            @can('access-route', 'admin.procedures.edit')
                <a href="{{ route('admin.procedures.edit', $procedure) }}" class="px-3 py-2 bg-indigo-600 text-white rounded-lg">Editar</a>
            @endcan
            @can('access-route', 'admin.procedures.submit-review')
                <form method="POST" action="{{ route('admin.procedures.submit-review', $procedure) }}">
                    @csrf
                    <button class="px-3 py-2 bg-amber-600 text-white rounded-lg">Enviar revisão</button>
                </form>
            @endcan
            @can('access-route', 'admin.procedures.approve')
                <form method="POST" action="{{ route('admin.procedures.approve', $procedure) }}">
                    @csrf
                    <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Aprovar</button>
                </form>
            @endcan
            @can('access-route', 'admin.procedures.publish')
                <form method="POST" action="{{ route('admin.procedures.publish', $procedure) }}">
                    @csrf
                    <button class="px-3 py-2 bg-blue-600 text-white rounded-lg">Publicar</button>
                </form>
            @endcan
        </div>
    </div>

    @can('access-route', 'admin.procedures.reject')
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Reprovar</h2>
            <form method="POST" action="{{ route('admin.procedures.reject', $procedure) }}" class="space-y-2">
                @csrf
                <textarea name="comment" rows="2" required placeholder="Comentário obrigatório da reprovação"
                          class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white"></textarea>
                <button class="px-3 py-2 bg-red-600 text-white rounded-lg">Reprovar</button>
            </form>
        </div>
    @endcan

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 markdown-prose markdown-prose--plain-inline-code markdown-prose--reader">
        {!! $renderedMarkdown !!}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Versões</h2>
            <div class="space-y-2 text-sm">
                @foreach($procedure->versions as $version)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-gray-900 dark:text-white">v{{ $version->version_number }} - {{ $version->title }}</span>
                            @can('access-route', 'admin.procedures.versions.restore')
                                <form method="POST" action="{{ route('admin.procedures.versions.restore', [$procedure, $version]) }}">
                                    @csrf
                                    <button class="text-indigo-600">Restaurar</button>
                                </form>
                            @endcan
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $version->creator?->name }} | {{ $version->created_at->format('d/m/Y H:i') }}
                        </p>
                        @if($loop->index < count($procedure->versions) - 1)
                            @php($next = $procedure->versions[$loop->index + 1])
                            <a class="text-blue-600" href="{{ route('admin.procedures.compare', [$procedure, $next, $version]) }}">
                                Comparar v{{ $next->version_number }} x v{{ $version->version_number }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Auditoria</h2>
            <div class="space-y-2 text-sm">
                @forelse($procedure->audits as $audit)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                        <p class="text-gray-900 dark:text-white">{{ $audit->action }}</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $audit->user?->name ?? 'Sistema' }} | {{ $audit->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @empty
                    <p class="text-gray-500">Sem registros de auditoria.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
