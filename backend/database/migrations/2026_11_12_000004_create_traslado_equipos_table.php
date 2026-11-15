<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslado_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traslado_id')->constrained('traslados')->cascadeOnDelete();
            $table->foreignId('inventario_id')->constrained('inventario_general')->cascadeOnDelete();
            // Denormalizado para búsqueda por placa sin join y para preservar el
            // historial aunque la fila de inventario se archive (soft delete).
            $table->string('placa_id');
            // Snapshot del origen en el momento de agregar el activo al lote.
            $table->string('zona_origen_snapshot')->nullable();
            $table->string('ubicacion_origen_snapshot')->nullable();
            // Recepción individual: permite cierre parcial (k < N).
            $table->boolean('recibido')->default(false);
            $table->foreignId('recibido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recibido_at')->nullable();
            $table->timestamps();

            $table->unique(['traslado_id', 'inventario_id']);
            $table->index('placa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslado_equipos');
    }
};
