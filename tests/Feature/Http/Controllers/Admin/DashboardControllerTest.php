<?php

use App\Models\Procedure;
use App\Models\Sector;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

test('dashboard counts procedures without approval as draft plus in review', function () {
    $user = actingAsAdmin();

    $sector = Sector::create([
        'name' => 'Qualidade',
        'slug' => 'qualidade',
        'is_active' => true,
    ]);

    $draft = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Procedimento em rascunho',
        'slug' => 'procedimento-rascunho',
        'status' => Procedure::STATUS_DRAFT,
    ]);
    $draft->sectors()->sync([$sector->id]);

    $inReview = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Procedimento em revisao',
        'slug' => 'procedimento-revisao',
        'status' => Procedure::STATUS_IN_REVIEW,
    ]);
    $inReview->sectors()->sync([$sector->id]);

    $approved = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Procedimento aprovado',
        'slug' => 'procedimento-aprovado',
        'status' => Procedure::STATUS_APPROVED,
    ]);
    $approved->sectors()->sync([$sector->id]);

    $published = Procedure::create([
        'sector_id' => $sector->id,
        'created_by' => $user->id,
        'title' => 'Procedimento publicado',
        'slug' => 'procedimento-publicado',
        'status' => Procedure::STATUS_PUBLISHED,
    ]);
    $published->sectors()->sync([$sector->id]);

    get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('pendingApproval', 2);
});

test('dashboard sem aprovacao card links to procedures list filtered by review status', function () {
    actingAsAdmin();

    get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.procedures.index', ['status' => 'review']), false);
});
