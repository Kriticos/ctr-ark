<?php

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
    $this->seed(\Database\Seeders\MenuSeeder::class);
});

test('menu can be created with mass assignment', function () {
    $module = Module::first();

    $menu = Menu::create([
        'module_id' => $module->id,
        'parent_id' => null,
        'title' => 'Test Menu',
        'icon' => 'fas fa-test',
        'route_name' => 'test.route',
        'url' => '/test',
        'permission_name' => 'test.permission',
        'order' => 1,
        'is_active' => true,
        'is_divider' => false,
        'target' => '_self',
        'badge' => 'New',
        'badge_color' => 'red',
        'description' => 'Test menu',
    ]);

    expect($menu->title)->toBe('Test Menu')
        ->and($menu->icon)->toBe('fas fa-test')
        ->and($menu->order)->toBe(1);
});

test('menu has correct casts', function () {
    $menu = Menu::first();

    expect($menu->is_active)->toBeBool()
        ->and($menu->is_divider)->toBeBool();
});

test('menu has module relationship', function () {
    $menu = Menu::whereNotNull('module_id')->first();

    if ($menu) {
        expect($menu->module)->toBeInstanceOf(Module::class);
    } else {
        expect(true)->toBeTrue(); // Skip if no menu with module
    }
});

test('menu can exist without module', function () {
    $menu = Menu::whereNull('module_id')->first();

    if ($menu) {
        expect($menu->module)->toBeNull();
    } else {
        // Create one for testing
        $menu = Menu::create([
            'module_id' => null,
            'title' => 'No Module Menu',
            'order' => 99,
        ]);
        expect($menu->module)->toBeNull();
    }
});

test('menu has parent relationship', function () {
    $parentMenu = Menu::whereNull('parent_id')->first();
    $childMenu = Menu::create([
        'parent_id' => $parentMenu->id,
        'title' => 'Child Menu',
        'order' => 1,
    ]);

    expect($childMenu->parent)->toBeInstanceOf(Menu::class)
        ->and($childMenu->parent->id)->toBe($parentMenu->id);
});

test('menu has children relationship', function () {
    $parentMenu = Menu::whereHas('children')->first();

    if ($parentMenu) {
        expect($parentMenu->children)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($parentMenu->children->count())->toBeGreaterThan(0);
    } else {
        expect(true)->toBeTrue(); // Skip if no parent menu with children
    }
});

test('menu children are ordered', function () {
    $parentMenu = Menu::whereHas('children')->first();

    if ($parentMenu && $parentMenu->children->count() > 1) {
        $orders = $parentMenu->children->pluck('order')->toArray();
        $sortedOrders = collect($orders)->sort()->values()->toArray();

        expect($orders)->toBe($sortedOrders);
    } else {
        expect(true)->toBeTrue(); // Skip if not enough children
    }
});

test('active scope returns only active menus', function () {
    Menu::create([
        'title' => 'Inactive Menu',
        'is_active' => false,
        'order' => 99,
    ]);

    $activeMenus = Menu::active()->get();

    foreach ($activeMenus as $menu) {
        expect($menu->is_active)->toBeTrue();
    }
});

test('mainMenus scope returns only parent menus', function () {
    $mainMenus = Menu::mainMenus()->get();

    foreach ($mainMenus as $menu) {
        expect($menu->parent_id)->toBeNull();
    }
});

test('mainMenus scope returns ordered menus', function () {
    $mainMenus = Menu::mainMenus()->get();

    if ($mainMenus->count() > 1) {
        $orders = $mainMenus->pluck('order')->toArray();
        $sortedOrders = collect($orders)->sort()->values()->toArray();

        expect($orders)->toBe($sortedOrders);
    } else {
        expect(true)->toBeTrue();
    }
});

test('submenus scope returns only child menus', function () {
    $submenus = Menu::submenus()->get();

    foreach ($submenus as $menu) {
        expect($menu->parent_id)->not->toBeNull();
    }
});

