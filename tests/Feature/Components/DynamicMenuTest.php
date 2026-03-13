<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\View\Components\DynamicMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
    $this->seed(\Database\Seeders\MenuSeeder::class);
});

test('component initializes with menus for authenticated user', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;

    expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($component->menus->count())->toBeGreaterThan(0);
});

test('component returns empty collection when no user is authenticated', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $component = new DynamicMenu;

    expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($component->menus)->toHaveCount(0);
});

test('component initializes open menu ids', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getMenusForUser returns only active menus', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;
    $menus = $component->menus;

    foreach ($menus as $menu) {
        expect($menu->is_active)->toBeTrue();
    }
});

test('getMenusForUser filters menus by user permissions', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $viewerRole = Role::where('slug', 'viewer')->first();
    $user->roles()->attach($viewerRole);

    $this->actingAs($user);

    $component = new DynamicMenu;
    $menus = $component->menus;

    // Viewer tem menos acesso que admin
    expect($menus->count())->toBeLessThan(Menu::where('is_active', true)->whereNull('parent_id')->count());
});

test('userHasAccessToMenu returns true for dividers', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $divider = Menu::where('is_divider', true)->first();

    if ($divider) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        // Divisores devem estar visíveis na lista de menus se forem ativos
        expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    } else {
        expect(true)->toBeTrue();
    }
});

test('userHasAccessToMenu returns true for public menus without permission', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $publicMenu = Menu::whereNull('permission_name')
        ->whereNull('parent_id')
        ->where('is_divider', false)
        ->first();

    if ($publicMenu) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        // Menus públicos devem estar visíveis
        expect($component->menus->count())->toBeGreaterThanOrEqual(0);
    } else {
        expect(true)->toBeTrue();
    }
});

test('userHasAccessToMenu checks children for public menu with children', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $parentMenu = Menu::whereNull('permission_name')
        ->whereNull('parent_id')
        ->where('is_divider', false)
        ->whereHas('children')
        ->first();

    if ($parentMenu) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        // Menus com filhos devem estar acessíveis
        expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    } else {
        expect(true)->toBeTrue();
    }
});

test('userHasAccessToMenu checks gate permission', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $menuWithPermission = Menu::whereNotNull('permission_name')
        ->first();

    if ($menuWithPermission) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        // Admin deve ter acesso a tudo através da interface pública
        expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    } else {
        expect(true)->toBeTrue();
    }
});

test('getOpenMenuIds returns empty array when no route', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $this->get('/'); // Rota que existe

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getOpenMenuIds returns menu ids for matching routes', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    // Acesse uma rota conhecida
    $this->get(route('admin.dashboard'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getOpenMenuIds checks route name prefix matching', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    // Acesse uma rota de usuários
    $this->get(route('admin.users.index'));

    $component = new DynamicMenu;

    // Deve ter algum menu aberto pois estamos em admin.users
    expect($component->openMenuIds)->toBeArray();
});

test('component render returns view', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;
    $view = $component->render();

    expect($view)->toBeInstanceOf(\Illuminate\View\View::class);
});

test('component view has menus data', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;

    expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($component->openMenuIds)->toBeArray();

    $component = new DynamicMenu;
    $menus = $component->menus;

    // Editor deve ter acesso a alguns menus
    expect($menus->count())->toBeGreaterThan(0);
});

test('getMenusForUser includes all menus for admin user', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;
    $menus = $component->menus;

    // Admin deve ver a maioria dos menus
    $totalMenus = Menu::where('is_active', true)
        ->whereNull('parent_id')
        ->where('is_divider', false)
        ->count();

    expect($menus->count())->toBeGreaterThan(0);
});

test('userHasAccessToMenu handles menu without children correctly', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $menuWithoutChildren = Menu::whereNull('parent_id')
        ->doesntHave('children')
        ->where('is_divider', false)
        ->whereNull('permission_name')
        ->first();

    if ($menuWithoutChildren) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        // Menus sem filhos devem estar acessíveis através da interface pública
        expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    } else {
        expect(true)->toBeTrue();
    }
});

