@extends('layouts.admin')

@section('title', 'Editar Setor')

@section('content')
<div class="p-6 max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Editar Setor</h1>

    <form method="POST" action="{{ route('admin.sectors.update', $sector) }}" class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700">
        @csrf
        @method('PUT')
        @include('admin.sectors.partials.form', ['sector' => $sector, 'members' => $members])

        <div class="flex gap-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Atualizar</button>
            <a href="{{ route('admin.sectors.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Cancelar</a>
        </div>
    </form>
</div>
@endsection

