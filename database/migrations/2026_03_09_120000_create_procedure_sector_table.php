<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procedure_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['procedure_id', 'sector_id']);
            $table->index(['sector_id', 'procedure_id']);
        });

        // Backfill dos procedimentos existentes
        DB::table('procedures')
            ->select(['id as procedure_id', 'sector_id'])
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                $now = now();
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'procedure_id' => $row->procedure_id,
                        'sector_id' => $row->sector_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($payload !== []) {
                    DB::table('procedure_sector')->insert($payload);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_sector');
    }
};

