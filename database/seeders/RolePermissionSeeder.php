<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar Roles
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Acesso total ao sistema',
            ]
        );

        $manager = Role::updateOrCreate(
            ['slug' => 'sector-manager'],
            [
                'name' => 'Gestor de Setor',
                'description' => 'Gerencia procedimentos e aprova/publica por setor',
            ]
        );

        $editor = Role::updateOrCreate(
            ['slug' => 'sector-editor'],
            [
                'name' => 'Editor de Setor',
                'description' => 'Cria e edita procedimentos por setor, sem excluir',
            ]
        );

        $reader = Role::updateOrCreate(
            ['slug' => 'sector-reader'],
            [
                'name' => 'Leitor de Setor',
                'description' => 'Apenas leitura de procedimentos por setor',
            ]
        );

        // Criar Permissões
        $permissions = [
            // Usuários
            ['name' => 'admin.users.index', 'description' => 'Listar usuários'],
            ['name' => 'admin.users.create', 'description' => 'Criar usuários'],
            ['name' => 'admin.users.store', 'description' => 'Salvar novo usuário'],
            ['name' => 'admin.users.show', 'description' => 'Visualizar usuário'],
            ['name' => 'admin.users.edit', 'description' => 'Editar usuário'],
            ['name' => 'admin.users.update', 'description' => 'Atualizar usuário'],
            ['name' => 'admin.users.destroy', 'description' => 'Excluir usuário'],

            // Roles
            ['name' => 'admin.roles.index', 'description' => 'Listar roles'],
            ['name' => 'admin.roles.create', 'description' => 'Criar roles'],
            ['name' => 'admin.roles.store', 'description' => 'Salvar nova role'],
            ['name' => 'admin.roles.show', 'description' => 'Visualizar role'],
            ['name' => 'admin.roles.edit', 'description' => 'Editar role'],
            ['name' => 'admin.roles.update', 'description' => 'Atualizar role'],
            ['name' => 'admin.roles.destroy', 'description' => 'Excluir role'],

            // Permissions
            ['name' => 'admin.permissions.index', 'description' => 'Listar permissões'],
            ['name' => 'admin.permissions.create', 'description' => 'Criar permissões'],
            ['name' => 'admin.permissions.store', 'description' => 'Salvar nova permissão'],
            ['name' => 'admin.permissions.show', 'description' => 'Visualizar permissão'],
            ['name' => 'admin.permissions.edit', 'description' => 'Editar permissão'],
            ['name' => 'admin.permissions.update', 'description' => 'Atualizar permissão'],
            ['name' => 'admin.permissions.destroy', 'description' => 'Excluir permissão'],

            // Modules
            ['name' => 'admin.modules.index', 'description' => 'Listar módulos'],
            ['name' => 'admin.modules.create', 'description' => 'Criar módulos'],
            ['name' => 'admin.modules.store', 'description' => 'Salvar novo módulo'],
            ['name' => 'admin.modules.show', 'description' => 'Visualizar módulo'],
            ['name' => 'admin.modules.edit', 'description' => 'Editar módulo'],
            ['name' => 'admin.modules.update', 'description' => 'Atualizar módulo'],
            ['name' => 'admin.modules.destroy', 'description' => 'Excluir módulo'],

            // Menus
            ['name' => 'admin.menus.index', 'description' => 'Listar menus'],
            ['name' => 'admin.menus.create', 'description' => 'Criar menus'],
            ['name' => 'admin.menus.store', 'description' => 'Salvar novo menu'],
            ['name' => 'admin.menus.show', 'description' => 'Visualizar menu'],
            ['name' => 'admin.menus.edit', 'description' => 'Editar menu'],
            ['name' => 'admin.menus.update', 'description' => 'Atualizar menu'],
            ['name' => 'admin.menus.destroy', 'description' => 'Excluir menu'],
            ['name' => 'admin.menus.reorder', 'description' => 'Reordenar menus'],

            // Dashboard e Perfil
            ['name' => 'admin.dashboard', 'description' => 'Acessar dashboard'],
            ['name' => 'admin.profile.edit', 'description' => 'Editar perfil'],
            ['name' => 'admin.profile.update', 'description' => 'Atualizar perfil'],
            ['name' => 'admin.profile.delete-avatar', 'description' => 'Remover avatar do perfil'],
            ['name' => 'admin.profile.update-theme', 'description' => 'Atualizar tema do perfil'],

            // Setores
            ['name' => 'admin.sectors.index', 'description' => 'Listar setores'],
            ['name' => 'admin.sectors.create', 'description' => 'Criar setor'],
            ['name' => 'admin.sectors.store', 'description' => 'Salvar setor'],
            ['name' => 'admin.sectors.show', 'description' => 'Visualizar setor'],
            ['name' => 'admin.sectors.edit', 'description' => 'Editar setor'],
            ['name' => 'admin.sectors.update', 'description' => 'Atualizar setor'],
            ['name' => 'admin.sectors.destroy', 'description' => 'Excluir setor'],

            // Procedimentos
            ['name' => 'admin.procedures.index', 'description' => 'Listar procedimentos'],
            ['name' => 'admin.procedures.create', 'description' => 'Criar procedimento'],
            ['name' => 'admin.procedures.store', 'description' => 'Salvar procedimento'],
            ['name' => 'admin.procedures.show', 'description' => 'Visualizar procedimento'],
            ['name' => 'admin.procedures.edit', 'description' => 'Editar procedimento'],
            ['name' => 'admin.procedures.update', 'description' => 'Atualizar procedimento'],
            ['name' => 'admin.procedures.destroy', 'description' => 'Excluir procedimento'],
            ['name' => 'admin.procedures.submit-review', 'description' => 'Enviar para revisão'],
            ['name' => 'admin.procedures.approve', 'description' => 'Aprovar procedimento'],
            ['name' => 'admin.procedures.reject', 'description' => 'Reprovar procedimento'],
            ['name' => 'admin.procedures.publish', 'description' => 'Publicar procedimento'],
            ['name' => 'admin.procedures.compare', 'description' => 'Comparar versões'],
            ['name' => 'admin.procedures.versions.restore', 'description' => 'Restaurar versões'],
            ['name' => 'admin.procedures.images.upload', 'description' => 'Upload de imagens do markdown'],
            ['name' => 'admin.procedures.images.show', 'description' => 'Visualizar imagens protegidas'],
            ['name' => 'admin.procedures.preview', 'description' => 'Preview em tempo real do markdown'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                ['description' => $permissionData['description']]
            );
        }

        // Atribuir permissões ao Gestor de Setor
        $managerPermissions = Permission::whereIn('name', [
            'admin.dashboard',
            'admin.profile.edit',
            'admin.profile.update',
            'admin.profile.delete-avatar',
            'admin.profile.update-theme',
            'admin.sectors.index',
            'admin.sectors.show',
            'admin.procedures.index',
            'admin.procedures.show',
            'admin.procedures.create',
            'admin.procedures.store',
            'admin.procedures.edit',
            'admin.procedures.update',
            'admin.procedures.destroy',
            'admin.procedures.submit-review',
            'admin.procedures.approve',
            'admin.procedures.reject',
            'admin.procedures.publish',
            'admin.procedures.compare',
            'admin.procedures.versions.restore',
            'admin.procedures.images.upload',
            'admin.procedures.images.show',
            'admin.procedures.preview',
        ])->get();
        $manager->permissions()->sync($managerPermissions);

        // Atribuir permissões ao Editor de Setor (sem excluir/aprovar/publicar)
        $editorPermissions = Permission::whereIn('name', [
            'admin.dashboard',
            'admin.profile.edit',
            'admin.profile.update',
            'admin.profile.delete-avatar',
            'admin.profile.update-theme',
            'admin.procedures.index',
            'admin.procedures.show',
            'admin.procedures.create',
            'admin.procedures.store',
            'admin.procedures.edit',
            'admin.procedures.update',
            'admin.procedures.submit-review',
            'admin.procedures.compare',
            'admin.procedures.images.upload',
            'admin.procedures.images.show',
            'admin.procedures.preview',
        ])->get();
        $editor->permissions()->sync($editorPermissions);

        // Atribuir permissões ao Leitor de Setor
        $readerPermissions = Permission::whereIn('name', [
            'admin.procedures.index',
            'admin.procedures.show',
            'admin.procedures.compare',
            'admin.procedures.images.show',
        ])->get();
        $reader->permissions()->sync($readerPermissions);

        // Atribuir role Admin ao usuário configurado no .env (fallback: primeiro usuário)
        $adminEmail = env('ADMIN_EMAIL', 'admin@larasaas.com');
        $firstUser = User::where('email', $adminEmail)->first() ?? User::first();
        if ($firstUser) {
            // Usar syncWithoutDetaching para não remover roles existentes
            $firstUser->roles()->syncWithoutDetaching([$admin->id]);
            $this->command->info('Role Admin atribuída ao usuário: '.$firstUser->email);
        }

        $this->command->info('✅ Roles e permissões criadas/atualizadas com sucesso!');
    }
}
