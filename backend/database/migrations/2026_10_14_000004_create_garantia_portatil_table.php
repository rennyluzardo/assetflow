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
            $table->string('placa_id');
            $table->string('serial');
            $table->string('marca')->nullable();
            $table->string('referencia')->nullable();
            $table->string('num_traslado');
            $table->string('tipo_gestion');
            $table->boolean('test_lenovo');
            $table->string('serial_disco');
            $table->string('ticket_proveedor')->nullable();
            $table->string('parte_fru')->nullable();
            $table->string('piezas_solicitar')->nullable();
            $table->string('ubicacion_destino');
            $table->string('estado');
            $table->string('foto_equipo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('analista_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('inventario_general_id');
            $table->index('placa_id');
            $table->index('serial');
            $table->index('num_traslado');
            $table->index('estado');
            $table->index('analista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garantia_portatil');
    }
};
