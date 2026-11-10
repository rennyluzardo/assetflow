<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('zona_origen');
            $table->string('zona_destino');
            $table->string('estado')->default('EN_TRANSITO');
            $table->foreignId('analista_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('zona_destino');
            $table->index('estado');
            $table->index('analista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslados');
    }
};
