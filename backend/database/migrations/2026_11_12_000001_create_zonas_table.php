<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            // Sede operativa a la que pertenece la zona (ej. 'COLOMBIA', 'GUYANA').
            $table->string('sede');
            // Nombre de la zona dentro de su sede (ej. 'BOGOTA', 'MEDELLIN').
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sede', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
