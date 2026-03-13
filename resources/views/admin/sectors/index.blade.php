@extends('layouts.admin')

@section('title', 'Setores')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Setores</h1>
        <a href="{{ route('admin.sectors.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Novo Setor
        </a>
    </div>

    <form method="GET" class="flex gap-2">
        <input name="search" value="{{ $search }}" placeholder="Buscar por nome ou slug"
               class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white">
        <button class="px-4 py-2 bg-gray-800 text-white rounded-lg">Buscar</button>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-200">
                <tr>
                    <th class="text-left p-3">Nome</th>
                    <th class="text-left p-3">Slug</th>
                    <th class="text-left p-3">Pai</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-right p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sectors as $sector)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="p-3 text-gray-900 dark:text-white">{{ $sector->name }}</td>
                        <td class="p-3 text-gray-600 dark:text-gray-300">{{ $sector->slug }}</td>
                        <td class="p-3 text-gray-600 dark:text-gray-300">{{ $sector->parent?->name ?? '-' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $sector->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $sector->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('admin.sectors.show', $sector) }}" class="text-blue-600">Ver</a>
                            <a href="{{ route('admin.sectors.edit', $sector) }}" class="text-indigo-600">Editar</a>
                            <form method="POST" action="{{ route('admin.sectors.destroy', $sector) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" onclick="return confirm('Excluir setor?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500">Nenhum setor encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sectors->links() }}
</div>
@endsection

