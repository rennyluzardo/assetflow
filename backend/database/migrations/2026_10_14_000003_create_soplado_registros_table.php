<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soplado_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->boolean('energiza');
            $table->boolean('da_video');
            $table->boolean('acceso_bios');
            $table->string('tipo_suciedad');
            $table->boolean('aplica_pasta_termica');
            $table->string('foto_ruta');
            $table->string('ubicacion_destino');
            $table->foreignId('analista_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('equipo_id');
            $table->index('analista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soplado_registros');
    }
};
