<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pertenece', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')
                ->constrained('libros')
                ->cascadeOnDelete();

            $table->foreignId('autor_id')
                ->constrained('autores')
                ->cascadeOnDelete();

            $table->unique(['libro_id', 'autor_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertenece');
    }
};
