<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminName = env('ADMIN_NAME', 'Administrador');
        $adminEmail = env('ADMIN_EMAIL', 'admin@larasaas.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        // Cria ou atualiza o usuário admin padrão vindo do .env
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );

        // Seed the application
        $this->call([
            RolePermissionSeeder::class,  // 1. Criar roles e permissões
            ModuleSeeder::class,           // 2. Criar módulos e vincular permissões
            MenuSeeder::class,             // 3. Criar menus (precisa dos módulos)
        ]);
    }
}
