<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table): void {
            $table->string('estado')->default('activo')->after('cantidad');
            $table->date('f_devolucion')->nullable()->after('f_finalizacion');
            $table->unsignedInteger('dias_penalizacion')->default(0)->after('f_devolucion');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table): void {
            $table->dropColumn(['estado', 'f_devolucion', 'dias_penalizacion']);
        });
    }
};
