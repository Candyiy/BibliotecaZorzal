<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los planes por libro se contratan por días; el catálogo completo y los
     * paquetes conservan su duración mensual o anual.
     */
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            $table->unsignedInteger('duracion_dias')
                ->nullable()
                ->after('trial_dias');
        });

        DB::table('planes')
            ->where('modalidad', 'libro')
            ->update([
                'tipo' => 'dias',
                'duracion_dias' => 30,
            ]);
    }

    public function down(): void
    {
        DB::table('planes')
            ->whereNotNull('duracion_dias')
            ->update(['tipo' => 'mensual']);

        Schema::table('planes', function (Blueprint $table): void {
            $table->dropColumn('duracion_dias');
        });
    }
};
