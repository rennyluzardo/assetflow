<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garantia_portatil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_general_id')->constrained('inventario_general')->onDelete('cascade');

            // Identificadores espejo de `inventario_general` (sin digitación manual).
            $table->string('placa_id');
            $table->string('serial');
            $table->string('marca')->nullable();
            $table->string('referencia')->nullable();
            $table->string('num_traslado');

            // Matriz de Clasificación de Laptops.
            $table->string('clasificacion');
            $table->string('gravedad_novedad')->nullable();      // NOVEDAD
            $table->string('origen_dano_garantia')->nullable();  // GARANTIA
            $table->string('causal_baja')->nullable();           // BAJA
            $table->unsignedInteger('cantidad_danos')->nullable();

            // Campos técnicos de laptop.
            $table->boolean('test_lenovo');
            $table->string('serial_disco')->nullable();
            $table->string('ticket_proveedor')->nullable();
            $table->string('partes_fru')->nullable();
            $table->text('piezas_solicitar')->nullable();
            $table->string('reparado_por')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('foto_evidencia')->nullable();

            // Ubicación y estado unificados con el estándar del sistema.
            $table->string('ubicacion_destino');
            $table->string('estado');

            $table->foreignId('analista_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('inventario_general_id');
            $table->index('placa_id');
            $table->index('serial');
            $table->index('num_traslado');
            $table->index('clasificacion');
            $table->index('estado');
            $table->index('analista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garantia_portatil');
    }
};
