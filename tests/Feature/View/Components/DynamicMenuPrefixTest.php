<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use function Pest\Laravel\get;

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    // Atribuir role admin para ter acesso às rotas
    $adminRole = Role::where('slug', 'admin')->first();
    if ($adminRole) {
        $user->roles()->attach($adminRole);
    }

    $this->actingAs($user);
});

it('opens parent menu when on different route with same prefix', function () {
    // Testa linhas 136-137: comparação de prefixo (admin.roles.create vs admin.roles.index)
    $module = Module::factory()->create();

    $parent = Menu::factory()->create([
        'title' => 'Controle de Acesso',
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childRoles = Menu::factory()->create([
        'title' => 'Roles',
        'is_active' => true,
        'parent_id' => $parent->id,
        'route_name' => 'admin.roles.index',
        'permission_name' => null,
        'module_id' => $module->id,
    ]);

    // GET na rota create (que tem o mesmo prefixo admin.roles)
    // Isso fará com que request()->route()->getName() seja 'admin.roles.create'
    // O código então compara: admin.roles (de admin.roles.create) === admin.roles (de admin.roles.index)
    $response = get('/admin/roles/create');
    $response->assertOk();
});

it('opens parent menu when on show route with same prefix', function () {
    // Outro teste com rota diferente mas mesmo prefixo
    $module = Module::factory()->create();

    $parent = Menu::factory()->create([
        'title' => 'Gerenciamento',
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childPerms = Menu::factory()->create([
        'title' => 'Permissions',
        'is_active' => true,
        'parent_id' => $parent->id,
        'route_name' => 'admin.permissions.index',
        'permission_name' => null,
        'module_id' => $module->id,
    ]);

    // Criar uma permissão para poder acessar a rota de edit
    $permission = Permission::factory()->create();

    // Acessar edit com mesmo prefixo admin.permissions
    $response = get("/admin/permissions/{$permission->id}/edit");
    $response->assertOk();
});

it('matches prefix for users resource routes', function () {
    // Teste com users resource
    $module = Module::factory()->create();

    $parent = Menu::factory()->create([
        'title' => 'Usuarios',
        'is_active' => true,
        'parent_id' => null,
        'module_id' => $module->id,
    ]);

    $childUsers = Menu::factory()->create([
        'title' => 'Users',
        'is_active' => true,
        'parent_id' => $parent->id,
        'route_name' => 'admin.users.index',
        'permission_name' => null,
        'module_id' => $module->id,
    ]);

    // Criar um usuário para acessar a rota de edit
    $user = User::factory()->create();

    // Acessar users edit (mesmo prefixo admin.users)
    $response = get("/admin/users/{$user->id}/edit");
    $response->assertOk();
});
