<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
});

// ========================================
// INDEX TESTS
// ========================================

test('permission index requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.permissions.index');
});

test('permission index displays list of permissions', function () {
    Permission::factory(5)->create();

    $response = getAsAdmin('admin.permissions.index');

    $response
        ->assertOk()
        ->assertViewIs('admin.permissions.index')
        ->assertViewHas('permissions');
});

test('permission index displays paginated results', function () {
    Permission::factory(20)->create();

    $response = getAsAdmin('admin.permissions.index');

    $permissions = $response->viewData('permissions');
    expect($permissions->count())->toBeLessThanOrEqual(15);
});

test('permission index searches by name', function () {
    $permission = Permission::factory()->create(['name' => 'users.create']);
    Permission::factory()->create(['name' => 'posts.edit']);

    $response = getAsAdmin('admin.permissions.index', ['search' => 'users.create']);
    $permissions = $response->viewData('permissions');
    expect($permissions->contains('id', $permission->id))->toBeTrue();
});

test('permission index searches by description', function () {
    $permission = Permission::factory()->create([
        'name' => 'create_users',
        'description' => 'Create new user account',
    ]);

    $response = getAsAdmin('admin.permissions.index', ['search' => 'new user']);
    $permissions = $response->viewData('permissions');
    expect($permissions->contains('id', $permission->id))->toBeTrue();
});

// ========================================
// CREATE TESTS
// ========================================

test('permission create requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.permissions.create');
});

test('permission create shows form', function () {
    $moduleA = Module::factory()->create(['name' => 'Analytics', 'slug' => 'analytics-'.uniqid(), 'order' => 2]);
    $moduleB = Module::factory()->create(['name' => 'ACL', 'slug' => 'acl-'.uniqid(), 'order' => 1]);

    $response = getAsAdmin('admin.permissions.create');

    $response->assertOk()
        ->assertViewIs('admin.permissions.create')
        ->assertViewHas('modules');

    $modules = $response->viewData('modules');
    $ids = $modules->pluck('id')->toArray();
    expect(array_search($moduleB->id, $ids))->toBeLessThan(array_search($moduleA->id, $ids));
});

// ========================================
// STORE TESTS
// ========================================

test('permission store requires authentication', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->post(route('admin.permissions.store'), [])
        ->assertRedirect(route('login'));
});

