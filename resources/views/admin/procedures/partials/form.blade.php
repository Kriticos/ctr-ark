@php
    $previewProcedureId = $procedure?->id;
    $previewRoute = route('admin.procedures.preview');
    $initialMarkdown = old('markdown_content', $procedure?->currentVersion?->markdown_content);
    $selectedSectorIds = collect(old('sector_ids', $selectedSectorIds ?? []))->map(fn ($id) => (int) $id)->all();
    $initialTempImageTokens = old('temp_image_tokens', '[]');
    $sectorIndex = $sectors->keyBy('id');
    $sectorPath = function ($sector) use (&$sectorPath, $sectorIndex): string {
        if (! $sector || ! $sector->parent_id) {
            return $sector->name;
        }

        $parent = $sectorIndex->get($sector->parent_id);

        return $parent ? $sectorPath($parent).' > '.$sector->name : $sector->name;
    };
    $sectorDepth = function ($sector) use ($sectorIndex): int {
        $depth = 0;
        $current = $sector;

        while ($current?->parent_id) {
            $depth++;
            $current = $sectorIndex->get($current->parent_id);
        }

        return $depth;
    };
    $sectorOptions = $sectors
        ->map(fn ($sector) => [
            'id' => $sector->id,
            'name' => $sector->name,
            'path' => $sectorPath($sector),
            'depth' => $sectorDepth($sector),
            'parent_id' => $sector->parent_id,
            'root_id' => $sectorDepth($sector) === 0
                ? $sector->id
                : (function ($current) use ($sectorIndex) {
                    while ($current?->parent_id) {
                        $current = $sectorIndex->get($current->parent_id);
                    }

                    return $current?->id;
                })($sector),
        ])
        ->sortBy('path', SORT_NATURAL | SORT_FLAG_CASE)
        ->values();
    $sectorRoots = $sectorOptions
        ->where('depth', 0)
        ->values();
@endphp

