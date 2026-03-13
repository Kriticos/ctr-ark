<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProcedureRequest;
use App\Http\Requests\Admin\UpdateProcedureRequest;
use App\Models\Procedure;
use App\Models\ProcedureApprovalAction;
use App\Models\ProcedureAudit;
use App\Models\ProcedureVersion;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProcedureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $sectorId = $request->integer('sector_id');

        $query = Procedure::query()->with(['sector', 'sectors', 'currentVersion', 'creator', 'latestApprovalAction']);

        if (! $user?->isAdmin()) {
            $allowedSectorIds = $user?->sectors()->pluck('sectors.id')->all() ?? [];
            $query->whereHas('sectors', function ($subQuery) use ($allowedSectorIds) {
                $subQuery->whereIn('sectors.id', $allowedSectorIds);
            });
        }

        $procedures = $query
            ->when($search !== '', function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%");
            })
            ->when($status !== '', function ($subQuery) use ($status) {
                if ($status === 'review') {
                    $subQuery->whereIn('status', [Procedure::STATUS_DRAFT, Procedure::STATUS_IN_REVIEW]);

                    return;
                }

                $subQuery->where('status', $status);
            })
            ->when($sectorId > 0, function ($subQuery) use ($sectorId) {
                $subQuery->whereHas('sectors', function ($sectorQuery) use ($sectorId) {
                    $sectorQuery->where('sectors.id', $sectorId);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sectors = $this->availableSectors();
        $statuses = [
            'review',
            Procedure::STATUS_APPROVED,
            Procedure::STATUS_PUBLISHED,
        ];

        return view('admin.procedures.index', compact('procedures', 'sectors', 'statuses', 'search', 'status', 'sectorId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.procedures.create', [
            'sectors' => $this->availableSectors(forEdit: true),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProcedureRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $sectorIds = collect($data['sector_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $tempImageTokens = $this->parseTempImageTokens($data['temp_image_tokens'] ?? null);

        abort_if(! $user || ! $this->userCanEditAllSectors($user, $sectorIds), 403);

        $conflict = $this->findSlugConflict($data['slug'], $sectorIds);
        if ($conflict) {
            $sectorNames = $conflict->sectors->pluck('name')->implode(', ');

            return back()->withErrors([
                'slug' => "Slug já usado por '{$conflict->title}' (slug: {$conflict->slug}) nos setores: {$sectorNames}.",
            ])->withInput();
        }

        DB::transaction(function () use ($data, $user, $sectorIds, $tempImageTokens): void {
            $procedure = Procedure::create([
                'sector_id' => $sectorIds[0],
                'created_by' => $user->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'status' => Procedure::STATUS_DRAFT,
            ]);

            $procedure->sectors()->sync($sectorIds);
            $markdownContent = $this->persistTemporaryImages(
                $procedure,
                $data['markdown_content'],
                $tempImageTokens,
                (int) $user->id,
            );

            $version = ProcedureVersion::create([
                'procedure_id' => $procedure->id,
                'created_by' => $user->id,
                'version_number' => 1,
                'title' => $data['title'],
                'markdown_content' => $markdownContent,
                'change_summary' => $data['change_summary'] ?? null,
            ]);

            $procedure->update(['current_version_id' => $version->id]);
            $this->audit($procedure->id, $version->id, 'created', ['status' => Procedure::STATUS_DRAFT]);
        });

        return redirect()
            ->route('admin.procedures.index')
            ->with('success', 'Procedimento criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Procedure $procedure): View
    {
        $this->assertCanAccess($procedure);
        $procedure->load(['sector', 'sectors', 'currentVersion', 'versions.creator', 'approvalActions.user', 'audits.user']);
        $this->audit($procedure->id, $procedure->current_version_id, 'viewed');

        $renderedMarkdown = $this->renderMarkdown($procedure->currentVersion?->markdown_content ?? '');

        return view('admin.procedures.show', compact('procedure', 'renderedMarkdown'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Procedure $procedure): View
    {
        $this->assertCanEdit($procedure);
        $procedure->load(['currentVersion', 'sectors']);
        $sectors = $this->availableSectors(forEdit: true);
        $selectedSectorIds = $procedure->sectors->pluck('id')->all();

        return view('admin.procedures.edit', compact('procedure', 'sectors', 'selectedSectorIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProcedureRequest $request, Procedure $procedure): RedirectResponse
    {
        $this->assertCanEdit($procedure);
        $data = $request->validated();
        $sectorIds = collect($data['sector_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $tempImageTokens = $this->parseTempImageTokens($data['temp_image_tokens'] ?? null);

        if (! $this->userCanEditAllSectors(Auth::user(), $sectorIds)) {
            abort(403);
        }

        $conflict = $this->findSlugConflict($data['slug'], $sectorIds, $procedure->id);
        if ($conflict) {
            $sectorNames = $conflict->sectors->pluck('name')->implode(', ');

            return back()->withErrors([
                'slug' => "Slug já usado por '{$conflict->title}' (slug: {$conflict->slug}) nos setores: {$sectorNames}.",
            ])->withInput();
        }

        DB::transaction(function () use ($data, $procedure, $sectorIds, $tempImageTokens): void {
            $latestVersion = (int) $procedure->versions()->max('version_number');
            $markdownContent = $this->persistTemporaryImages(
                $procedure,
                $data['markdown_content'],
                $tempImageTokens,
                (int) Auth::id(),
            );
            $newVersion = ProcedureVersion::create([
                'procedure_id' => $procedure->id,
                'created_by' => (int) Auth::id(),
                'version_number' => $latestVersion + 1,
                'title' => $data['title'],
                'markdown_content' => $markdownContent,
                'change_summary' => $data['change_summary'] ?? null,
            ]);

            $procedure->update([
                'sector_id' => $sectorIds[0],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'status' => Procedure::STATUS_DRAFT,
                'current_version_id' => $newVersion->id,
            ]);
            $procedure->sectors()->sync($sectorIds);

            $this->audit($procedure->id, $newVersion->id, 'updated', ['status' => Procedure::STATUS_DRAFT]);
        });

        return redirect()
            ->route('admin.procedures.show', $procedure)
            ->with('success', 'Nova versão criada e procedimento atualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Procedure $procedure): RedirectResponse
    {
        $this->assertCanManage($procedure);
        $procedure->delete();
        $this->audit($procedure->id, $procedure->current_version_id, 'soft_deleted');

        return redirect()
            ->route('admin.procedures.index')
            ->with('success', 'Procedimento movido para a lixeira.');
    }

    public function submitForReview(Procedure $procedure): RedirectResponse
    {
        $this->assertCanEdit($procedure);
        $procedure->update(['status' => Procedure::STATUS_IN_REVIEW]);
        $this->approval($procedure, 'submitted');
        $this->audit($procedure->id, $procedure->current_version_id, 'submitted_for_review');

        return back()->with('success', 'Procedimento enviado para revisão.');
    }

    public function approve(Procedure $procedure): RedirectResponse
    {
        $this->assertCanManage($procedure);
        $procedure->update(['status' => Procedure::STATUS_APPROVED]);
        $this->approval($procedure, 'approved');
        $this->audit($procedure->id, $procedure->current_version_id, 'approved');

        return back()->with('success', 'Procedimento aprovado.');
    }

    public function reject(Request $request, Procedure $procedure): RedirectResponse
    {
        $this->assertCanManage($procedure);
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $procedure->update(['status' => Procedure::STATUS_DRAFT]);
        $this->approval($procedure, 'rejected', $validated['comment']);
        $this->audit($procedure->id, $procedure->current_version_id, 'rejected', ['comment' => $validated['comment']]);

        return back()->with('success', 'Procedimento reprovado e retornado para rascunho.');
    }

    public function publish(Procedure $procedure): RedirectResponse
    {
        $this->assertCanManage($procedure);
        abort_if($procedure->status !== Procedure::STATUS_APPROVED, 422, 'Somente procedimentos aprovados podem ser publicados.');

        $procedure->update([
            'status' => Procedure::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->approval($procedure, 'published');
        $this->audit($procedure->id, $procedure->current_version_id, 'published');

        return back()->with('success', 'Procedimento publicado.');
    }

    public function restoreVersion(Procedure $procedure, ProcedureVersion $version): RedirectResponse
    {
        $this->assertCanManage($procedure);
        abort_if($version->procedure_id !== $procedure->id, 404);

        DB::transaction(function () use ($procedure, $version): void {
            $nextVersion = (int) $procedure->versions()->max('version_number') + 1;

            $restored = ProcedureVersion::create([
                'procedure_id' => $procedure->id,
                'created_by' => (int) Auth::id(),
                'version_number' => $nextVersion,
                'title' => $version->title,
                'markdown_content' => $version->markdown_content,
                'change_summary' => 'Versão restaurada da v'.$version->version_number,
                'is_restore' => true,
            ]);

            $procedure->update([
                'title' => $restored->title,
                'status' => Procedure::STATUS_DRAFT,
                'current_version_id' => $restored->id,
            ]);

            $this->audit($procedure->id, $restored->id, 'version_restored', ['from_version' => $version->version_number]);
        });

        return back()->with('success', 'Versão restaurada com sucesso.');
    }

    public function compareVersions(Procedure $procedure, ProcedureVersion $from, ProcedureVersion $to): View
    {
        $this->assertCanAccess($procedure);
        abort_if($from->procedure_id !== $procedure->id || $to->procedure_id !== $procedure->id, 404);

        $diffRows = $this->buildSideBySideDiff($from->markdown_content, $to->markdown_content);

        return view('admin.procedures.compare', compact('procedure', 'from', 'to', 'diffRows'));
    }

    public function uploadImage(Request $request, ?Procedure $procedure = null)
    {
        $this->assertCanUploadImages($procedure);

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $validated['image'];
        $filename = now()->format('YmdHis').'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $token = Str::uuid()->toString().'-'.$filename;
        $folder = $this->temporaryImageDirectory($procedure, (int) Auth::id());

        Storage::disk('local')->putFileAs($folder, $file, $token);

        $urlParams = ['token' => $token];
        if ($procedure) {
            $urlParams['procedure'] = $procedure;
        }

        $url = route('admin.procedures.images.temp.show', $urlParams);

        return response()->json([
            'token' => $token,
            'url' => $url,
            'markdown' => '!['.$filename.']('.$url.')',
        ]);
    }

    public function cleanupTempImages(Request $request, ?Procedure $procedure = null): JsonResponse
    {
        $this->assertCanUploadImages($procedure);

        $validated = $request->validate([
            'tokens' => ['required', 'array'],
            'tokens.*' => ['required', 'string'],
        ]);

        $directory = $this->temporaryImageDirectory($procedure, (int) Auth::id());

        foreach ($validated['tokens'] as $token) {
            Storage::disk('local')->delete($directory.'/'.$this->sanitizeStoredFilename($token));
        }

        return response()->json(['deleted' => count($validated['tokens'])]);
    }

    public function showTempImage(string $token, ?Procedure $procedure = null)
    {
        $this->assertCanUploadImages($procedure);

        $safeToken = $this->sanitizeStoredFilename($token);
        $relativePath = $this->temporaryImageDirectory($procedure, (int) Auth::id()).'/'.$safeToken;
        abort_unless(Storage::disk('local')->exists($relativePath), 404);

        return response()->file(Storage::disk('local')->path($relativePath));
    }

    public function showImage(Procedure $procedure, string $filename)
    {
        $this->assertCanAccess($procedure);

        $safeFile = basename($filename);
        $relativePath = 'procedures/p'.$procedure->id.'/'.$safeFile;
        abort_unless(Storage::disk('local')->exists($relativePath), 404);

        return response()->file(Storage::disk('local')->path($relativePath));
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'markdown_content' => ['required', 'string'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'procedure_id' => ['nullable', 'integer', 'exists:procedures,id'],
        ]);

        $user = Auth::user();
        abort_if(! $user, 403);

        if (! empty($validated['procedure_id'])) {
            /** @var Procedure $procedure */
            $procedure = Procedure::findOrFail((int) $validated['procedure_id']);
            abort_if(! $this->userCanEditProcedure($user, $procedure), 403);
        } elseif (! empty($validated['sector_id'])) {
            abort_if(! $user->canEditSector((int) $validated['sector_id']), 403);
        } else {
            // sem setor selecionado ainda: permite apenas para quem já tem algum setor editável
            abort_if(! $user->isAdmin() && ! $user->sectors()->whereIn('sector_user.role', ['manager', 'editor'])->exists(), 403);
        }

        return response()->json([
            'html' => $this->renderMarkdown($validated['markdown_content']),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Sector>
     */
    private function availableSectors(bool $forEdit = false)
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return Sector::active()->orderBy('name')->get();
        }

        $query = $user?->sectors()->where('sectors.is_active', true);
        if ($forEdit) {
            $query?->whereIn('sector_user.role', ['manager', 'editor']);
        }

        return $query?->orderBy('sectors.name')->get() ?? collect();
    }

    /**
     * @return array<int, array{left:string|null,right:string|null,status:string}>
     */
    private function buildSideBySideDiff(string $fromContent, string $toContent): array
    {
        $leftLines = preg_split('/\R/', $fromContent) ?: [];
        $rightLines = preg_split('/\R/', $toContent) ?: [];
        $max = max(count($leftLines), count($rightLines));
        $rows = [];

        for ($i = 0; $i < $max; $i++) {
            $left = $leftLines[$i] ?? null;
            $right = $rightLines[$i] ?? null;
            $status = 'same';

            if ($left === null && $right !== null) {
                $status = 'added';
            } elseif ($left !== null && $right === null) {
                $status = 'removed';
            } elseif ($left !== $right) {
                $status = 'changed';
            }

            $rows[] = [
                'left' => $left,
                'right' => $right,
                'status' => $status,
            ];
        }

        return $rows;
    }

    private function assertCanAccess(Procedure $procedure): void
    {
        $user = Auth::user();
        abort_if(! $user || ! $this->userCanAccessProcedure($user, $procedure), 403);
    }

    private function assertCanEdit(Procedure $procedure): void
    {
        $user = Auth::user();
        abort_if(! $user || ! $this->userCanEditProcedure($user, $procedure), 403);
    }

    private function assertCanManage(Procedure $procedure): void
    {
        $user = Auth::user();
        abort_if(! $user || ! $this->userCanManageProcedure($user, $procedure), 403);
    }

    private function userCanAccessProcedure(\App\Models\User $user, Procedure $procedure): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $procedureSectorIds = $procedure->sectors()->pluck('sectors.id')->all();
        if ($procedureSectorIds === []) {
            $procedureSectorIds = [$procedure->sector_id];
        }

        return $user->sectors()->whereIn('sectors.id', $procedureSectorIds)->exists();
    }

    private function userCanEditProcedure(\App\Models\User $user, Procedure $procedure): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $procedureSectorIds = $procedure->sectors()->pluck('sectors.id')->all();
        if ($procedureSectorIds === []) {
            $procedureSectorIds = [$procedure->sector_id];
        }

        return $user->sectors()
            ->whereIn('sectors.id', $procedureSectorIds)
            ->whereIn('sector_user.role', ['manager', 'editor'])
            ->exists();
    }

    private function userCanManageProcedure(\App\Models\User $user, Procedure $procedure): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $procedureSectorIds = $procedure->sectors()->pluck('sectors.id')->all();
        if ($procedureSectorIds === []) {
            $procedureSectorIds = [$procedure->sector_id];
        }

        return $user->sectors()
            ->whereIn('sectors.id', $procedureSectorIds)
            ->wherePivot('role', 'manager')
            ->exists();
    }

    /**
     * @param  array<int, int>  $sectorIds
     */
    private function userCanEditAllSectors(?\App\Models\User $user, array $sectorIds): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $editableIds = $user->sectors()
            ->whereIn('sector_user.role', ['manager', 'editor'])
            ->pluck('sectors.id')
            ->all();

        return count(array_diff($sectorIds, $editableIds)) === 0;
    }

    /**
     * @param  array<int, int>  $sectorIds
     */
    private function findSlugConflict(string $slug, array $sectorIds, ?int $ignoreProcedureId = null): ?Procedure
    {
        $query = Procedure::query()
            ->with('sectors')
            ->where('slug', $slug)
            ->whereHas('sectors', function ($subQuery) use ($sectorIds) {
                $subQuery->whereIn('sectors.id', $sectorIds);
            });

        if ($ignoreProcedureId) {
            $query->where('id', '!=', $ignoreProcedureId);
        }

        /** @var Procedure|null $procedure */
        $procedure = $query->first();

        return $procedure;
    }

    private function renderMarkdown(string $markdown): string
    {
        $normalizedMarkdown = $this->normalizeImageWidthSyntax($markdown);
        $html = Str::markdown(
            $normalizedMarkdown,
            ['html_input' => 'strip', 'allow_unsafe_links' => false]
        );

        return $this->applyImageWidthMarkersToHtml($html);
    }

    private function normalizeImageWidthSyntax(string $markdown): string
    {
        return (string) preg_replace_callback(
            '/!\[(.*?)\]\(([^)\n]+)\)\s*\{width\s*=\s*(\d{2,4})\}/i',
            function (array $matches): string {
                $alt = $matches[1];
                $imagePath = $matches[2];
                $width = (int) $matches[3];
                $width = max(120, min(1600, $width));

                return '!['.$alt.' {{imgw:'.$width.'}}]('.$imagePath.')';
            },
            $markdown
        );
    }

    private function applyImageWidthMarkersToHtml(string $html): string
    {
        if (! str_contains($html, '{{imgw:')) {
            return $html;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<div id="md-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            $alt = (string) $image->getAttribute('alt');
            if (! preg_match('/\{\{imgw:(\d{2,4})\}\}/', $alt, $matches)) {
                continue;
            }

            $width = (int) $matches[1];
            $width = max(120, min(1600, $width));

            $existingStyle = trim((string) $image->getAttribute('style'));
            $widthStyle = 'max-width: '.$width.'px; width: 100%; height: auto;';
            if ($existingStyle !== '') {
                $existingStyle = rtrim($existingStyle, '; ').'; ';
            }
            $image->setAttribute('style', $existingStyle.$widthStyle);

            $cleanAlt = (string) preg_replace('/\s*\{\{imgw:\d{2,4}\}\}\s*/', '', $alt);
            $image->setAttribute('alt', trim($cleanAlt));
        }

        $root = $dom->getElementById('md-root');
        if (! $root) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return $html;
        }

        $rendered = '';
        foreach ($root->childNodes as $child) {
            $rendered .= $dom->saveHTML($child) ?: '';
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $rendered;
    }

    /**
     * @return array<int, string>
     */
    private function parseTempImageTokens(?string $rawTokens): array
    {
        if (! $rawTokens) {
            return [];
        }

        $decoded = json_decode($rawTokens, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($token) => is_string($token) && $token !== '')
            ->map(fn (string $token) => $this->sanitizeStoredFilename($token))
            ->unique()
            ->values()
            ->all();
    }

    private function persistTemporaryImages(Procedure $procedure, string $markdown, array $tempImageTokens, int $userId): string
    {
        if ($tempImageTokens === []) {
            return $markdown;
        }

        $procedureTempDirectory = $this->temporaryImageDirectory($procedure, $userId);
        $draftTempDirectory = $this->temporaryImageDirectory(null, $userId);
        $finalDirectory = 'procedures/p'.$procedure->id;

        foreach ($tempImageTokens as $token) {
            $procedureTempUrl = route('admin.procedures.images.temp.show', [
                'procedure' => $procedure,
                'token' => $token,
            ]);
            $draftTempUrl = route('admin.procedures.images.temp.show', [
                'token' => $token,
            ]);

            $finalUrl = route('admin.procedures.images.show', [
                'procedure' => $procedure,
                'filename' => $token,
            ]);

            $procedureTempPath = $procedureTempDirectory.'/'.$token;
            $draftTempPath = $draftTempDirectory.'/'.$token;

            if (str_contains($markdown, $procedureTempUrl) && Storage::disk('local')->exists($procedureTempPath)) {
                Storage::disk('local')->move($procedureTempPath, $finalDirectory.'/'.$token);
                $markdown = str_replace($procedureTempUrl, $finalUrl, $markdown);
                continue;
            }

            if (str_contains($markdown, $draftTempUrl) && Storage::disk('local')->exists($draftTempPath)) {
                Storage::disk('local')->move($draftTempPath, $finalDirectory.'/'.$token);
                $markdown = str_replace($draftTempUrl, $finalUrl, $markdown);
                continue;
            }

            Storage::disk('local')->delete([$procedureTempPath, $draftTempPath]);
        }

        return $markdown;
    }

    private function temporaryImageDirectory(?Procedure $procedure, int $userId): string
    {
        if ($procedure) {
            return 'procedures/tmp/p'.$procedure->id.'/u'.$userId;
        }

        return 'procedures/tmp/new/u'.$userId;
    }

    private function assertCanUploadImages(?Procedure $procedure): void
    {
        if ($procedure) {
            $this->assertCanEdit($procedure);

            return;
        }

        $user = Auth::user();
        abort_if(! $user, 403);
        abort_if(
            ! $user->isAdmin() && ! $user->sectors()->whereIn('sector_user.role', ['manager', 'editor'])->exists(),
            403
        );
    }

    private function sanitizeStoredFilename(string $filename): string
    {
        return basename($filename);
    }

    private function approval(Procedure $procedure, string $action, ?string $comment = null): void
    {
        ProcedureApprovalAction::create([
            'procedure_id' => $procedure->id,
            'version_id' => $procedure->current_version_id,
            'user_id' => (int) Auth::id(),
            'action' => $action,
            'comment' => $comment,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(int $procedureId, ?int $versionId, string $action, array $metadata = []): void
    {
        ProcedureAudit::create([
            'procedure_id' => $procedureId,
            'version_id' => $versionId,
            'user_id' => Auth::id(),
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