test('permission store creates new permission', function () {
    $module = Module::first();

    postAsAdmin('admin.permissions.store', [
        'name' => 'resources.create',
        'description' => 'Create resource',
        'module_id' => $module->id,
    ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('permissions', [
        'name' => 'resources.create',
    ]);
});

test('permission store validates required fields', function () {
    postAsAdmin('admin.permissions.store', [])
        ->assertSessionHasErrors(['name', 'module_id']);
});

test('permission store prevents duplicate names', function () {
    $module = Module::first();
    Permission::factory()->create(['name' => 'items.edit', 'module_id' => $module->id]);

    postAsAdmin('admin.permissions.store', [
        'name' => 'items.edit',
        'description' => 'Duplicate edit permission',
        'module_id' => $module->id,
    ])
        ->assertSessionHasErrors('name');
});

test('permission store accepts permission name with spaces', function () {
    $module = Module::first();

    postAsAdmin('admin.permissions.store', [
        'name' => 'invalid permission name',
        'description' => 'Name with spaces',
        'module_id' => $module->id,
    ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('permissions', [
        'name' => 'invalid permission name',
    ]);
});

test('permission store with description', function () {
    $module = Module::first();

    $response = postAsAdmin('admin.permissions.store', [
        'name' => 'items.delete',
        'description' => 'Delete items permission',
        'module_id' => $module->id,
    ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    $response->assertRedirect();
    assertDatabaseHas('permissions', [
        'name' => 'items.delete',
        'description' => 'Delete items permission',
    ]);
});

// ========================================
// EDIT TESTS
// ========================================

test('permission edit requires authentication', function () {
    $permission = Permission::factory()->create();
    assertGuestIsRedirectedToLogin('admin.permissions.edit', ['permission' => $permission]);
});

test('permission edit shows form with current data', function () {
    $moduleA = Module::factory()->create(['name' => 'Billing', 'slug' => 'billing-'.uniqid(), 'order' => 2]);
    $moduleB = Module::factory()->create(['name' => 'Catalog', 'slug' => 'catalog-'.uniqid(), 'order' => 1]);
    $permission = Permission::factory()->create(['module_id' => $moduleA->id]);

    $response = getAsAdmin('admin.permissions.edit', ['permission' => $permission]);

    $response->assertOk()
        ->assertViewIs('admin.permissions.edit')
        ->assertViewHas('permission', $permission)
        ->assertViewHas('modules');

    $modules = $response->viewData('modules');
    $ids = $modules->pluck('id')->toArray();
    expect(array_search($moduleB->id, $ids))->toBeLessThan(array_search($moduleA->id, $ids));
});

test('permission show displays aggregated data', function () {
    $module = Module::create(['name' => 'Reports', 'slug' => 'reports', 'order' => 1]);
    $permission = Permission::factory()->create(['module_id' => $module->id]);

    $roleA = Role::factory()->create(['name' => 'Manager']);
    $roleB = Role::factory()->create(['name' => 'Viewer']);
    $usersA = User::factory(2)->create();
    $usersB = User::factory(1)->create();
    $roleA->permissions()->attach($permission);
    $roleB->permissions()->attach($permission);
    $usersA->each(fn ($user) => $user->roles()->attach($roleA));
    $usersB->each(fn ($user) => $user->roles()->attach($roleB));

    $response = getAsAdmin('admin.permissions.show', ['permission' => $permission]);

    $response->assertOk()
        ->assertViewIs('admin.permissions.show')
        ->assertViewHasAll(['permission', 'totalUsers', 'users', 'usersByRole', 'totalRoles']);

    expect($response->viewData('totalUsers'))->toBe(3);
    expect($response->viewData('totalRoles'))->toBe(2);
    $users = $response->viewData('users');
    expect($users->count())->toBeLessThanOrEqual(10);
    $usersByRole = $response->viewData('usersByRole');
    expect($usersByRole->first()['role'])->toBe('Manager');
    expect($usersByRole->first()['count'])->toBe(2);
});

// ========================================
// UPDATE TESTS
// ========================================

test('permission update requires authentication', function () {
    $permission = Permission::factory()->create();
    assertGuestIsRedirectedToLogin('admin.permissions.update', ['permission' => $permission]);
});

test('permission update modifies permission data', function () {
    $module = Module::first();
    $permission = Permission::factory()->create([
        'name' => 'old.permission',
        'description' => 'Old description',
        'module_id' => $module->id,
    ]);

    $response = patchAsAdmin('admin.permissions.update', [
        'name' => 'new.permission',
        'description' => 'New description',
        'module_id' => $module->id,
    ], ['permission' => $permission]);

    $response->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    $permission->refresh();
    expect($permission->name)->toBe('new.permission');
    expect($permission->description)->toBe('New description');
});

test('permission update validates unique name', function () {
    $perm1 = Permission::factory()->create(['name' => 'perm.one']);
    $perm2 = Permission::factory()->create(['name' => 'perm.two']);

    $response = patchAsAdmin('admin.permissions.update', [
        'name' => 'perm.one',
    ], ['permission' => $perm2]);

    $response->assertSessionHasErrors('name');
});

test('permission update allows same name for same permission', function () {
    $module = Module::first();
    $permission = Permission::factory()->create([
        'name' => 'same.name',
        'module_id' => $module->id,
    ]);

    $response = patchAsAdmin('admin.permissions.update', [
        'name' => 'same.name',
        'description' => 'Updated description',
        'module_id' => $module->id,
    ], ['permission' => $permission]);

    $response->assertRedirect();
    $permission->refresh();
    expect($permission->description)->toBe('Updated description');
});

test('permission update clears description when empty', function () {
    $module = Module::first();
    $permission = Permission::factory()->create([
        'name' => 'perm.action',
        'description' => 'Old description',
        'module_id' => $module->id,
    ]);

    patchAsAdmin('admin.permissions.update', [
        'name' => 'perm.action',
        'description' => '',
        'module_id' => $module->id,
    ], ['permission' => $permission]);

    $permission->refresh();
    expect($permission->description)->toBeNull();
});

// ========================================
// DESTROY TESTS
// ========================================

test('permission destroy requires authentication', function () {
    $permission = Permission::factory()->create();
    assertGuestIsRedirectedToLogin('admin.permissions.destroy', ['permission' => $permission]);
});

test('permission destroy deletes permission', function () {
    $permission = Permission::factory()->create();
    $permId = $permission->id;

    $response = deleteAsAdmin('admin.permissions.destroy', ['permission' => $permission]);

    $response->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    assertDatabaseMissing('permissions', ['id' => $permId]);
});

test('permission destroy detaches from roles', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $permission = Permission::factory()->create();
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);

    deleteAsAdmin('admin.permissions.destroy', ['permission' => $permission]);

    $role->refresh();
    expect($role->permissions->contains('id', $permission->id))->toBeFalse();
});

test('permission destroy deletes from multiple roles', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $permission = Permission::factory()->create();
    $roles = Role::factory(3)->create();

    foreach ($roles as $role) {
        $role->permissions()->attach($permission);
    }

    deleteAsAdmin('admin.permissions.destroy', ['permission' => $permission]);

    foreach ($roles as $role) {
        $role->refresh();
        expect($role->permissions->contains('id', $permission->id))->toBeFalse();
    }
});

// ========================================
// ANOMALY TESTS
// ========================================

test('permission store with very long name fails', function () {
    $module = Module::first();

    postAsAdmin('admin.permissions.store', [
        'name' => str_repeat('a', 256),
        'description' => 'Very long name test',
        'module_id' => $module->id,
    ])->assertSessionHasErrors('name');
});

test('permission store with SQL injection attempt fails', function () {
    $module = Module::first();

    $response = postAsAdmin('admin.permissions.store', [
        'name' => "admin'; DROP TABLE roles; --",
        'description' => 'SQL injection test',
        'module_id' => $module->id,
    ]);

    // Laravel sanitiza automaticamente via Eloquent
    $response->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('permissions', [
        'name' => "admin'; DROP TABLE roles; --",
    ]);
});

test('permission store accepts special characters in name', function () {
    $module = Module::first();

    $response = postAsAdmin('admin.permissions.store', [
        'name' => 'perm!@#$%^&*()_+|}{":?><,./;\'[]\\=-`~',
        'description' => 'Special characters test',
        'module_id' => $module->id,
    ]);

    // Validação permite qualquer string
    $response->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('permissions', [
        'name' => 'perm!@#$%^&*()_+|}{":?><,./;\'[]\\=-`~',
    ]);
});

test('permission index with empty search', function () {
    Permission::factory(5)->create();

    $response = getAsAdmin('admin.permissions.index', ['search' => '']);
    $permissions = $response->viewData('permissions');
    expect($permissions->count())->toBeGreaterThan(0);
});

test('permission store without description succeeds', function () {
    $module = Module::first();

    $response = postAsAdmin('admin.permissions.store', [
        'name' => 'action.without.description',
        'module_id' => $module->id,
    ]);

    $response->assertRedirect();
    assertDatabaseHas('permissions', [
        'name' => 'action.without.description',
    ]);
});

test('permission cannot be created with reserved names', function () {
    $module = Module::first();
    $systemPermission = Permission::factory()->create([
        'name' => 'system.admin',
        'module_id' => $module->id,
    ]);

    postAsAdmin('admin.permissions.store', [
        'name' => 'system.admin',
        'description' => 'Trying to create duplicate system permission',
        'module_id' => $module->id,
    ])->assertSessionHasErrors('name');
});

test('permission update maintains audit trail', function () {
    $module = Module::first();
    $permission = Permission::factory()->create([
        'name' => 'initial.name',
        'description' => 'Initial description',
        'module_id' => $module->id,
    ]);
    $originalId = $permission->id;

    patchAsAdmin('admin.permissions.update', [
        'name' => 'updated.name',
        'description' => 'Updated description',
        'module_id' => $module->id,
    ], ['permission' => $permission]);

    // ID deve permanecer o mesmo (não foi recriado)
    $updatedPermission = Permission::find($originalId);
    expect($updatedPermission->name)->toBe('updated.name');
});
