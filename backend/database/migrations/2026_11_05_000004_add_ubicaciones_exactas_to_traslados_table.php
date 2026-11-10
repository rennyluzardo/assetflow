<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traslados', function (Blueprint $table) {
            // Fase 9 extendida: ubicaciones físicas exactas dentro de cada sede
            // (p. ej. "Bodega Norte", "Sala 2") para precisar origen/destino.
            $table->string('ubicacion_origen_exacta')->nullable()->after('zona_origen');
            $table->string('ubicacion_destino_exacta')->nullable()->after('zona_destino');
        });
    }

    public function down(): void
    {
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropColumn(['ubicacion_origen_exacta', 'ubicacion_destino_exacta']);
        });
    }
};
