@extends('layouts.admin')

@section('title', 'Editar Usuário')

@section('content')
<div class="p-6">
    <div class="max-w-4xl">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-4 mb-4">
                <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Usuário</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Atualizar informações do usuário</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Avatar Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <x-avatar-upload
                    name="avatar"
                    :current-image="$user->avatar ? Storage::url($user->avatar) : null"
                />
            </div>

            <!-- Personal Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações Pessoais</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nome -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nome completo <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            E-mail <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Password Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Alterar Senha</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Deixe em branco se não quiser alterar a senha</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nova senha
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirmar Senha -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirmar nova senha
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Roles Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Roles de Acesso</h3>

                <div class="space-y-2">
                    @foreach($roles as $role)
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="role_{{ $role->id }}"
                                name="roles[]"
                                value="{{ $role->id }}"
                                {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                            >
                            <label for="role_{{ $role->id }}" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                {{ $role->name }}
                                @if($role->description)
                                    <span class="text-gray-500 dark:text-gray-400">({{ $role->description }})</span>
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>

                @error('roles')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sector Access -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acesso por Setor</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Defina em quais setores o usuário terá acesso e com qual papel.</p>

                <div class="space-y-2">
                    @foreach($sectors as $index => $sector)
                        @php
                            $selectedRole = old("sector_access.$index.role");
                            if ($selectedRole === null) {
                                $selectedRole = $userSectorAccess[$sector->id] ?? null;
                            }
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $sector->name }}
                            </div>
                            <input type="hidden" name="sector_access[{{ $index }}][sector_id]" value="{{ $sector->id }}">
                            <select name="sector_access[{{ $index }}][role]"
                                    class="md:col-span-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Sem acesso</option>
                                <option value="manager" @selected($selectedRole === 'manager')>Gestor de Setor</option>
                                <option value="editor" @selected($selectedRole === 'editor')>Editor de Setor</option>
                                <option value="reader" @selected($selectedRole === 'reader')>Leitor de Setor</option>
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    Salvar Alterações
                </button>
            </div>
        </form>

        <!-- Remover Avatar (formulário separado) -->
        @if($user->avatar)
            <form id="delete-avatar-form" action="{{ route('admin.users.delete-avatar', $user) }}" method="POST" class="mt-6">
                @csrf
                @method('DELETE')
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Zona de Perigo</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Esta ação removerá permanentemente a foto de perfil deste usuário.</p>
                    <button type="button" onclick="confirmDeleteAvatar()" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Remover Foto Permanentemente
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeleteAvatar() {
    Swal.fire({
        title: 'Remover Foto?',
        text: 'Tem certeza que deseja remover a foto deste usuário permanentemente? Esta ação não pode ser desfeita!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, remover!',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-avatar-form').submit();
        }
    });
}
</script>
@endpush
@endsection
