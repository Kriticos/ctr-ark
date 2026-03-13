<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar módulos do sistema
        $modules = [
            [
                'name' => 'Gestão de Usuários',
                'slug' => 'users',
                'icon' => 'fas fa-users',
                'description' => 'Gerenciamento de usuários do sistema',
                'order' => 1,
            ],
            [
                'name' => 'Controle de Acesso',
                'slug' => 'acl',
                'icon' => 'fas fa-lock',
                'description' => 'Gerenciamento de roles, permissões e módulos',
                'order' => 2,
            ],
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'fas fa-home',
                'description' => 'Painel principal e perfil do usuário',
                'order' => 0,
            ],
            [
                'name' => 'Menus',
                'slug' => 'menus',
                'icon' => 'fas fa-bars',
                'description' => 'Gerenciamento de menus dinâmicos do sistema',
                'order' => 3,
            ],
            [
                'name' => 'Base de Conhecimento',
                'slug' => 'knowledge-base',
                'icon' => 'fas fa-book',
                'description' => 'Gerenciamento de setores e procedimentos',
                'order' => 4,
            ],
        ];

        foreach ($modules as $moduleData) {
            Module::updateOrCreate(
                ['slug' => $moduleData['slug']],
                $moduleData
            );
        }

        // Vincular permissões existentes aos módulos
        $this->linkPermissionsToModules();

        $this->command->info('✅ Módulos criados e permissões vinculadas com sucesso!');
    }

    /**
     * Vincular permissões existentes aos módulos criados.
     */
    private function linkPermissionsToModules(): void
    {
        // Módulo de Usuários
        $usersModule = Module::where('slug', 'users')->first();
        if ($usersModule) {
            Permission::where('name', 'like', 'admin.users.%')->update(['module_id' => $usersModule->id]);
        }

        // Módulo de Controle de Acesso (ACL)
        $aclModule = Module::where('slug', 'acl')->first();
        if ($aclModule) {
            Permission::where('name', 'like', 'admin.roles.%')->update(['module_id' => $aclModule->id]);
            Permission::where('name', 'like', 'admin.permissions.%')->update(['module_id' => $aclModule->id]);
            Permission::where('name', 'like', 'admin.modules.%')->update(['module_id' => $aclModule->id]);
        }

        // Módulo de Dashboard
        $dashboardModule = Module::where('slug', 'dashboard')->first();
        if ($dashboardModule) {
            Permission::where('name', 'admin.dashboard')->update(['module_id' => $dashboardModule->id]);
            Permission::where('name', 'like', 'admin.profile.%')->update(['module_id' => $dashboardModule->id]);
        }

        // Módulo de Menus
        $menusModule = Module::where('slug', 'menus')->first();
        if ($menusModule) {
            Permission::where('name', 'like', 'admin.menus.%')->update(['module_id' => $menusModule->id]);
        }

        // Módulo de Base de Conhecimento
        $kbModule = Module::where('slug', 'knowledge-base')->first();
        if ($kbModule) {
            Permission::where('name', 'like', 'admin.sectors.%')->update(['module_id' => $kbModule->id]);
            Permission::where('name', 'like', 'admin.procedures.%')->update(['module_id' => $kbModule->id]);
        }
    }
}