<div class="space-y-8">
    @if($errors->any())
        <div class="p-4 bg-red-100 text-red-700 rounded-xl text-sm">
            <p class="font-semibold mb-2">Nao foi possivel salvar o procedimento.</p>
            <ul class="list-disc ml-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <input type="hidden" name="temp_image_tokens" value="{{ $initialTempImageTokens }}" data-temp-image-tokens>

    <section class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título</label>
                <input name="title" value="{{ old('title', $procedure?->title ?? $procedure?->currentVersion?->title) }}" required data-title-input
                       class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Setores (múltipla seleção)</label>
                <div class="space-y-3 rounded-lg border p-3 dark:border-gray-700" data-sector-picker>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2" data-sector-selected-tags></div>
                        <button type="button"
                            data-sector-toggle
                            aria-expanded="false"
                            class="shrink-0 inline-flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            Selecionar setores
                        </button>
                    </div>

                    <div data-sector-panel class="hidden space-y-3">
                        <select
                            data-sector-root
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                            <option value="">Todos os setores pai</option>
                            @foreach($sectorRoots as $sectorRoot)
                                <option value="{{ $sectorRoot['id'] }}">{{ $sectorRoot['name'] }}</option>
                            @endforeach
                        </select>

                        <input type="search"
                            data-sector-search
                            placeholder="Buscar setor ou subsetor pelo nome ou caminho..."
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">

                        <div class="max-h-56 overflow-y-auto space-y-2 pr-1" data-sector-options>
                            @foreach($sectorOptions as $sectorOption)
                                <label
                                    data-sector-row
                                    data-sector-label="{{ Str::lower($sectorOption['name']) }}"
                                    data-sector-path="{{ Str::lower($sectorOption['path']) }}"
                                    data-sector-id="{{ $sectorOption['id'] }}"
                                    data-sector-root-id="{{ $sectorOption['root_id'] }}"
                                    data-sector-depth="{{ $sectorOption['depth'] }}"
                                    class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                                >
                                    <input type="checkbox"
                                           name="sector_ids[]"
                                           value="{{ $sectorOption['id'] }}"
                                           data-preview-sector-checkbox
                                           data-sector-option
                                           data-sector-name="{{ $sectorOption['name'] }}"
                                           data-sector-path-label="{{ $sectorOption['path'] }}"
                                           @checked(in_array($sectorOption['id'], $selectedSectorIds, true))
                                           class="sr-only peer">
                                    <span class="min-w-0" style="padding-left: {{ min($sectorOption['depth'], 4) * 0.75 }}rem;">
                                        <span class="block text-sm text-gray-800 dark:text-gray-200">{{ $sectorOption['name'] }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $sectorOption['path'] }}</span>
                                    </span>
                                    <span class="ml-auto mt-0.5 hidden peer-checked:inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                                        Selecionado
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <p data-sector-empty class="hidden text-xs text-gray-500">Nenhum setor encontrado para essa busca.</p>
                    </div>
                </div>
                @error('sector_ids')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                <input name="slug" value="{{ old('slug', $procedure?->slug) }}" required data-slug-input
                       class="w-full px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            </div>
        </div>
    </section>

    <section class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3"
        data-markdown-editor
        data-preview-route="{{ $previewRoute }}"
        data-preview-procedure-id="{{ $previewProcedureId }}"
        data-upload-action="{{ auth()->user()?->can('access-route', 'admin.procedures.images.upload') ? ($procedure ? route('admin.procedures.images.upload', ['procedure' => $procedure]) : route('admin.procedures.images.upload')) : '' }}"
        data-cleanup-route="{{ $procedure ? route('admin.procedures.images.cleanup-temp', ['procedure' => $procedure]) : route('admin.procedures.images.cleanup-temp') }}"
        data-csrf-token="{{ csrf_token() }}">

        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Markdown</label>
            <div class="flex items-center gap-3">
                <p class="text-xs text-gray-500">Preview seguro em tempo real</p>
                <button type="button"
                        data-preview-toggle
                        aria-pressed="true"
                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    Desabilitar preview
                </button>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Para ajustar tamanho por imagem, adicione <code class="px-1.5 py-0.5 rounded border border-gray-300 bg-gray-100 text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{width=420}</code> ao final do comando. Ex. <code>![37e34c03-...05.jpg](http://...05.jpg){width=120}</code>
        </p>

        <div data-markdown-layout class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div data-markdown-editor-pane>
                <textarea id="procedure-markdown-content" name="markdown_content" data-markdown-input rows="18" required
                          class="w-full px-3 py-2 border rounded-lg font-mono text-sm resize-y min-h-[55vh] lg:min-h-[62vh] 2xl:min-h-[68vh] max-h-[78vh] dark:bg-gray-900 dark:border-gray-700 dark:text-white">{{ $initialMarkdown }}</textarea>
            </div>
            <div data-markdown-preview-pane class="border rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <div class="px-3 py-2 text-xs border-b border-gray-200 dark:border-gray-700 text-gray-500">Preview</div>
                <div data-markdown-preview class="markdown-prose markdown-prose--plain-inline-code markdown-prose--reader px-4 py-3 min-h-[55vh] lg:min-h-[62vh] 2xl:min-h-[68vh] max-h-[78vh] overflow-y-auto">
                    <p class="text-gray-400">Digite conteúdo para visualizar...</p>
                </div>
            </div>
        </div>

    </section>
</div>

