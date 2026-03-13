<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

// ========================================
// INDEX
// ========================================

test('module index requires authentication', function () {
    get(route('admin.modules.index'))
        ->assertRedirect(route('login'));
});

test('module index displays list with search and pagination', function () {
    actingAsAdmin();

    $match = Module::factory()->create([
        'name' => 'Analytics Module',
        'slug' => 'analytics-'.uniqid(),
        'order' => 1,
    ]);

    Module::factory(5)->create();

    $response = get(route('admin.modules.index', [
        'search' => 'Analytics',
    ]));

    $response
        ->assertOk()
        ->assertViewIs('admin.modules.index')
        ->assertViewHas('modules');

    expect($response->viewData('modules')->pluck('id'))
        ->toContain($match->id);
});

// ========================================
// CREATE
// ========================================

test('module create requires authentication', function () {
    get(route('admin.modules.create'))
        ->assertRedirect(route('login'));
});

test('module create shows form', function () {
    actingAsAdmin();

    get(route('admin.modules.create'))
        ->assertOk()
        ->assertViewIs('admin.modules.create');
});

// ========================================
// STORE
// ========================================

test('module store requires authentication', function () {
    post(route('admin.modules.store'), [])
        ->assertRedirect(route('login'));
});

test('module store creates module', function () {
    actingAsAdmin();

    post(route('admin.modules.store'), [
        'name' => 'Billing',
        'slug' => 'billing',
        'description' => 'Handles billing',
        'icon' => 'fas fa-money',
        'order' => 3,
    ])
        ->assertRedirect(route('admin.modules.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('modules', [
        'name' => 'Billing',
        'slug' => 'billing',
        'order' => 3,
    ]);
});

// ========================================
// SHOW
// ========================================

test('module show requires authentication', function () {
    $module = Module::factory()->create();
    get(route('admin.modules.show', $module))
        ->assertRedirect(route('login'));
});

test('module show displays statistics and aggregates', function () {

    $module = Module::factory()->create(['name' => 'Reports', 'slug' => 'reports-'.uniqid()]);

    $permA = Permission::factory()->create(['module_id' => $module->id, 'name' => 'reports.view-'.uniqid()]);
    $permB = Permission::factory()->create(['module_id' => $module->id, 'name' => 'reports.edit-'.uniqid()]);

    $role1 = Role::factory()->create(['name' => 'Analyst', 'slug' => 'analyst']);
    $role2 = Role::factory()->create(['name' => 'Manager', 'slug' => 'manager']);

    $role1->permissions()->attach([$permA->id]);
    $role2->permissions()->attach([$permA->id, $permB->id]);

    actingAsAdmin();

    $response = get(route('admin.modules.show', $module));

    $response->assertOk()
        ->assertViewIs('admin.modules.show')
        ->assertViewHasAll([
            'module',
            'totalPermissions',
            'totalRoles',
            'roles',
            'permissionsByRole',
            'topPermissions',
        ]);

    expect($response->viewData('totalPermissions'))->toBe(2);
    expect($response->viewData('totalRoles'))->toBe(2);
});

// ========================================
// EDIT
// ========================================

test('module edit requires authentication', function () {
    $module = Module::factory()->create();
    get(route('admin.modules.edit', $module))
        ->assertRedirect(route('login'));
});

test('module edit shows form', function () {
    $module = Module::factory()->create();

    actingAsAdmin();

    get(route('admin.modules.edit', $module))
        ->assertOk()
        ->assertViewIs('admin.modules.edit')
        ->assertViewHas('module', $module);
});

// ========================================
// UPDATE
// ========================================

test('module update requires authentication', function () {
    $module = Module::factory()->create();
    patch(route('admin.modules.update', $module), [])
        ->assertRedirect(route('login'));
});

test('module update modifies data', function () {
    $module = Module::factory()->create([
        'name' => 'Old',
        'slug' => 'old',
        'order' => 1,
    ]);

    actingAsAdmin();

    patch(route('admin.modules.update', $module), [
        'name' => 'New Name',
        'slug' => 'new-slug',
        'order' => 5,
        'description' => 'Updated',
    ])
        ->assertRedirect(route('admin.modules.index'))
        ->assertSessionHas('success');

    $module->refresh();
    expect($module->name)->toBe('New Name');
    expect($module->slug)->toBe('new-slug');
    expect($module->order)->toBe(5);
    expect($module->description)->toBe('Updated');
});

// ========================================
// DESTROY
// ========================================

test('module destroy prevents deletion when has permissions', function () {
    $module = Module::factory()->create();
    Permission::factory()->create(['module_id' => $module->id]);

    actingAsAdmin();

    delete(route('admin.modules.destroy', $module))
        ->assertRedirect(route('admin.modules.index'))
        ->assertSessionHas('error');

    assertDatabaseHas('modules', ['id' => $module->id]);
});

test('module destroy deletes module without permissions', function () {
    $module = Module::factory()->create();

    actingAsAdmin();

    delete(route('admin.modules.destroy', $module))
        ->assertRedirect(route('admin.modules.index'))
        ->assertSessionHas('success');

    assertDatabaseMissing('modules', ['id' => $module->id]);
});
