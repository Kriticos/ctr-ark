<?php

declare(strict_types=1);

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

// ========================================
// INDEX TESTS
// ========================================

test('role index requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.roles.index');
});

test('role index displays list of roles', function () {
    Role::factory(3)->create();

    $response = getAsAdmin('admin.roles.index');

    $response->assertOk()
        ->assertViewIs('admin.roles.index')
        ->assertViewHas('roles');
});

test('role index shows pagination', function () {
    // Criar roles com nomes e slugs únicos
    for ($i = 1; $i <= 20; $i++) {
        Role::factory()->create([
            'name' => "Test Role {$i}",
            'slug' => "test-role-{$i}",
        ]);
    }

    $response = getAsAdmin('admin.roles.index');
    $roles = $response->viewData('roles');
    expect($roles->count())->toBeLessThanOrEqual(15);
});

test('role index searches by name', function () {
    $specificRole = Role::factory()->create(['name' => 'Specific Role']);
    Role::factory()->create(['name' => 'Other Role']);

    $response = getAsAdmin('admin.roles.index', ['search' => 'Specific']);

    $roles = $response->viewData('roles');
    expect($roles->contains('id', $specificRole->id))->toBeTrue();
});

// ========================================
// CREATE TESTS
// ========================================

test('role create requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.roles.create');
});

test('role create shows form with permissions', function () {
    $module = Module::create([
        'name' => 'ACL',
        'slug' => 'acl',
        'description' => 'Access control',
        'icon' => 'fas fa-lock',
        'order' => 1,
    ]);
    $permA = Permission::factory()->create(['module_id' => $module->id, 'name' => 'z-permission']);
    $permB = Permission::factory()->create(['module_id' => $module->id, 'name' => 'a-permission']);

    $response = getAsAdmin('admin.roles.create');

    $response->assertOk()
        ->assertViewIs('admin.roles.create')
        ->assertViewHas('modules');

    $modules = $response->viewData('modules');
    $firstModule = $modules->first();
    expect($firstModule->permissions->pluck('name')->toArray())
        ->toBe(['a-permission', 'z-permission']);
});

// ========================================
// STORE TESTS
// ========================================

test('role store requires authentication', function () {
    post(route('admin.roles.store'), [])
        ->assertRedirect(route('login'));
});

test('role store creates new role', function () {

    $response = postAsAdmin('admin.roles.store', [
        'name' => 'New Role',
        'slug' => 'new-role',
        'description' => 'A new role for testing',
    ]);

    $response->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('roles', [
        'name' => 'New Role',
    ]);
});

test('role store validates required fields', function () {
    postAsAdmin('admin.roles.store', [])
        ->assertSessionHasErrors(['name']);
});

test('role store prevents duplicate names', function () {
    Role::factory()->create(['name' => 'Duplicate Role']);

    postAsAdmin('admin.roles.store', [
        'name' => 'Duplicate Role',
        'description' => 'Another duplicate',
    ])
        ->assertSessionHasErrors('name');
});

test('role store without permissions creates role without relations', function () {

    $slug = 'role-without-permissions';

    postAsAdmin('admin.roles.store', [
        'name' => 'Role Without Permissions',
        'slug' => $slug,
        'description' => 'No permissions provided',
    ])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role = Role::where('slug', $slug)->first();

    expect($role)->not()->toBeNull();
    expect($role->permissions)->toHaveCount(0);
});

test('role store attaches permissions', function () {
    $permissions = Permission::factory(3)->create();

    postAsAdmin('admin.roles.store', [
        'name' => 'Role With Permissions',
        'slug' => 'role-with-permissions',
        'description' => 'Testing role with permissions',
        'permissions' => $permissions->pluck('id')->toArray(),
    ])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role = Role::where('name', 'Role With Permissions')->first();
    expect($role->permissions->count())->toBe(3);
});

// ========================================
// EDIT TESTS
// ========================================

test('role edit requires authentication', function () {
    $role = Role::factory()->create();
    assertGuestIsRedirectedToLogin('admin.roles.edit', ['role' => $role]);
});

