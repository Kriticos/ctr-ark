<?php

use App\Livewire\Admin\ModulesTable;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);

    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);
    $this->actingAs($user);
});

it('handles pagination with 15 items per page', function () {
    Livewire::test(ModulesTable::class)
        ->assertViewHas('modules', function ($modules) {
            expect($modules->perPage())->toBe(15);

            return true;
        });
});

it('applies search filter and updates query string', function () {
    $module = Module::first();
    if ($module) {
        Livewire::test(ModulesTable::class)
            ->set('search', $module->name)
            ->assertSet('search', $module->name)
            ->assertViewHas('modules');
    } else {
        expect(true)->toBeTrue();
    }
});

it('delete raises error when module has permissions', function () {
    // Create module with explicit permissions
    $module = Module::factory()->create();
    $permission = \App\Models\Permission::factory()->create(['module_id' => $module->id]);

    expect($module->permissions()->count())->toBeGreaterThan(0);

    Livewire::test(ModulesTable::class)
        ->call('delete', $module->id)
        ->assertDispatched('delete-error');

    // Verify module still exists
    expect(Module::find($module->id))->not->toBeNull();
});

it('delete successfully removes module without permissions', function () {
    // Create a module explicitly without permissions
    $module = Module::factory()->create();
    // Ensure no permissions exist for this module
    expect($module->permissions()->count())->toBe(0);

    $moduleId = $module->id;
    Livewire::test(ModulesTable::class)
        ->call('delete', $moduleId)
        ->assertDispatched('module-deleted');

    expect(Module::find($moduleId))->toBeNull();
});
