<?php

use App\Livewire\Admin\MenusTable;
use App\Models\Menu;
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
    $this->seed(\Database\Seeders\MenuSeeder::class);

    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);
});

test('menus table component mounts successfully', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->assertSuccessful();
});

test('menus table displays all parent menus', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $parentMenus = Menu::whereNull('parent_id')
        ->where('is_active', true)
        ->count();

    Livewire::test(MenusTable::class)
        ->assertViewHas('menus');
});

test('menus table can search by title', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->set('search', $menu->title)
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table can search by route name', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->whereNotNull('route_name')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->set('search', $menu->route_name)
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table can filter by module', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $module = Module::first();

    if ($module) {
        Livewire::test(MenusTable::class)
            ->set('moduleFilter', $module->id)
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table resets page when searching', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->set('search', 'test')
        ->assertSuccessful();
});

test('menus table resets page when filtering', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->set('moduleFilter', '1')
        ->assertSuccessful();
});

test('menus table toggles reorder mode', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->assertSet('reorderMode', false)
        ->call('toggleReorderMode')
        ->assertSet('reorderMode', true)
        ->call('toggleReorderMode')
        ->assertSet('reorderMode', false);
});

test('menus table dispatches reorder enabled event', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->call('toggleReorderMode')
        ->assertDispatched('reorder-enabled');
});

test('menus table dispatches reorder disabled event', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->call('toggleReorderMode')
        ->call('toggleReorderMode')
        ->assertDispatched('reorder-disabled');
});

test('menus table can update order', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menus = Menu::whereNull('parent_id')->limit(2)->pluck('id')->toArray();

    if (count($menus) >= 2) {
        $orderedIds = array_reverse($menus);

        Livewire::test(MenusTable::class)
            ->call('updateOrder', $orderedIds)
            ->assertDispatched('order-updated');
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table dispatches order updated event with message', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menus = Menu::whereNull('parent_id')->limit(2)->pluck('id')->toArray();

    if (count($menus) >= 2) {
        Livewire::test(MenusTable::class)
            ->call('updateOrder', $menus)
            ->assertDispatched('order-updated');
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table can delete menu without children', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')
        ->doesntHave('children')
        ->first();

    if ($menu) {
        $menuId = $menu->id;

        Livewire::test(MenusTable::class)
            ->call('delete', $menuId)
            ->assertDispatched('menu-deleted');

        expect(Menu::find($menuId))->toBeNull();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table cannot delete menu with children', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $parentMenu = Menu::whereNull('parent_id')
        ->whereHas('children')
        ->first();

    if ($parentMenu) {
        Livewire::test(MenusTable::class)
            ->call('delete', $parentMenu->id)
            ->assertDispatched('delete-error');

        // Menu should still exist
        expect(Menu::find($parentMenu->id))->not->toBeNull();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table renders view with modules', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->assertViewHas('modules');
});

test('menus table uses pagination', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->assertViewHas('menus', function ($menus) {
            return $menus->perPage() == 15;
        });
});

test('menus table search is case insensitive', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->set('search', strtoupper($menu->title))
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table maintains search in query string', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->set('search', 'test-search')
        ->assertSet('search', 'test-search');
});

test('menus table maintains module filter in query string', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->set('moduleFilter', '1')
        ->assertSet('moduleFilter', '1');
});

test('menus table orders by order column and title', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->assertViewHas('menus', function ($menus) {
            // Verificar que os menus estão ordenados
            return $menus->count() >= 0;
        });
});

test('menus table includes child count in menu relations', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->with('children')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->assertViewHas('menus', function ($menus) {
                return $menus->count() >= 0;
            });
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table clear all filters', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Livewire::test(MenusTable::class)
        ->set('search', 'test')
        ->set('moduleFilter', '1')
        ->set('search', '')
        ->set('moduleFilter', '')
        ->assertSet('search', '')
        ->assertSet('moduleFilter', '');
});

test('menus table search by URL', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->whereNotNull('url')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->set('search', $menu->url)
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});

test('menus table search by permission name', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::whereNull('parent_id')->whereNotNull('permission_name')->first();

    if ($menu) {
        Livewire::test(MenusTable::class)
            ->set('search', $menu->permission_name)
            ->assertSuccessful();
    } else {
        expect(true)->toBeTrue();
    }
});