test('role edit shows form with current data', function () {
    $module = Module::create([
        'name' => 'Reports',
        'slug' => 'reports',
        'description' => 'Reporting',
        'icon' => 'fas fa-chart',
        'order' => 2,
    ]);
    $permissions = Permission::factory(2)->create(['module_id' => $module->id]);
    $role = Role::factory()->create();
    $role->permissions()->attach($permissions);

    $response = getAsAdmin('admin.roles.edit', ['role' => $role]);

    $response->assertOk()
        ->assertViewIs('admin.roles.edit')
        ->assertViewHas('role', $role)
        ->assertViewHas('modules')
        ->assertViewHas('rolePermissions');

    $modules = $response->viewData('modules');
    $firstModule = $modules->first();
    expect($firstModule->permissions->pluck('name')->values()->toArray())
        ->toBe($firstModule->permissions->pluck('name')->sort()->values()->toArray());
});

test('role edit shows selected permissions', function () {
    /** @var Illuminate\Foundation\Testing\TestCase $this */
    $role = Role::factory()->create();
    $permissions = Permission::factory(2)->create();
    $role->permissions()->attach($permissions);

    $response = getAsAdmin('admin.roles.edit', ['role' => $role]);
    $response->assertOk();

    // Verifica que a role tem as permissions anexadas
    $role->refresh();
    expect($role->permissions->count())->toBe(2);
});

test('role show displays aggregated data', function () {
    $module = Module::create([
        'name' => 'Reports',
        'slug' => 'reports',
        'description' => 'Reporting module',
        'icon' => 'fas fa-chart-line',
        'order' => 1,
    ]);
    $role = Role::factory()->create();
    $permissions = Permission::factory(2)->create(['module_id' => $module->id]);
    $role->permissions()->attach($permissions);

    // Anexa usuários à role para cobrir contagem
    $users = User::factory(2)->create();
    $users->each(fn ($user) => $user->roles()->attach($role));

    $response = getAsAdmin('admin.roles.show', ['role' => $role]);
    $response->assertOk()
        ->assertViewIs('admin.roles.show')
        ->assertViewHasAll(['role', 'totalUsers', 'totalPermissions', 'totalModules', 'permissionsByModule']);

    $viewData = $response->viewData('permissionsByModule');
    expect($response->viewData('totalUsers'))->toBe(2);
    expect($response->viewData('totalPermissions'))->toBe(2);
    expect($response->viewData('totalModules'))->toBe(1);
    expect($viewData->first()['module'])->toBe($module->name);
});

// ========================================
// UPDATE TESTS
// ========================================

test('role update requires authentication', function () {
    $role = Role::factory()->create();
    assertGuestIsRedirectedToLogin('admin.roles.update', ['role' => $role]);
});

test('role update modifies role data', function () {
    $role = Role::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $response = patchAsAdmin('admin.roles.update', [
        'name' => 'Updated Name',
        'slug' => 'updated-name',
        'description' => 'Updated description',
    ], ['role' => $role]);

    $response->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role->refresh();
    expect($role->name)->toBe('Updated Name');
});

test('role update validates unique name', function () {
    $role1 = Role::factory()->create(['name' => 'Role One']);
    $role2 = Role::factory()->create(['name' => 'Role Two']);

    patchAsAdmin('admin.roles.update', [
        'name' => 'Role One',
    ], ['role' => $role2])
        ->assertSessionHasErrors('name');
});

test('role update syncs permissions', function () {
    $role = Role::factory()->create();
    $oldPermissions = Permission::factory(2)->create();
    $newPermissions = Permission::factory(3)->create();
    $role->permissions()->attach($oldPermissions);

    patchAsAdmin('admin.roles.update', [
        'name' => $role->name,
        'slug' => $role->slug,
        'permissions' => $newPermissions->pluck('id')->toArray(),
    ], ['role' => $role])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role->refresh();
    expect($role->permissions->count())->toBe(3);
    expect($role->permissions->contains('id', $newPermissions->first()->id))->toBeTrue();
});

