<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['zona_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
