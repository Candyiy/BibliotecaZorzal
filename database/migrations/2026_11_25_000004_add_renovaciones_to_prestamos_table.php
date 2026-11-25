<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table): void {
            $table->unsignedTinyInteger('renovaciones')->default(0)->after('dias_penalizacion');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table): void {
            $table->dropColumn('renovaciones');
        });
    }
};
