<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
        <input id="sector-name" name="name" value="{{ old('name', $sector?->name) }}"
               class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
        <input id="sector-slug" name="slug" value="{{ old('slug', $sector?->slug) }}"
               class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Setor Pai</label>
        <select name="parent_id" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            <option value="">Sem setor pai</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $sector?->parent_id) === (string) $parent->id)>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
        <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">{{ old('description', $sector?->description) }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $sector?->is_active ?? true))>
            Setor ativo
        </label>
    </div>
</div>

<div class="space-y-2">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Usuários do Setor</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400">Atribua o papel por setor (Gestor, Editor ou Leitor).</p>

    <div class="space-y-2">
        @foreach($users as $index => $user)
            @php
                $selectedRole = old("members.$index.role");
                if ($selectedRole === null && isset($members)) {
                    $selectedRole = $members[$user->id] ?? null;
                }
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center border border-gray-200 dark:border-gray-700 p-3 rounded-lg">
                <div class="md:col-span-2 text-sm text-gray-800 dark:text-gray-200">
                    {{ $user->name }} <span class="text-gray-500">({{ $user->email }})</span>
                </div>
                <input type="hidden" name="members[{{ $index }}][user_id]" value="{{ $user->id }}">
                <select name="members[{{ $index }}][role]" class="w-full px-2 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                    <option value="">Sem acesso</option>
                    <option value="manager" @selected($selectedRole === 'manager')>Gestor de Setor</option>
                    <option value="editor" @selected($selectedRole === 'editor')>Editor de Setor</option>
                    <option value="reader" @selected($selectedRole === 'reader')>Leitor de Setor</option>
                </select>
            </div>
        @endforeach
    </div>
</div>

@if($errors->any())
    <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(! $sector)
    @push('scripts')
        <script>
            (() => {
                const nameInput = document.getElementById('sector-name');
                const slugInput = document.getElementById('sector-slug');

                if (!nameInput || !slugInput) {
                    return;
                }

                const slugify = (value) => value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');

                let manualSlug = slugInput.value.trim() !== '' && slugInput.value !== slugify(nameInput.value);

                const syncSlug = () => {
                    if (!manualSlug) {
                        slugInput.value = slugify(nameInput.value);
                    }
                };

                nameInput.addEventListener('input', syncSlug);
                slugInput.addEventListener('input', () => {
                    manualSlug = slugInput.value.trim() !== '' && slugInput.value !== slugify(nameInput.value);
                });

                syncSlug();
            })();
        </script>
    @endpush
@endif
