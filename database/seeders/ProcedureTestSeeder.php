<?php

namespace Database\Seeders;

use App\Models\Procedure;
use App\Models\ProcedureAudit;
use App\Models\ProcedureVersion;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProcedureTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@larasaas.com'))->first() ?? User::first();

        if (! $admin) {
            $this->command->error('Nenhum usuário encontrado para vincular os procedimentos de teste.');

            return;
        }

        $sectors = $this->ensureBaseSectors();

        $tutorials = [
            ['title' => 'Onboarding de Novo Colaborador', 'sector' => 'rh'],
            ['title' => 'Solicitação e Aprovação de Férias', 'sector' => 'rh'],
            ['title' => 'Processo de Recrutamento e Seleção', 'sector' => 'rh'],
            ['title' => 'Fechamento Mensal Financeiro', 'sector' => 'financeiro'],
            ['title' => 'Fluxo de Contas a Pagar', 'sector' => 'financeiro'],
            ['title' => 'Fluxo de Contas a Receber', 'sector' => 'financeiro'],
            ['title' => 'Conciliação Bancária Semanal', 'sector' => 'financeiro'],
            ['title' => 'Abertura e Tratativa de Chamados Internos', 'sector' => 'operacoes'],
            ['title' => 'Checklist de Publicação de Procedimentos', 'sector' => 'operacoes'],
            ['title' => 'Plano de Resposta a Incidentes', 'sector' => 'operacoes'],
        ];

        foreach ($tutorials as $tutorial) {
            $sector = $sectors[$tutorial['sector']] ?? $sectors['operacoes'];
            $slug = Str::slug($tutorial['title']);

            /** @var Procedure $procedure */
            $procedure = Procedure::updateOrCreate(
                [
                    'sector_id' => $sector->id,
                    'slug' => $slug,
                ],
                [
                    'created_by' => $admin->id,
                    'title' => $tutorial['title'],
                    'status' => Procedure::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]
            );

            $procedure->sectors()->syncWithoutDetaching([$sector->id]);

            $version = $procedure->versions()->where('version_number', 1)->first();

            if (! $version) {
                $version = ProcedureVersion::create([
                    'procedure_id' => $procedure->id,
                    'created_by' => $admin->id,
                    'version_number' => 1,
                    'title' => $tutorial['title'],
                    'markdown_content' => $this->generateMarkdown($tutorial['title']),
                    'change_summary' => 'Versão inicial de teste',
                    'is_restore' => false,
                ]);
            }

            $procedure->update([
                'current_version_id' => $version->id,
                'status' => Procedure::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            ProcedureAudit::firstOrCreate(
                [
                    'procedure_id' => $procedure->id,
                    'version_id' => $version->id,
                    'action' => 'seed_test_tutorial',
                ],
                [
                    'user_id' => $admin->id,
                    'metadata' => ['source' => 'ProcedureTestSeeder'],
                ]
            );
        }

        $this->command->info('10 procedimentos de teste criados/atualizados com sucesso.');
    }

    /**
     * @return array<string, Sector>
     */
    private function ensureBaseSectors(): array
    {
        $rh = Sector::firstOrCreate(
            ['slug' => 'rh'],
            ['name' => 'RH', 'description' => 'Recursos Humanos', 'is_active' => true]
        );

        $financeiro = Sector::firstOrCreate(
            ['slug' => 'financeiro'],
            ['name' => 'Financeiro', 'description' => 'Setor Financeiro', 'is_active' => true]
        );

        $operacoes = Sector::firstOrCreate(
            ['slug' => 'operacoes'],
            ['name' => 'Operações', 'description' => 'Operações internas', 'is_active' => true]
        );

        return [
            'rh' => $rh,
            'financeiro' => $financeiro,
            'operacoes' => $operacoes,
        ];
    }

    private function generateMarkdown(string $title): string
    {
        return <<<MD
# {$title}

## Objetivo
Descrever o fluxo padrão deste procedimento.

## Responsáveis
- Solicitante
- Aprovador
- Executor

## Etapas
1. Abrir solicitação no sistema.
2. Validar dados obrigatórios.
3. Aprovar ou reprovar com justificativa.
4. Executar atividade e registrar evidências.
5. Encerrar procedimento.

## Critérios de Qualidade
- Todos os campos obrigatórios preenchidos.
- Aprovação registrada no sistema.
- Evidências anexadas quando aplicável.

## Referências
- Política interna vigente.
- Manual de operações do setor.
MD;
    }
}
