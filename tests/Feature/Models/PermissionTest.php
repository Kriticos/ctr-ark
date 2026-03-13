<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
});

test('permission can be created with mass assignment', function () {
    $module = Module::first();

    $permission = Permission::create([
        'name' => 'test.permission',
        'description' => 'Test Permission',
        'module_id' => $module->id,
    ]);

    expect($permission->name)->toBe('test.permission')
        ->and($permission->description)->toBe('Test Permission')
        ->and($permission->module_id)->toBe($module->id);
});

test('permission has fillable attributes', function () {
    $permission = Permission::first();

    expect($permission->getFillable())->toContain('name', 'description', 'module_id');
});

test('permission has module relationship', function () {
    $permission = Permission::where('name', 'admin.users.index')->first();

    expect($permission->module)->toBeInstanceOf(Module::class)
        ->and($permission->module->slug)->toBe('users');
});

test('permission can exist without module', function () {
    $permission = Permission::create([
        'name' => 'standalone.permission',
        'description' => 'Standalone',
        'module_id' => null,
    ]);

    expect($permission->module)->toBeNull();
});

test('permission has roles relationship', function () {
    $permission = Permission::where('name', 'admin.dashboard')->first();

    expect($permission->roles)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($permission->roles->count())->toBeGreaterThan(0);
});

test('permission can be attached to role', function () {
    $permission = Permission::create([
        'name' => 'test.permission',
        'description' => 'Test',
    ]);

    $role = Role::first();
    $role->permissions()->attach($permission);

    $permission->refresh();
    expect($permission->roles->pluck('id'))->toContain($role->id);
    $permission->refresh();
    expect($permission->roles)->toHaveCount(count($permission->roles)); // Vai ter pelo menos 2
});

test('permission timestamps are set on roles pivot', function () {
    $permission = Permission::create([
        'name' => 'test.permission',
        'description' => 'Test',
    ]);

    $role = Role::first();
    $role->permissions()->attach($permission);

    $pivotData = $permission->roles()->first()->pivot;
    expect($pivotData->created_at)->not->toBeNull()
        ->and($pivotData->updated_at)->not->toBeNull();
});

test('permission belongs to correct module', function () {
    $permission = Permission::where('name', 'admin.roles.index')->first();

    expect($permission->module->slug)->toBe('acl');
});

test('permission module relationship returns null when no module', function () {
    $permission = Permission::create([
        'name' => 'test.orphan',
        'description' => 'Orphan permission',
        'module_id' => null,
    ]);

    expect($permission->module)->toBeNull();
});
