<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_general_id')->constrained('inventario_general')->onDelete('cascade');
            $table->string('placa_id');
            $table->string('serial');
            $table->string('marca')->nullable();
            $table->string('referencia')->nullable();
            $table->string('descripcion');
            $table->string('num_traslado');
            $table->string('tipo_gestion');
            $table->boolean('requiere_soplado_o_pasta')->default(false);
            $table->boolean('esta_funcional')->nullable();
            $table->boolean('tiene_garantia')->nullable();
            $table->boolean('es_obsoleta')->nullable();
            $table->boolean('hay_pieza')->nullable();
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
            $table->index('tipo_gestion');
            $table->index('estado');
            $table->index('analista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
