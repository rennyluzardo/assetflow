<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_general', function (Blueprint $table) {
            $table->id();
            $table->string('placa_id')->unique();
            $table->string('serial');
            $table->string('marca')->nullable();
            $table->string('referencia')->nullable();
            $table->string('descripcion');
            $table->string('zona_actual')->nullable();
            $table->string('ubicacion_actual')->default('ALMACEN');
            $table->string('num_traslado');
            $table->string('estado_actual')->nullable();
            $table->boolean('intervenido')->default(false);
            $table->foreignId('usuario_cargue')->constrained('users')->onDelete('restrict');
            $table->foreignId('analista_intervencion')->nullable()->constrained('users')->onDelete('set null');
            $table->string('modulo_intervencion')->nullable();
            $table->timestamp('fecha_intervencion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('serial');
            $table->index('num_traslado');
            $table->index('ubicacion_actual');
            $table->index('estado_actual');
            $table->index('intervenido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_general');
    }
};
