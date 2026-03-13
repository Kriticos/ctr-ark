<?php

use App\Models\Procedure;
use App\Models\ProcedureApprovalAction;
use App\Models\Sector;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

test('procedure index status review filter shows draft and in review procedures', function () {
    $user = actingAsAdmin();

    $sector = Sector::create([
        'name' => 'Operacoes',
        'slug' => 'operacoes',
        'is_active' => true,
    ]);

    $inReview = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Fluxo em revisao',
        'slug' => 'fluxo-revisao',
        'status' => Procedure::STATUS_IN_REVIEW,
    ]);
    $inReview->sectors()->sync([$sector->id]);

    $draft = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Fluxo draft',
        'slug' => 'fluxo-draft',
        'status' => Procedure::STATUS_DRAFT,
    ]);
    $draft->sectors()->sync([$sector->id]);

    $approved = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Fluxo aprovado',
        'slug' => 'fluxo-aprovado',
        'status' => Procedure::STATUS_APPROVED,
    ]);
    $approved->sectors()->sync([$sector->id]);

    get(route('admin.procedures.index', ['status' => 'review']))
        ->assertOk()
        ->assertViewHas('status', 'review')
        ->assertViewHas('procedures', function ($procedures) use ($draft, $inReview, $approved) {
            $items = $procedures->items();
            $ids = collect($items)->pluck('id');

            return $ids->contains($inReview->id)
                && $ids->contains($draft->id)
                && ! $ids->contains($approved->id);
        });
});

test('procedure index shows rejected last decision badge when latest approval action is rejected', function () {
    $user = actingAsAdmin();

    $sector = Sector::create([
        'name' => 'RH',
        'slug' => 'rh',
        'is_active' => true,
    ]);

    $procedure = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Fluxo com reprovacao',
        'slug' => 'fluxo-com-reprovacao',
        'status' => Procedure::STATUS_DRAFT,
    ]);
    $procedure->sectors()->sync([$sector->id]);

    ProcedureApprovalAction::create([
        'procedure_id' => $procedure->id,
        'version_id' => null,
        'user_id' => $user->id,
        'action' => 'submitted',
    ]);

    ProcedureApprovalAction::create([
        'procedure_id' => $procedure->id,
        'version_id' => null,
        'user_id' => $user->id,
        'action' => 'rejected',
        'comment' => 'Ajustar passo 2',
    ]);

    get(route('admin.procedures.index', ['status' => 'review']))
        ->assertOk()
        ->assertSee('Última decisão: reprovado');
});

test('procedure preview applies per-image width from markdown syntax', function () {
    actingAsAdmin();

    $response = post(route('admin.procedures.preview'), [
        'markdown_content' => '![Diagrama](https://example.com/fluxo.jpg){width=420}',
    ]);

    $response->assertOk();
    $html = (string) $response->json('html');

    expect($html)
        ->toContain('<img')
        ->toContain('max-width: 420px')
        ->toContain('width: 100%')
        ->not->toContain('{width=420}')
        ->not->toContain('{{imgw:');
});
