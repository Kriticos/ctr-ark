@extends('layouts.admin')

@section('title', 'Detalhes do Setor')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sector->name }}</h1>
            <p class="text-gray-500 dark:text-gray-400">Slug: {{ $sector->slug }}</p>
        </div>
        <a href="{{ route('admin.sectors.edit', $sector) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Informações</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">Setor Pai: {{ $sector->parent?->name ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">Descrição: {{ $sector->description ?: '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">Status: {{ $sector->is_active ? 'Ativo' : 'Inativo' }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Subsetores</h2>
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                @forelse($sector->children as $child)
                    <li>{{ $child->name }} ({{ $child->slug }})</li>
                @empty
                    <li>Nenhum subsetor.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Usuários Atribuídos</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="pb-2">Usuário</th>
                        <th class="pb-2">Email</th>
                        <th class="pb-2">Papel no Setor</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-200">
                    @forelse($sector->users as $member)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2">{{ $member->name }}</td>
                            <td class="py-2">{{ $member->email }}</td>
                            <td class="py-2">{{ $member->pivot?->role }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-3">Nenhum usuário atribuído.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

