<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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

test('menu index requires authentication', function () {
    get(route('admin.menus.index'))
        ->assertRedirect(route('login'));
});

test('menu index displays list with search and module filter', function () {
    actingAsAdmin();

    $module = Module::factory()->create([
        'name' => 'Reports',
        'slug' => 'reports-'.uniqid(),
    ]);

    $menuIn = Menu::factory()->create([
        'module_id' => $module->id,
        'title' => 'Reports Menu',
        'route_name' => 'reports.index',
    ]);

    Menu::factory()->create(['title' => 'Other Menu']);

    $response = get(route('admin.menus.index', [
        'search' => 'Reports',
        'module' => $module->id,
    ]));

    $response
        ->assertOk()
        ->assertViewIs('admin.menus.index')
        ->assertViewHas('menus');

    $menus = $response->viewData('menus');

    expect($menus->pluck('id'))->toContain($menuIn->id);
});

// ========================================
// CREATE
// ========================================

test('menu create requires authentication', function () {
    get(route('admin.menus.create'))
        ->assertRedirect(route('login'));
});

test('menu create shows form with modules, parents and permissions', function () {
    actingAsAdmin();

    $module = Module::factory()->create();
    $parent = Menu::factory()->create();
    $permission = Permission::factory()->create(['module_id' => $module->id]);

    $response = get(route('admin.menus.create'));

    $response
        ->assertOk()
        ->assertViewIs('admin.menus.create')
        ->assertViewHasAll(['modules', 'parentMenus', 'permissions']);

    expect($response->viewData('modules')->pluck('id'))->toContain($module->id);
    expect($response->viewData('parentMenus')->pluck('id'))->toContain($parent->id);
    expect($response->viewData('permissions')->pluck('id'))->toContain($permission->id);
});

// ========================================
// STORE
// ========================================

test('menu store requires authentication', function () {
    post(route('admin.menus.store'), [])
        ->assertRedirect(route('login'));
});

test('menu store sets default order when not provided', function () {
    actingAsAdmin();

    $existing = Menu::factory()->create(['order' => 2]);

    post(route('admin.menus.store'), [
        'title' => 'New Menu',
        'route_name' => 'new.menu',
    ])
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('title', 'New Menu')->firstOrFail();

    expect($menu->order)->toBe($existing->order + 1);
});

test('menu store uses provided order and flushes cache', function () {
    actingAsAdmin();
    Cache::put('menus_test', 'value');

    post(route('admin.menus.store'), [
        'title' => 'Ordered Menu',
        'route_name' => 'ordered.menu',
        'order' => 10,
        'is_active' => true,
        'is_divider' => false,
        'target' => '_self',
    ])
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('menus', [
        'title' => 'Ordered Menu',
        'order' => 10,
    ]);

    expect(Cache::has('menus_test'))->toBeFalse();
});

// ========================================
// SHOW
// ========================================
test('menu show requires authentication', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $menu = Menu::factory()->create();

    $this->get(route('admin.menus.show', $menu))
        ->assertRedirect(route('login'));
});

test('menu show displays stats including active submenus', function () {
    actingAsAdmin();

    $menu = Menu::factory()->create(['route_name' => null]);
    Menu::factory()->create(['parent_id' => $menu->id, 'is_active' => true]);
    Menu::factory()->create(['parent_id' => $menu->id, 'is_active' => false]);

    $response = get(route('admin.menus.show', $menu));

    $response
        ->assertOk()
        ->assertViewIs('admin.menus.show')
        ->assertViewHasAll(['menu', 'stats']);

    $stats = $response->viewData('stats');

    expect($stats['submenus_count'])->toBe(2);
    expect($stats['active_submenus'])->toBe(1);
});

// ========================================
// EDIT
// ========================================

test('menu edit requires authentication', function () {
    $menu = Menu::factory()->create();
    get(route('admin.menus.edit', $menu))
        ->assertRedirect(route('login'));
});

test('menu edit shows form data excluding self as parent option', function () {
    actingAsAdmin();

    $menu = Menu::factory()->create(['route_name' => null]);
    $parent = Menu::factory()->create();
    $module = Module::factory()->create();

    $response = get(route('admin.menus.edit', $menu));

    $response
        ->assertOk()
        ->assertViewIs('admin.menus.edit')
        ->assertViewHasAll(['menu', 'modules', 'parentMenus', 'permissions']);

    expect($response->viewData('parentMenus')->pluck('id'))
        ->toContain($parent->id)
        ->not()->toContain($menu->id);
});

// ========================================
// UPDATE
// ========================================

test('menu update requires authentication', function () {
    $menu = Menu::factory()->create(['route_name' => null]);

    patch(route('admin.menus.update', $menu), [])
        ->assertRedirect(route('login'));
});

test('menu update prevents setting self as parent', function () {
    actingAsAdmin();

    $menu = Menu::factory()->create();

    patch(route('admin.menus.update', $menu), [
        'title' => $menu->title,
        'parent_id' => $menu->id,
    ])->assertSessionHas('error');
});

test('menu update modifies data and flushes cache', function () {
    actingAsAdmin();
    Cache::put('menus_test', 'value');

    $menu = Menu::factory()->create(['title' => 'Old', 'route_name' => 'old.route']);

    patch(route('admin.menus.update', $menu), [
        'title' => 'New',
        'route_name' => 'new.route',
        'order' => 5,
    ])
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('menus', [
        'id' => $menu->id,
        'title' => 'New',
        'route_name' => 'new.route',
        'order' => 5,
    ]);

    expect(Cache::has('menus_test'))->toBeFalse();
});

// ========================================
// DESTROY
// ========================================

test('menu destroy blocks deletion when menu has children', function () {
    actingAsAdmin();

    $menu = Menu::factory()->create();
    Menu::factory()->create(['parent_id' => $menu->id]);

    delete(route('admin.menus.destroy', $menu))
        ->assertSessionHas('error');

    assertDatabaseHas('menus', ['id' => $menu->id]);
});

test('menu destroy deletes menu without children and flushes cache', function () {
    actingAsAdmin();
    Cache::put('menus_test', 'value');

    $menu = Menu::factory()->create();

    delete(route('admin.menus.destroy', $menu))
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('success');

    assertDatabaseMissing('menus', ['id' => $menu->id]);

    expect(Cache::has('menus_test'))->toBeFalse();
});

// ========================================
// UPDATE ORDER
// ========================================

test('menu update order updates positions and flushes cache', function () {
    actingAsAdmin();
    Cache::put('menus_test', 'value');

    $menu1 = Menu::factory()->create(['order' => 1]);
    $menu2 = Menu::factory()->create(['order' => 2]);

    post(route('admin.menus.update-order'), [
        'items' => [
            ['id' => $menu1->id, 'order' => 5],
            ['id' => $menu2->id, 'order' => 6],
        ],
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    assertDatabaseHas('menus', ['id' => $menu1->id, 'order' => 5]);
    assertDatabaseHas('menus', ['id' => $menu2->id, 'order' => 6]);

    expect(Cache::has('menus_test'))->toBeFalse();
});
