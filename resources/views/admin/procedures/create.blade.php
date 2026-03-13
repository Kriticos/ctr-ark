@extends('layouts.admin')

@section('title', 'Novo Procedimento')
@section('content_wrapper_class', 'w-full px-4 py-6 xl:px-6 2xl:px-8')

@section('content')
<div class="w-full max-w-[1600px] mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Novo Procedimento</h1>

    <form method="POST" action="{{ route('admin.procedures.store') }}" class="space-y-8">
        @csrf
        @include('admin.procedures.partials.form', ['procedure' => null, 'sectors' => $sectors, 'selectedSectorIds' => []])

        <section class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resumo da alteração</label>
                <textarea name="change_summary" rows="3"
                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">{{ old('change_summary') }}</textarea>
            </div>

            <div class="flex items-center justify-start">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Salvar</button>
            </div>
        </section>
    </form>
</div>
@endsection
