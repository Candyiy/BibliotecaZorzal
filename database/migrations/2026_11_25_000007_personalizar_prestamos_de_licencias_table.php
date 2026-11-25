<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada licencia con contador define cuántos días se presta y cuánto cuesta.
     * El pago del préstamo se vincula al préstamo (la suscripción es opcional).
     */
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table): void {
            $table->unsignedInteger('dias_prestamo')
                ->default(24)
                ->after('cantidad_lecturas');
            $table->decimal('precio_prestamo', 10, 2)
                ->default(0)
                ->after('dias_prestamo');
        });

        Schema::table('pagos', function (Blueprint $table): void {
            $table->foreignId('prestamo_id')
                ->nullable()
                ->after('suscripcion_id')
                ->constrained('prestamos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('prestamo_id');
        });

        Schema::table('licencias', function (Blueprint $table): void {
            $table->dropColumn(['dias_prestamo', 'precio_prestamo']);
        });
    }
};
