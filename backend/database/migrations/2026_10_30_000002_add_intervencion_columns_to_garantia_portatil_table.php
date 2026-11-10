<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garantia_portatil', function (Blueprint $table) {
            // Fase 8: intervención en sitio (cambio de pieza / repotenciación).
            $table->boolean('pieza_es_nueva')->nullable()->after('cantidad_danos');
            $table->string('subtipo_intervencion')->nullable()->after('pieza_es_nueva');
            $table->text('especificacion_repotenciacion')->nullable()->after('subtipo_intervencion');
        });
    }

    public function down(): void
    {
        Schema::table('garantia_portatil', function (Blueprint $table) {
            $table->dropColumn(['pieza_es_nueva', 'subtipo_intervencion', 'especificacion_repotenciacion']);
        });
    }
};
