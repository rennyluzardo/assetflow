<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Fase 8: rama de retiro directo de CPUs (BAJA) y baja por diagnóstico.
            $table->text('motivo_baja')->nullable()->after('observaciones');
            $table->string('serial_disco')->nullable()->after('motivo_baja');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['motivo_baja', 'serial_disco']);
        });
    }
};
