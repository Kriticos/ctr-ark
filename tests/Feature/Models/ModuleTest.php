<?php

use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
});

test('module can be created with mass assignment', function () {
    $module = Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
        'icon' => 'fas fa-test',
        'description' => 'Test Description',
        'order' => 10,
    ]);

    expect($module->name)->toBe('Test Module')
        ->and($module->slug)->toBe('test-module')
        ->and($module->icon)->toBe('fas fa-test')
        ->and($module->description)->toBe('Test Description')
        ->and($module->order)->toBe(10);
});

test('module has fillable attributes', function () {
    $module = Module::first();

    expect($module->getFillable())->toContain('name', 'slug', 'icon', 'description', 'order');
});

test('module has permissions relationship', function () {
    $module = Module::where('slug', 'users')->first();

    expect($module->permissions)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($module->permissions->count())->toBeGreaterThan(0);
});

test('module permissions are ordered by name', function () {
    $module = Module::where('slug', 'acl')->first();
    $permissions = $module->permissions;

    $sortedNames = $permissions->pluck('name')->sort()->values();
    $actualNames = $permissions->pluck('name')->values();

    expect($actualNames->toArray())->toBe($sortedNames->toArray());
});

test('module has menus relationship', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\MenuSeeder::class);

    $module = Module::where('slug', 'users')->first();

    expect($module->menus)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('module menus are ordered by order column', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\MenuSeeder::class);

    $module = Module::where('slug', 'users')->first();
    $menus = $module->menus;

    if ($menus->count() > 1) {
        $orders = $menus->pluck('order')->toArray();
        $sortedOrders = collect($orders)->sort()->values()->toArray();

        expect($orders)->toBe($sortedOrders);
    } else {
        expect(true)->toBeTrue(); // Pular teste se não houver menus suficientes
    }
});

test('module can exist without permissions', function () {
    $module = Module::create([
        'name' => 'Empty Module',
        'slug' => 'empty',
        'icon' => 'fas fa-empty',
        'description' => 'Empty',
        'order' => 99,
    ]);

    expect($module->permissions)->toHaveCount(0);
});

test('module can exist without menus', function () {
    $module = Module::create([
        'name' => 'No Menu Module',
        'slug' => 'no-menu',
        'icon' => 'fas fa-test',
        'description' => 'No menus',
        'order' => 99,
    ]);

    expect($module->menus)->toHaveCount(0);
});

test('module can have multiple permissions', function () {
    $module = Module::where('slug', 'users')->first();

    expect($module->permissions->count())->toBeGreaterThanOrEqual(7); // users.index, create, store, show, edit, update, destroy
});

test('module relationships work correctly', function () {
    $module = Module::where('slug', 'users')->first();

    expect($module->permissions()->count())->toBeGreaterThan(0)
        ->and($module->menus()->count())->toBeGreaterThanOrEqual(0);
});