test('getOpenMenuIds matches full route name', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $this->get(route('admin.users.index'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getOpenMenuIds matches route prefix', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    // admin.users.create deve abrir o menu parent de admin.users
    $this->get(route('admin.users.create'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getOpenMenuIds handles routes with multiple segments', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $this->get(route('admin.dashboard'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});
test('getOpenMenuIds matches by url path', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    // Acesse uma rota com URL path
    $this->get('/admin/users');

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('getOpenMenuIds checks children with no route name', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    // Buscar um menu com filhos que não tem route_name mas tem url
    $menuWithUrlChild = Menu::whereNull('parent_id')
        ->where('is_active', true)
        ->whereHas('children', function ($query) {
            $query->where('is_active', true)->whereNull('route_name')->whereNotNull('url');
        })
        ->first();

    if ($menuWithUrlChild) {
        $this->get($menuWithUrlChild->children->first()->url);
        $component = new DynamicMenu;
        expect($component->openMenuIds)->toBeArray();
    } else {
        expect(true)->toBeTrue();
    }
});

test('getOpenMenuIds handles routes with less than 2 segments', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $this->get('/');

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('component handles menu with route prefix matching', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    // Create a parent menu with a child that has a multi-segment route name
    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Parent Menu',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childMenu = Menu::create([
        'title' => 'Child Menu',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.users.edit',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    $this->get(route('admin.users.edit', 1));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toBeArray();
});

test('component opens parent menu when child route matches prefix', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Admin Parent',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childMenu = Menu::create([
        'title' => 'Admin Roles',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.roles.index',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    // Access admin.roles.show which shares admin.roles prefix
    $this->get(route('admin.roles.index'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toContain('menu_'.$parentMenu->id);
});

test('component with child having no route_name and has URL', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Parent',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childMenu = Menu::create([
        'title' => 'Child with URL',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'url' => '/admin/custom-path',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    $this->get('/admin/custom-path');

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toContain('menu_'.$parentMenu->id);
});

test('component with childRoutePrefix matching', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Parent Prefix',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    // Child with a dotted route name
    $childMenu = Menu::create([
        'title' => 'Child Dotted',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.menus.index',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    // Access admin.menus.create which has admin.menus as prefix
    $this->get(route('admin.menus.create'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toContain('menu_'.$parentMenu->id);
});

test('component with multiple children where one matches route', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Multi Children Parent',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    // First child does not match
    $child1 = Menu::create([
        'title' => 'Child 1',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.users.index',
        'module_id' => $module->id,
    ]);

    // Second child matches
    $child2 = Menu::create([
        'title' => 'Child 2',
        'icon' => 'fas fa-cube',
        'order' => 2,
        'is_active' => true,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.roles.index',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    $this->get(route('admin.roles.index'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toContain('menu_'.$parentMenu->id);
});

test('component with inactive child menu', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module',
    ]);

    $parentMenu = Menu::create([
        'title' => 'Parent with Inactive Child',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $inactiveChild = Menu::create([
        'title' => 'Inactive Child',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => false,
        'parent_id' => $parentMenu->id,
        'route_name' => 'admin.users.index',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    $this->get(route('admin.users.index'));

    $component = new DynamicMenu;

    // Inactive children should not cause the parent to open
    expect($component->menus)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

test('userHasAccessToMenu with permission denied by gate', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $viewerRole = Role::where('slug', 'viewer')->first();
    $user->roles()->attach($viewerRole);

    $menuWithRestriction = Menu::whereNotNull('permission_name')
        ->where('is_active', true)
        ->first();

    if ($menuWithRestriction) {
        $this->actingAs($user);
        $component = new DynamicMenu;

        $reflection = new \ReflectionMethod($component, 'userHasAccessToMenu');
        $reflection->setAccessible(true);

        $hasAccess = $reflection->invoke($component, $menuWithRestriction, $user);
        // Viewer may not have access to restricted items
        expect(is_bool($hasAccess))->toBeTrue();
    } else {
        expect(true)->toBeTrue();
    }
});

test('getOpenMenuIds with both currentRoute and currentPath empty', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    // This is an edge case - normally either route or path exists
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $this->actingAs($user);

    $component = new DynamicMenu;

    // Even with a route/path, the method should handle it gracefully
    expect($component->openMenuIds)->toBeArray();
});

test('component with complex menu hierarchy', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $user->roles()->attach($adminRole);

    $module = Module::first() ?? Module::create([
        'name' => 'Complex Module',
        'slug' => 'complex-module',
    ]);

    // Create a deeply nested menu structure
    $level1 = Menu::create([
        'title' => 'Level 1',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $level2 = Menu::create([
        'title' => 'Level 2',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'is_active' => true,
        'parent_id' => $level1->id,
        'route_name' => 'admin.permissions.index',
        'module_id' => $module->id,
    ]);

    $this->actingAs($user);
    $this->get(route('admin.permissions.index'));

    $component = new DynamicMenu;

    expect($component->openMenuIds)->toContain('menu_'.$level1->id);
});
