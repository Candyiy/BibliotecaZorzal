<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            $table->string('modalidad', 20)->default('general')->after('tipo');
            $table->foreignId('libro_id')->nullable()->after('modalidad')->constrained('libros')->nullOnDelete();
            $table->unsignedInteger('cantidad_libros')->nullable()->after('libro_id');
            $table->unsignedInteger('trial_dias')->nullable()->after('precio');
            $table->json('beneficios')->nullable()->after('trial_dias');
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            $table->dropColumn([
                'modalidad',
                'libro_id',
                'cantidad_libros',
                'trial_dias',
                'beneficios',
            ]);
        });
    }
};
