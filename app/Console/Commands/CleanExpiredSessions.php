<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa sessões expiradas e marca usuários como offline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Limpando sessões expiradas...');

        // Tempo de expiração de sessão (padrão Laravel: 120 minutos)
        $sessionLifetime = config('session.lifetime', 120);
        $expirationTime = now()->subMinutes($sessionLifetime);

        // Buscar IDs de usuários com sessões ativas
        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>=', $expirationTime->timestamp)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Marcar usuários SEM sessão ativa como offline (se ainda aparecem como online)
        $offlineCount = User::whereNotIn('id', $activeSessions)
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->update([
                'last_activity_at' => now()->subMinutes(10),
            ]);

        // Limpar sessões antigas do banco (opcional, mas recomendado)
        $deletedSessions = DB::table('sessions')
            ->where('last_activity', '<', $expirationTime->timestamp)
            ->delete();

        $this->info("✓ {$offlineCount} usuários marcados como offline");
        $this->info("✓ {$deletedSessions} sessões expiradas removidas");
        $this->info('Limpeza concluída com sucesso!');

        return Command::SUCCESS;
    }
}
