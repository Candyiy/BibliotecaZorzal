<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_libro', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'libro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_libro');
    }
};
