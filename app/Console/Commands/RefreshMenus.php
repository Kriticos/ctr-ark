<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshMenus extends Command
{
    /** @var string */
    protected $signature = 'menus:refresh {--force : Force the operation without confirmation}';

    /** @var string */
    protected $description = 'Limpar e recriar todos os menus do sistema';

    public function handle(): int
    {
        if (
            ! $this->option('force')
            && ! $this->confirm('Isso irá apagar TODOS os menus existentes e recriar do zero. Deseja continuar?')
        ) {
            $this->info('Operação cancelada.');

            return self::SUCCESS;
        }

        $this->info('🗑️  Limpando menus existentes...');

        // Desabilitar FKs para evitar erro de truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('menus')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('🔄 Limpando cache de menus...');
        Cache::flush();

        $this->info('🎯 Executando MenuSeeder...');
        $this->call('db:seed', [
            '--class' => 'MenuSeeder',
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->newLine();
        $this->info('✅ Menus recriados com sucesso!');

        $this->printStats();

        return self::SUCCESS;
    }

    /**
     * Imprime estatísticas dos menus recriados.
     */
    private function printStats(): void
    {
        $menus = DB::table('menus')->get();

        $this->table(
            ['Tipo', 'Quantidade'],
            [
                ['Total', $menus->count()],
                ['Menus Principais', $menus->whereNull('parent_id')->where('is_divider', false)->count()],
                ['Submenus', $menus->whereNotNull('parent_id')->count()],
                ['Divisores', $menus->where('is_divider', true)->count()],
            ]
        );
    }
}
