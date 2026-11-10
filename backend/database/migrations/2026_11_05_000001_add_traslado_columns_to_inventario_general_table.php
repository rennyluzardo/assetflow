<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_general', function (Blueprint $table) {
            // Instante en que el activo entró al inventario (cargue masivo). Se
            // usa para el cálculo de SLA (fecha_cargue -> fecha_intervencion).
            $table->timestamp('fecha_cargue')->nullable()->after('intervenido');
            // Ubicación física de origen del lote, fijada manualmente en el
            // cargue masivo para no depender de la columna "Zona" de Cubit.
            $table->string('ubicacion_origen')->nullable()->after('fecha_cargue');

            $table->index('fecha_cargue');
            $table->index('ubicacion_origen');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_general', function (Blueprint $table) {
            $table->dropIndex(['ubicacion_origen']);
            $table->dropIndex(['fecha_cargue']);
            $table->dropColumn(['ubicacion_origen', 'fecha_cargue']);
        });
    }
};
