@extends('layouts.admin')

@section('title', 'Procedimentos')

@php
    $statusLabels = [
        'review' => 'Revisão',
        'draft' => 'Revisão',
        'in_review' => 'Revisão',
        'approved' => 'Aprovado',
        'published' => 'Publicado',
    ];
@endphp

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Procedimentos</h1>
        <a href="{{ route('admin.procedures.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Novo Procedimento
        </a>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
        <input name="search" value="{{ $search }}" placeholder="Buscar título"
               class="md:col-span-2 px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
        <select name="sector_id" class="px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            <option value="">Todos os setores</option>
            @foreach($sectors as $sector)
                <option value="{{ $sector->id }}" @selected((string) $sectorId === (string) $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            <option value="">Todos os status</option>
            @foreach($statuses as $item)
                <option value="{{ $item }}" @selected($status === $item)>{{ $statusLabels[$item] ?? $item }}</option>
            @endforeach
        </select>
        <button
            type="submit"
            class="px-3 py-1.5 h-[36px] text-sm bg-gray-700 hover:bg-gray-600 text-gray-100 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800"
        >
            Filtrar
        </button>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-200">
                <tr>
                    <th class="text-left p-3">Título</th>
                    <th class="text-left p-3">Setor</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Versão Atual</th>
                    <th class="text-right p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($procedures as $procedure)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="p-3 text-gray-900 dark:text-white">{{ $procedure->title }}</td>
                        <td class="p-3 text-gray-600 dark:text-gray-300">
                            {{ $procedure->sectors->pluck('name')->implode(', ') ?: $procedure->sector?->name }}
                        </td>
                        <td class="p-3 text-gray-600 dark:text-gray-300">
                            <div>{{ $statusLabels[$procedure->status] ?? $procedure->status }}</div>
                            @if($procedure->latestApprovalAction?->action === 'rejected')
                                <span class="inline-block mt-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                    Última decisão: reprovado
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-gray-600 dark:text-gray-300">v{{ $procedure->currentVersion?->version_number ?? '-' }}</td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('admin.procedures.show', $procedure) }}" class="text-blue-600">Ver</a>
                            <a href="{{ route('admin.procedures.edit', $procedure) }}" class="text-indigo-600">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500">Nenhum procedimento encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $procedures->links() }}
</div>
@endsection
