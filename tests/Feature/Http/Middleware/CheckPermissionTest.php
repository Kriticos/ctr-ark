<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;

uses(RefreshDatabase::class);

use function Pest\Laravel\get;

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

function registerTestRoute(string $name)
{
    RouteFacade::middleware([CheckPermission::class])->group(function () use ($name) {
        RouteFacade::get('/test-permission', fn () => 'ok')->name($name);
    });
}

// Permite acesso quando usuário tem permissão
test('check permission allows access with permission', function () {
    registerTestRoute('admin.test.index');

    $user = login(createUser());

    $role = Role::factory()->create();
    $perm = Permission::factory()->create(['name' => 'admin.test.index']);

    $role->permissions()->attach($perm);
    $user->roles()->attach($role);

    get('/test-permission')->assertOk();
});

// Nega acesso quando usuário não tem permissão
test('check permission denies access without permission', function () {
    registerTestRoute('admin.test.index');

    login(createUser());

    get('/test-permission')->assertForbidden();
});

// Redireciona quando não autenticado
test('check permission redirects unauthenticated to login', function () {
    registerTestRoute('admin.test.index');

    get('/test-permission')->assertRedirect(route('login'));
});

// Bypass quando sem nome de rota
test('check permission allows access when route has no name', function () {
    RouteFacade::middleware([CheckPermission::class])->group(function () {
        RouteFacade::get('/unnamed', fn () => 'ok');
    });

    login(createUser());

    get('/unnamed')->assertOk();
});

// Bypass para admin
test('check permission allows access for admin user', function () {
    registerTestRoute('admin.test.index');

    login(createUserWithRoles('admin'));

    get('/test-permission')->assertOk();
});