test('role update without permissions key clears permissions', function () {
    $role = Role::factory()->create();
    $permissions = Permission::factory(2)->create();
    $role->permissions()->attach($permissions);

    patchAsAdmin('admin.roles.update', [
        'name' => 'Keeps Name',
        'slug' => 'keeps-name',
        'description' => 'No permissions sent',
    ], ['role' => $role])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role->refresh();
    expect($role->permissions)->toHaveCount(0);
});

test('role update can remove all permissions', function () {
    /** @var Illuminate\Foundation\Testing\TestCase $this */
    $role = Role::factory()->create();
    $permissions = Permission::factory(2)->create();
    $role->permissions()->attach($permissions);

    patchAsAdmin('admin.roles.update', [
        'name' => $role->name,
        'slug' => $role->slug,
        'permissions' => [],
    ], ['role' => $role])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $role->refresh();
    expect($role->permissions->count())->toBe(0);
});

// ========================================
// DESTROY TESTS
// ========================================

test('role destroy requires authentication', function () {
    $role = Role::factory()->create();
    assertGuestIsRedirectedToLogin('admin.roles.destroy', ['role' => $role]);
});

test('role destroy deletes role', function () {
    /** @var Illuminate\Foundation\Testing\TestCase $this */
    $role = Role::factory()->create();
    $roleId = $role->id;

    $response = deleteAsAdmin('admin.roles.destroy', ['role' => $role]);
    $response->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    assertDatabaseMissing('roles', ['id' => $roleId]);
});

test('role destroy detaches permissions', function () {
    $role = Role::factory()->create();
    $permissions = Permission::factory(3)->create();
    $role->permissions()->attach($permissions);

    deleteAsAdmin('admin.roles.destroy', ['role' => $role]);
    assertDatabaseMissing('permission_role', ['role_id' => $role->id]);
});

test('role destroy detaches users', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $user->roles()->attach($role);

    deleteAsAdmin('admin.roles.destroy', ['role' => $role]);

    $user->refresh();
    expect($user->roles->contains('id', $role->id))->toBeFalse();
});

// ========================================
// ANOMALY TESTS
// ========================================

test('role store with very long name fails', function () {

    postAsAdmin('admin.roles.store', [
        'name' => str_repeat('A', 256),
        'description' => 'Long name test',
    ])
        ->assertSessionHasErrors('name');
});

test('role update cannot change to duplicate name', function () {
    /** @var Illuminate\Foundation\Testing\TestCase $this */
    $role1 = Role::factory()->create(['name' => 'Original']);
    $role2 = Role::factory()->create(['name' => 'Another']);

    // Tentar renomear para um que já existe
    patchAsAdmin('admin.roles.update', [
        'name' => 'Another',
    ], ['role' => $role1])
        ->assertSessionHasErrors('name');
});

test('role index search with special characters', function () {
    Role::factory()->create(['name' => 'Role & Special!']);
    Role::factory()->create(['name' => 'Normal Role']);

    $response = getAsAdmin('admin.roles.index', ['search' => '&']);
    $roles = $response->viewData('roles');
    expect($roles->count())->toBeGreaterThan(0);
});

test('role store with empty permissions list', function () {

    $response = postAsAdmin('admin.roles.store', [
        'name' => 'Role Without Permissions',
        'slug' => 'role-without-permissions',
        'description' => 'Testing empty permissions',
        'permissions' => [],
    ]);

    $response->assertRedirect();
    $role = Role::where('name', 'Role Without Permissions')->first();
    expect($role->permissions->count())->toBe(0);
});

test('role destroy prevents deletion if system role', function () {
    $adminRole = Role::where('slug', 'admin')->first();

    $response = deleteAsAdmin('admin.roles.destroy', ['role' => $adminRole]);

    // Sistema deve prevenir deleção de roles do sistema
    $response->assertRedirect()
        ->assertSessionHas('error');

    assertDatabaseHas('roles', ['id' => $adminRole->id]);
});
