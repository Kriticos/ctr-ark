<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserOnlineStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-online-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o status online dos usuários baseado em sua última atividade';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Atualizando status online dos usuários...');

        // Marcar usuários inativos há mais de 5 minutos como offline explicitamente
        // Isso garante que usuários com sessão expirada sejam marcados como offline
        $markedOffline = User::where('last_activity_at', '<', now()->subMinutes(5))
            ->where('last_activity_at', '>', now()->subMinutes(10))
            ->update([
                'last_activity_at' => now()->subMinutes(10),
            ]);

        // Conta usuários que estão online (últimos 5 minutos)
        $onlineCount = User::where('last_activity_at', '>=', now()->subMinutes(5))->count();

        // Conta usuários inativos
        $inactiveCount = User::where('last_activity_at', '<', now()->subMinutes(5))
            ->orWhereNull('last_activity_at')
            ->count();

        if ($markedOffline > 0) {
            $this->info("✓ {$markedOffline} usuários marcados como offline");
        }
        $this->info("Usuários online (últimos 5 minutos): {$onlineCount}");
        $this->info("Usuários inativos: {$inactiveCount}");

        $this->info('Status atualizado com sucesso!');

        return Command::SUCCESS;
    }
}
