<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rol', 50)->default('cliente');
            });
        }

        if (! DB::table('users')->where('rol', 'superAdmin')->exists()) {
            $primerUsuario = DB::table('users')
                ->orderBy('created_at')
                ->orderBy('id')
                ->first();

            if ($primerUsuario) {
                DB::table('users')
                    ->where('id', $primerUsuario->id)
                    ->update(['rol' => 'superAdmin']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('rol');
            });
        }
    }
};