<link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
<link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css">
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
<style>
    [data-sector-row][data-selected="true"] {
        border-color: rgb(147 197 253) !important;
        background-color: rgb(239 246 255) !important;
    }

    .dark [data-sector-row][data-selected="true"] {
        border-color: rgb(37 99 235) !important;
        background-color: rgb(30 58 138 / 0.25) !important;
    }

    [data-markdown-editor-pane] .toastui-editor-defaultUI {
        border-color: rgb(229 231 235) !important;
        border-radius: 0.5rem;
        background-color: rgb(255 255 255) !important;
    }

    [data-markdown-editor-pane] .toastui-editor-toolbar {
        background-color: rgb(249 250 251) !important;
        border-bottom-color: rgb(229 231 235) !important;
    }

    [data-markdown-editor-pane] .toastui-editor-toolbar-icons {
        border-color: transparent !important;
    }

    [data-markdown-editor-pane] .toastui-editor-md-container {
        background-color: rgb(255 255 255) !important;
    }

    [data-markdown-editor-pane] .toastui-editor-contents {
        color: rgb(17 24 39) !important;
    }

    .dark [data-markdown-editor-pane] .toastui-editor-defaultUI {
        border-color: rgb(55 65 81) !important;
        background-color: rgb(17 24 39) !important;
    }

    .dark [data-markdown-editor-pane] .toastui-editor-toolbar {
        background-color: rgb(31 41 55) !important;
        border-bottom-color: rgb(55 65 81) !important;
    }

    .dark [data-markdown-editor-pane] .toastui-editor-md-container {
        background-color: rgb(17 24 39) !important;
    }

    .dark [data-markdown-editor-pane] .toastui-editor-contents {
        color: rgb(243 244 246) !important;
    }

    [data-markdown-editor-pane] .toastui-editor-md-preview,
    [data-markdown-editor-pane] .toastui-editor-md-splitter,
    [data-markdown-editor-pane] .toastui-editor-md-tab-container {
        display: none !important;
    }

    [data-markdown-editor-pane] .toastui-editor-main {
        display: block !important;
    }

    [data-markdown-editor-pane] .toastui-editor-md-container {
        width: 100% !important;
    }
</style>

