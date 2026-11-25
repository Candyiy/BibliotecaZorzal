<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('suscripcion_id')
                ->nullable()
                ->constrained('suscripciones')
                ->nullOnDelete();
            $table->string('referencia')->unique();
            $table->decimal('monto', 10, 2);
            $table->string('metodo')->default('interno');
            $table->string('estado')->default('pagado');
            $table->date('f_pago');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
