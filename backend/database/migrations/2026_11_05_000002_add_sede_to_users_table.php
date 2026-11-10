<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sede física del operario (BOGOTA | MEDELLIN). Nula para perfiles
            // sin sede asignada (administración/supervisión sin restricción).
            $table->string('sede')->nullable()->after('email');

            $table->index('sede');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['sede']);
            $table->dropColumn('sede');
        });
    }
};
