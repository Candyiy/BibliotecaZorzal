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
        if (! Schema::hasColumn('planes', 'estado')) {
            Schema::table('planes', fn (Blueprint $table) => $table->string('estado')->default('activa'));

            return;
        }

        DB::table('planes')
            ->whereNull('estado')
            ->orWhere('estado', '')
            ->update(['estado' => 'activa']);

        Schema::table('planes', function (Blueprint $table) {
            $table->string('estado')->default('activa')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // El valor por defecto se conserva para no dejar planes sin estado válido.
    }
};
