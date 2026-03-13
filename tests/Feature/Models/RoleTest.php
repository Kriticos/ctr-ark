<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('role can be created with mass assignment', function () {
    $role = Role::create([
        'name' => 'Test Role',
        'slug' => 'test-role',
        'description' => 'Test Description',
    ]);

    expect($role->name)->toBe('Test Role')
        ->and($role->slug)->toBe('test-role')
        ->and($role->description)->toBe('Test Description');
});

test('role has fillable attributes', function () {
    $role = Role::create([
        'name' => 'Manager',
        'slug' => 'manager',
        'description' => 'Manages the system',
    ]);

    expect($role->getFillable())->toContain('name', 'slug', 'description');
});

test('role has users relationship', function () {
    $role = Role::where('slug', 'admin')->first();
    $user = User::factory()->create();

    $user->roles()->attach($role);

    expect($role->users)->toHaveCount(1)
        ->and($role->users->first()->id)->toBe($user->id);
});

test('role has permissions relationship', function () {
    $role = Role::where('slug', 'admin')->first();

    expect($role->permissions)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('hasPermission returns true when role has the permission', function () {
    $role = Role::where('slug', 'editor')->first();

    expect($role->hasPermission('admin.dashboard'))->toBeTrue();
});

test('hasPermission returns false when role does not have the permission', function () {
    $role = Role::where('slug', 'viewer')->first();

    expect($role->hasPermission('admin.users.create'))->toBeFalse();
});

test('isAdmin returns true for admin role', function () {
    $role = Role::where('slug', 'admin')->first();

    expect($role->isAdmin())->toBeTrue();
});

test('isAdmin returns false for non-admin roles', function () {
    $role = Role::where('slug', 'editor')->first();

    expect($role->isAdmin())->toBeFalse();
});

test('role can have multiple users', function () {
    $role = Role::where('slug', 'admin')->first();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->roles()->attach($role);
    $user2->roles()->attach($role);

    $role->refresh();
    expect($role->users)->toHaveCount(2);
});

test('role can have multiple permissions', function () {
    $role = Role::create([
        'name' => 'Custom Role',
        'slug' => 'custom',
        'description' => 'Custom role',
    ]);

    $permissions = Permission::take(3)->get();
    $role->permissions()->attach($permissions->pluck('id')->toArray());

    $role->refresh();
    expect($role->permissions)->toHaveCount(3);
});

test('role timestamps are set on users pivot', function () {
    $role = Role::where('slug', 'admin')->first();
    $user = User::factory()->create();

    $user->roles()->attach($role);

    $pivotData = $user->roles()->first()->pivot;
    expect($pivotData->created_at)->not->toBeNull()
        ->and($pivotData->updated_at)->not->toBeNull();
});

test('role timestamps are set on permissions pivot', function () {
    $role = Role::create([
        'name' => 'Test Role',
        'slug' => 'test',
        'description' => 'Test',
    ]);

    $permission = Permission::first();
    $role->permissions()->attach($permission);

    $pivotData = $role->permissions()->first()->pivot;
    expect($pivotData->created_at)->not->toBeNull()
        ->and($pivotData->updated_at)->not->toBeNull();
});