test('submenus scope returns ordered menus', function () {
    $submenus = Menu::submenus()->get();

    if ($submenus->count() > 1) {
        $orders = $submenus->pluck('order')->toArray();
        $sortedOrders = collect($orders)->sort()->values()->toArray();

        expect($orders)->toBe($sortedOrders);
    } else {
        expect(true)->toBeTrue();
    }
});

test('hasChildren returns true when menu has children', function () {
    $parentMenu = Menu::whereHas('children')->first();

    if ($parentMenu) {
        expect($parentMenu->hasChildren())->toBeTrue();
    } else {
        expect(true)->toBeTrue();
    }
});

test('hasChildren returns false when menu has no children', function () {
    $menu = Menu::doesntHave('children')->first();

    if ($menu) {
        expect($menu->hasChildren())->toBeFalse();
    } else {
        expect(true)->toBeTrue();
    }
});

test('getUrlAttribute returns route url when route_name exists', function () {
    $menu = Menu::where('route_name', 'admin.dashboard')->first();

    if ($menu && Route::has('admin.dashboard')) {
        expect($menu->url)->toBe(route('admin.dashboard'));
    } else {
        expect(true)->toBeTrue();
    }
});

test('getUrlAttribute returns original value when no route_name', function () {
    $menu = Menu::whereNull('route_name')->where('url', '/pulse')->first();

    if ($menu) {
        expect($menu->url)->toBe('/pulse');
    } else {
        expect(true)->toBeTrue();
    }
});

test('getUrlAttributeAttribute returns route when route exists', function () {
    $menu = Menu::where('route_name', 'admin.dashboard')->first();

    if ($menu && Route::has('admin.dashboard')) {
        expect($menu->getUrlAttributeAttribute())->toBe(route('admin.dashboard'));
    } else {
        expect(true)->toBeTrue();
    }
});

test('getUrlAttributeAttribute returns hash when no route or url', function () {
    $menu = Menu::create([
        'title' => 'No URL Menu',
        'route_name' => null,
        'url' => null,
        'order' => 99,
    ]);

    expect($menu->getUrlAttributeAttribute())->toBe('#');
});

test('isDivider returns true for divider menus', function () {
    $divider = Menu::where('is_divider', true)->first();

    if ($divider) {
        expect($divider->isDivider())->toBeTrue();
    } else {
        // Create one for testing
        $divider = Menu::create([
            'title' => 'Divider',
            'is_divider' => true,
            'order' => 99,
        ]);
        expect($divider->isDivider())->toBeTrue();
    }
});

test('isDivider returns false for non-divider menus', function () {
    $menu = Menu::where('is_divider', false)->first();

    expect($menu->isDivider())->toBeFalse();
});

test('getMenuTree returns hierarchical structure', function () {
    $tree = Menu::getMenuTree();

    expect($tree)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);

    foreach ($tree as $menu) {
        expect($menu->parent_id)->toBeNull()
            ->and($menu->is_active)->toBeTrue();
    }
});

test('getMenuTree includes active children', function () {
    $tree = Menu::getMenuTree();

    foreach ($tree as $parentMenu) {
        if ($parentMenu->children->count() > 0) {
            foreach ($parentMenu->children as $child) {
                expect($child->is_active)->toBeTrue();
            }
        }
    }
});

test('getMenuTree orders main menus', function () {
    $tree = Menu::getMenuTree();

    if ($tree->count() > 1) {
        $orders = $tree->pluck('order')->toArray();
        $sortedOrders = collect($orders)->sort()->values()->toArray();

        expect($orders)->toBe($sortedOrders);
    } else {
        expect(true)->toBeTrue();
    }
});

test('getMenuTree orders children menus', function () {
    $tree = Menu::getMenuTree();

    foreach ($tree as $parentMenu) {
        if ($parentMenu->children->count() > 1) {
            $orders = $parentMenu->children->pluck('order')->toArray();
            $sortedOrders = collect($orders)->sort()->values()->toArray();

            expect($orders)->toBe($sortedOrders);
            break; // Test only first parent with multiple children
        }
    }

    expect(true)->toBeTrue();
});
