<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar módulos existentes
        $dashboardModule = Module::where('slug', 'dashboard')->first();
        $usersModule = Module::where('slug', 'users')->first();
        $aclModule = Module::where('slug', 'acl')->first();
        $menusModule = Module::where('slug', 'menus')->first();
        $knowledgeBaseModule = Module::where('slug', 'knowledge-base')->first();

        // 1. Dashboard (Menu Principal)
        $dashboardMenu = Menu::updateOrCreate(
            ['route_name' => 'admin.dashboard'],
            [
                'module_id' => $dashboardModule?->id,
                'parent_id' => null,
                'title' => 'Dashboard',
                'icon' => 'fas fa-home',
                'url' => null,
                'permission_name' => 'admin.dashboard',
                'order' => 0,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Página principal do painel administrativo',
            ]
        );

        // 2. Usuários (Menu Principal com Submenus)
        $usersMenu = Menu::updateOrCreate(
            ['title' => 'Usuários', 'parent_id' => null],
            [
                'module_id' => $usersModule?->id,
                'icon' => 'fas fa-users',
                'route_name' => null,
                'url' => null,
                'permission_name' => 'admin.users.index',
                'order' => 1,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciamento de usuários do sistema',
            ]
        );

        // 2.1. Listar Usuários (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.users.index'],
            [
                'module_id' => $usersModule?->id,
                'parent_id' => $usersMenu->id,
                'title' => 'Lista de Usuários',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.users.index',
                'order' => 0,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Visualizar todos os usuários cadastrados',
            ]
        );

        // 2.2. Adicionar Usuário (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.users.create'],
            [
                'module_id' => $usersModule?->id,
                'parent_id' => $usersMenu->id,
                'title' => 'Adicionar Usuário',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.users.create',
                'order' => 1,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Cadastrar novo usuário no sistema',
            ]
        );

        // 3. Procedimentos (Menu Principal com Submenus)
        $proceduresMenu = Menu::updateOrCreate(
            ['title' => 'Procedimentos', 'parent_id' => null],
            [
                'module_id' => $knowledgeBaseModule?->id,
                'icon' => 'fas fa-book-open',
                'route_name' => null,
                'url' => null,
                'permission_name' => null,
                'order' => 2,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Base de conhecimento por setor',
            ]
        );

        // 3.1. Lista de Procedimentos
        Menu::updateOrCreate(
            ['route_name' => 'admin.procedures.index'],
            [
                'module_id' => $knowledgeBaseModule?->id,
                'parent_id' => $proceduresMenu->id,
                'title' => 'Lista de Procedimentos',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.procedures.index',
                'order' => 0,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Visualizar procedimentos por setor',
            ]
        );

        // 3.2. Novo Procedimento
        Menu::updateOrCreate(
            ['route_name' => 'admin.procedures.create'],
            [
                'module_id' => $knowledgeBaseModule?->id,
                'parent_id' => $proceduresMenu->id,
                'title' => 'Novo Procedimento',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.procedures.create',
                'order' => 1,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Cadastrar novo procedimento',
            ]
        );

        // 3.3. Setores
        Menu::updateOrCreate(
            ['route_name' => 'admin.sectors.index'],
            [
                'module_id' => $knowledgeBaseModule?->id,
                'parent_id' => $proceduresMenu->id,
                'title' => 'Setores',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.sectors.index',
                'order' => 2,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciar setores e subsetores',
            ]
        );

        // 4. Controle de Acesso (Menu Principal com Submenus)
        $aclMenu = Menu::updateOrCreate(
            ['title' => 'Controle de Acesso', 'parent_id' => null],
            [
                'module_id' => $aclModule?->id,
                'icon' => 'fas fa-lock',
                'route_name' => null,
                'url' => null,
                'permission_name' => null,
                'order' => 3,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciamento de permissões e roles',
            ]
        );

        // 3.1. Módulos (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.modules.index'],
            [
                'module_id' => $aclModule?->id,
                'parent_id' => $aclMenu->id,
                'title' => 'Módulos',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.modules.index',
                'order' => 0,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciar módulos do sistema',
            ]
        );

        // 3.2. Roles (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.roles.index'],
            [
                'module_id' => $aclModule?->id,
                'parent_id' => $aclMenu->id,
                'title' => 'Roles',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.roles.index',
                'order' => 1,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciar papéis de usuário',
            ]
        );

        // 3.3. Permissões (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.permissions.index'],
            [
                'module_id' => $aclModule?->id,
                'parent_id' => $aclMenu->id,
                'title' => 'Permissões',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.permissions.index',
                'order' => 2,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciar permissões do sistema',
            ]
        );

        // 3.4. Menus (Submenu)
        Menu::updateOrCreate(
            ['route_name' => 'admin.menus.index'],
            [
                'module_id' => $menusModule?->id,
                'parent_id' => $aclMenu->id,
                'title' => 'Menus',
                'icon' => null,
                'url' => null,
                'permission_name' => 'admin.menus.index',
                'order' => 3,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Gerenciar menus do sistema',
            ]
        );

        // 5. Divisor (Separador Visual)
        Menu::updateOrCreate(
            ['title' => 'Divisor', 'parent_id' => null, 'is_divider' => true],
            [
                'module_id' => null,
                'icon' => null,
                'route_name' => null,
                'url' => null,
                'permission_name' => null,
                'order' => 4,
                'is_active' => true,
                'target' => '_self',
                'description' => 'Linha separadora no menu',
            ]
        );

        // 6. Perfil (Menu Principal)
        Menu::updateOrCreate(
            ['route_name' => 'admin.profile.edit'],
            [
                'module_id' => null,
                'parent_id' => null,
                'title' => 'Perfil',
                'icon' => 'fas fa-user',
                'url' => null,
                'permission_name' => 'admin.profile.edit',
                'order' => 5,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Editar perfil do usuário',
            ]
        );

        // 7. Configurações (Menu Principal)
        Menu::updateOrCreate(
            ['title' => 'Configurações', 'parent_id' => null, 'url' => '#'],
            [
                'module_id' => null,
                'icon' => 'fas fa-cog',
                'route_name' => null,
                'permission_name' => 'admin.menus.index',
                'order' => 6,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_self',
                'description' => 'Configurações gerais do sistema',
            ]
        );

        // 8. Laravel Pulse (Menu Principal - Apenas Admin)
        Menu::updateOrCreate(
            ['title' => 'Pulse', 'url' => '/pulse'],
            [
                'module_id' => null,
                'parent_id' => null,
                'icon' => 'fas fa-bolt',
                'route_name' => null,
                'permission_name' => 'admin.roles.index',
                'order' => 7,
                'is_active' => true,
                'is_divider' => false,
                'target' => '_blank',
                'badge' => 'Admin',
                'badge_color' => 'bg-purple-600 text-white dark:bg-purple-500 dark:text-white',
                'description' => 'Monitoramento de performance da aplicação',
            ]
        );

        $this->command->info('✅ Menus criados com sucesso!');
        $this->command->info('📊 Total: '.Menu::count().' menus');
        $this->command->info('🔹 Principais: '.Menu::whereNull('parent_id')->where('is_divider', false)->count());
        $this->command->info('🔸 Submenus: '.Menu::whereNotNull('parent_id')->count());
        $this->command->info('➖ Divisores: '.Menu::where('is_divider', true)->count());
    }
}
