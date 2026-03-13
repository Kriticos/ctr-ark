@extends('layouts.admin')

@section('title', 'Editar Role')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para lista
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Role</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Atualize as informações da role</p>
        </div>

        <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nome da Role <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name', $role->name) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="slug" id="slug"
                                   value="{{ old('slug', $role->slug) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   pattern="[a-z0-9\-]+"
                                   required
                                   @if($role->slug === 'admin') readonly @endif>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Apenas letras minúsculas, números e hífens</p>
                            @if($role->slug === 'admin')
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">O slug da role Admin não pode ser alterado</p>
                            @endif
                            @error('slug')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Descrição
                            </label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Permissões
                            </label>
                            <div class="space-y-4">
                                @forelse($modules as $module)
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900">
                                    <div class="bg-gray-100 dark:bg-gray-800 px-4 py-3 border-b border-gray-300 dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <x-module-icon :icon="$module->icon" class="mr-2 text-blue-600 dark:text-blue-400" />
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $module->name }}</h3>
                                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ $module->permissions->count() }} permissões)</span>
                                            </div>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" class="module-toggle w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" data-module="{{ $module->id }}">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Selecionar todas</span>
                                            </label>
                                        </div>
                                        @if($module->description)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $module->description }}</p>
                                        @endif
                                    </div>
                                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @forelse($module->permissions as $permission)
                                        <label class="flex items-start space-x-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 p-2 rounded module-permission" data-module="{{ $module->id }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 mt-0.5">
                                            <div class="flex-1 min-w-0">
                                                <code class="text-xs font-mono text-gray-900 dark:text-white block truncate">{{ $permission->name }}</code>
                                                @if($permission->description)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ Str::limit($permission->description, 50) }}</p>
                                                @endif
                                            </div>
                                        </label>
                                        @empty
                                        <p class="col-span-3 text-center text-gray-500 dark:text-gray-400 py-2 text-sm">Nenhuma permissão neste módulo</p>
                                        @endforelse
                                    </div>
                                </div>
                                @empty
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 p-6 text-center">
                                    <p class="text-gray-500 dark:text-gray-400">Nenhum módulo cadastrado</p>
                                </div>
                                @endforelse
                            </div>
                            @error('permissions')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.roles.index') }}"
                               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                Atualizar Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar o estado dos toggles
    document.querySelectorAll('.module-toggle').forEach(toggle => {
        const moduleId = toggle.dataset.module;
        const allCheckboxes = document.querySelectorAll(`.module-permission[data-module="${moduleId}"] input[type="checkbox"]`);
        const checkedCount = Array.from(allCheckboxes).filter(cb => cb.checked).length;

        toggle.checked = checkedCount === allCheckboxes.length && allCheckboxes.length > 0;
        toggle.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
    });

    // Toggle todas as permissões de um módulo
    document.querySelectorAll('.module-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const moduleId = this.dataset.module;
            const checkboxes = document.querySelectorAll(`.module-permission[data-module="${moduleId}"] input[type="checkbox"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    });

    // Atualizar o estado do toggle quando checkboxes individuais mudam
    document.querySelectorAll('.module-permission input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const moduleId = this.closest('.module-permission').dataset.module;
            const toggle = document.querySelector(`.module-toggle[data-module="${moduleId}"]`);
            const allCheckboxes = document.querySelectorAll(`.module-permission[data-module="${moduleId}"] input[type="checkbox"]`);
            const checkedCount = Array.from(allCheckboxes).filter(cb => cb.checked).length;

            toggle.checked = checkedCount === allCheckboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
        });
    });
});
</script>
@endsection
