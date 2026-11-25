<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cantidad de lecturas (préstamos) permitidas por licencia.
     * Null significa que no hay límite.
     */
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table): void {
            $table->unsignedInteger('cantidad_lecturas')
                ->nullable()
                ->after('licencia_editorial_id');
        });
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table): void {
            $table->dropColumn('cantidad_lecturas');
        });
    }
};