<script>
(function () {
    const root = document.querySelector('[data-markdown-editor]');
    if (!root) return;

    const sectorPicker = document.querySelector('[data-sector-picker]');
    const sectorToggle = sectorPicker?.querySelector('[data-sector-toggle]');
    const sectorPanel = sectorPicker?.querySelector('[data-sector-panel]');
    const sectorRoot = sectorPicker?.querySelector('[data-sector-root]');
    const sectorSearch = sectorPicker?.querySelector('[data-sector-search]');
    const sectorRows = Array.from(sectorPicker?.querySelectorAll('[data-sector-row]') ?? []);
    const sectorOptions = Array.from(document.querySelectorAll('[data-sector-option]'));
    const sectorSelectedTags = sectorPicker?.querySelector('[data-sector-selected-tags]');
    const sectorEmpty = sectorPicker?.querySelector('[data-sector-empty]');
    const titleInput = document.querySelector('[data-title-input]');
    const slugInput = document.querySelector('[data-slug-input]');
    const textarea = root.querySelector('[data-markdown-input]');
    const preview = root.querySelector('[data-markdown-preview]');
    const previewPane = root.querySelector('[data-markdown-preview-pane]');
    const editorPane = root.querySelector('[data-markdown-editor-pane]');
    const layout = root.querySelector('[data-markdown-layout]');
    const toggle = root.querySelector('[data-preview-toggle]');
    const tempImageTokensInput = document.querySelector('[data-temp-image-tokens]');
    const sectorCheckboxes = Array.from(document.querySelectorAll('[data-preview-sector-checkbox]'));
    const previewRoute = root.dataset.previewRoute;
    const procedureId = root.dataset.previewProcedureId;
    const uploadAction = root.dataset.uploadAction;
    const canUploadImage = Boolean(uploadAction);
    const cleanupRoute = root.dataset.cleanupRoute;
    const csrfToken = root.dataset.csrfToken;
    const parentForm = root.closest('form');

    let timer = null;
    let lastRequestId = 0;
    let previewEnabled = false;
    let skipTempCleanup = false;
    let tempImageTokens = new Set();
    let slugTouched = Boolean(slugInput?.value);
    let toastEditor = null;
    let editorThemeIsDark = null;

    try {
        const parsedTokens = JSON.parse(tempImageTokensInput?.value || '[]');
        if (Array.isArray(parsedTokens)) {
            parsedTokens.forEach((token) => {
                if (typeof token === 'string' && token !== '') {
                    tempImageTokens.add(token);
                }
            });
        }
    } catch (error) {
        tempImageTokens = new Set();
    }

    function setStatus(text, isError = false) {
        void text;
        void isError;
    }

    async function uploadImageFromEditor(blob) {
        if (!canUploadImage) {
            return null;
        }

        const formData = new FormData();
        formData.append('image', blob);

        const response = await fetch(uploadAction, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        const result = await response.json();
        if (!response.ok || !result.url) {
            throw new Error('Falha no upload.');
        }

        if (result.token) {
            tempImageTokens.add(result.token);
            syncTempImageTokens();
        }

        return result;
    }

    function getMarkdownContent() {
        if (!toastEditor) {
            return textarea.value;
        }

        return toastEditor.getMarkdown();
    }

    function syncMarkdownFromEditor() {
        if (!textarea) {
            return;
        }

        textarea.value = getMarkdownContent();
    }

    function setEditorHeight(targetHeight) {
        if (!toastEditor) {
            return;
        }

        editorPane?.style.setProperty('height', `${targetHeight}px`);
        editorPane?.style.setProperty('min-height', `${targetHeight}px`);

        const wrapper = editorPane?.querySelector('.toastui-editor-defaultUI');
        const main = editorPane?.querySelector('.toastui-editor-main');
        const mdContainer = editorPane?.querySelector('.toastui-editor-md-container');

        wrapper?.style.setProperty('overflow', 'hidden');
        wrapper?.style.setProperty('height', `${targetHeight}px`);
        main?.style.setProperty('height', `${Math.max(0, targetHeight - 44)}px`);
        mdContainer?.style.setProperty('height', `${Math.max(0, targetHeight - 44)}px`);
    }

    function isDarkThemeActive() {
        return document.documentElement.classList.contains('dark');
    }

    function initToastEditor(forceReinit = false) {
        if (!window.toastui?.Editor || !textarea || !editorPane) {
            return;
        }

        const desiredDarkMode = isDarkThemeActive();

        if (toastEditor && !forceReinit && editorThemeIsDark === desiredDarkMode) {
            return;
        }

        const initialMarkdown = getMarkdownContent();

        if (toastEditor) {
            toastEditor.destroy();
            toastEditor = null;
        }

        editorThemeIsDark = desiredDarkMode;

        textarea.classList.add('hidden');
        const toolbarItems = canUploadImage
            ? [['heading', 'bold', 'italic', 'strike'], ['hr', 'quote'], ['ul', 'ol', 'task'], ['table', 'link', 'image'], ['code', 'codeblock']]
            : [['heading', 'bold', 'italic', 'strike'], ['hr', 'quote'], ['ul', 'ol', 'task'], ['table', 'link'], ['code', 'codeblock']];

        toastEditor = new window.toastui.Editor({
            el: editorPane,
            initialValue: initialMarkdown,
            previewStyle: 'tab',
            initialEditType: 'markdown',
            hideModeSwitch: true,
            usageStatistics: false,
            toolbarItems,
            hooks: {
                addImageBlobHook: async (blob, callback) => {
                    try {
                        const result = await uploadImageFromEditor(blob);
                        if (!result) {
                            alert('Você não tem permissão para upload de imagens.');
                            return false;
                        }

                        callback(result.url, result.token || blob.name || 'imagem');
                    } catch (error) {
                        alert('Falha ao enviar imagem.');
                    }

                    return false;
                },
            },
            ...(desiredDarkMode ? { theme: 'dark' } : {}),
        });

        toastEditor.on('change', () => {
            syncMarkdownFromEditor();
            schedulePreview();
        });

        syncMarkdownFromEditor();
        applyResponsiveHeights();
        schedulePreview();
    }

    function watchThemeChanges() {
        if (!window.MutationObserver) {
            return;
        }

        const htmlElement = document.documentElement;
        const observer = new MutationObserver(() => {
            const darkMode = isDarkThemeActive();
            if (darkMode === editorThemeIsDark) {
                return;
            }

            initToastEditor(true);
        });

        observer.observe(htmlElement, { attributes: true, attributeFilter: ['class'] });
    }

    function syncPreviewState() {
        previewPane.classList.toggle('hidden', !previewEnabled);
        editorPane.classList.toggle('lg:col-span-2', !previewEnabled);
        layout.classList.toggle('lg:grid-cols-2', previewEnabled);
        layout.classList.toggle('lg:grid-cols-1', !previewEnabled);
        toggle.textContent = previewEnabled ? 'Desabilitar preview' : 'Habilitar preview';
        toggle.setAttribute('aria-pressed', previewEnabled ? 'true' : 'false');
        applyResponsiveHeights();

        if (!previewEnabled) {
            clearTimeout(timer);
            setStatus('Preview desabilitado');
            return;
        }

        renderPreview();
    }

    async function renderPreview() {
        if (!previewEnabled) {
            return;
        }

        const requestId = ++lastRequestId;
        const markdown = getMarkdownContent();

        if (!markdown.trim()) {
            preview.innerHTML = '<p class="text-gray-400">Digite conteúdo para visualizar...</p>';
            setStatus('Aguardando edição...');
            return;
        }

        setStatus('Atualizando preview...');

        const payload = {
            markdown_content: markdown,
        };

        if (procedureId) {
            payload.procedure_id = Number(procedureId);
        } else {
            const checked = sectorCheckboxes.find((el) => el.checked);
            if (checked) {
                payload.sector_id = Number(checked.value);
            }
        }

        try {
            const response = await fetch(previewRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Falha ao gerar preview.');
            }

            const data = await response.json();

            if (requestId !== lastRequestId) return;

            preview.innerHTML = data.html || '<p class="text-gray-400">Sem conteúdo renderizado.</p>';
            setStatus('Preview atualizado');
        } catch (error) {
            if (requestId !== lastRequestId) return;
            setStatus('Não foi possível atualizar o preview agora.', true);
        }
    }

    function applyResponsiveHeights() {
        const layoutTop = layout.getBoundingClientRect().top;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const reserveSpace = previewEnabled ? 260 : 210;
        const minHeight = previewEnabled ? 432 : 552;
        const maxHeight = previewEnabled ? 744 : 1032;
        const baseHeight = viewportHeight - layoutTop - reserveSpace;
        const targetHeight = Math.max(minHeight, Math.min(maxHeight, Math.round(baseHeight * 1.2)));
        const previewHeader = previewPane?.querySelector('div');
        const previewHeaderHeight = previewEnabled && previewHeader ? previewHeader.offsetHeight : 0;

        textarea.style.height = `${targetHeight}px`;
        textarea.style.minHeight = `${targetHeight}px`;
        textarea.style.maxHeight = `${targetHeight}px`;
        setEditorHeight(targetHeight);

        preview.style.height = `${Math.max(0, targetHeight - previewHeaderHeight)}px`;
        preview.style.minHeight = `${Math.max(0, targetHeight - previewHeaderHeight)}px`;
        preview.style.maxHeight = `${Math.max(0, targetHeight - previewHeaderHeight)}px`;
    }

    function schedulePreview() {
        if (!previewEnabled) {
            return;
        }

        clearTimeout(timer);
        timer = setTimeout(renderPreview, 300);
    }

    function syncTempImageTokens() {
        if (tempImageTokensInput) {
            tempImageTokensInput.value = JSON.stringify(Array.from(tempImageTokens));
        }
    }

    function renderSelectedSectorTags() {
        if (!sectorSelectedTags) {
            return;
        }

        const selected = sectorOptions.filter((checkbox) => checkbox.checked);

        if (selected.length === 0) {
            sectorSelectedTags.innerHTML = '<span class="text-xs text-gray-500">Nenhum setor selecionado.</span>';
            return;
        }

        sectorSelectedTags.innerHTML = selected.map((checkbox) => `
            <button type="button"
                data-remove-sector="${checkbox.value}"
                class="inline-flex items-center gap-2 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200 px-3 py-1 text-xs font-medium">
                <span>${checkbox.dataset.sectorPathLabel}</span>
                <span aria-hidden="true">&times;</span>
            </button>
        `).join('');
    }

    function syncSectorRowState() {
        sectorOptions.forEach((checkbox) => {
            const row = checkbox.closest('[data-sector-row]');
            if (!row) {
                return;
            }

            row.dataset.selected = checkbox.checked ? 'true' : 'false';
        });
    }

    function filterSectorOptions() {
        if (!sectorSearch) {
            return;
        }

        const term = sectorSearch.value.trim().toLowerCase();
        const selectedRoot = sectorRoot?.value || '';
        let visibleCount = 0;

        if (selectedRoot === '') {
            sectorRows.forEach((row) => row.classList.add('hidden'));
            if (sectorEmpty) {
                sectorEmpty.textContent = 'Selecione um setor pai para listar os subsetores.';
                sectorEmpty.classList.remove('hidden');
            }
            return;
        }

        sectorRows.forEach((row) => {
            const haystack = `${row.dataset.sectorLabel} ${row.dataset.sectorPath}`;
            const matchesRoot = row.dataset.sectorRootId === selectedRoot && row.dataset.sectorId !== selectedRoot;
            const matchesTerm = term === '' || haystack.includes(term);
            const visible = matchesRoot && matchesTerm;
            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (sectorEmpty) {
            sectorEmpty.textContent = 'Nenhum setor encontrado para essa busca.';
            sectorEmpty.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function setSectorPanel(open) {
        if (!sectorPanel || !sectorToggle) {
            return;
        }

        sectorPanel.classList.toggle('hidden', !open);
        sectorToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        sectorToggle.textContent = open ? 'Fechar setores' : 'Selecionar setores';

        if (open) {
            sectorSearch?.focus();
        }
    }

    function slugify(value) {
        return value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');
    }

    textarea.addEventListener('input', schedulePreview);
    sectorCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', schedulePreview));
    sectorOptions.forEach((checkbox) => checkbox.addEventListener('change', () => {
        renderSelectedSectorTags();
        syncSectorRowState();
    }));
    sectorSearch?.addEventListener('input', filterSectorOptions);
    sectorRoot?.addEventListener('change', filterSectorOptions);
    sectorToggle?.addEventListener('click', function () {
        setSectorPanel(sectorPanel?.classList.contains('hidden') ?? true);
    });
    sectorSelectedTags?.addEventListener('click', function (event) {
        const target = event.target.closest('[data-remove-sector]');
        if (!target) {
            return;
        }

        const checkbox = sectorOptions.find((item) => item.value === target.dataset.removeSector);
        if (!checkbox) {
            return;
        }

        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    });
    slugInput?.addEventListener('input', function () {
        slugTouched = slugInput.value.trim() !== '';
    });
    titleInput?.addEventListener('input', function () {
        if (!slugInput || slugTouched) {
            return;
        }

        slugInput.value = slugify(titleInput.value);
    });
    toggle.addEventListener('click', function () {
        previewEnabled = !previewEnabled;
        syncPreviewState();
    });

    parentForm?.addEventListener('submit', function () {
        const markdown = getMarkdownContent();
        textarea.value = markdown;
        skipTempCleanup = true;
    });

    parentForm?.addEventListener('formdata', function (event) {
        event.formData.set('markdown_content', getMarkdownContent());
        event.formData.set('temp_image_tokens', JSON.stringify(Array.from(tempImageTokens)));
    });

    window.addEventListener('pagehide', function () {
        if (skipTempCleanup || !cleanupRoute || tempImageTokens.size === 0) {
            return;
        }

        const payload = new FormData();
        payload.append('_token', csrfToken);
        Array.from(tempImageTokens).forEach((token) => payload.append('tokens[]', token));
        navigator.sendBeacon(cleanupRoute, payload);
    });

    window.addEventListener('resize', applyResponsiveHeights);

    initToastEditor();
    watchThemeChanges();
    syncPreviewState();
    syncTempImageTokens();
    renderSelectedSectorTags();
    syncSectorRowState();
    filterSectorOptions();
    setSectorPanel(false);
})();
</script>
