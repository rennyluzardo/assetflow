<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Fase 9: información obligatoria de garantía para CPUs en PENDIENTE_GARANTIA.
            $table->string('ticket_garantia')->nullable()->after('serial_disco');
            $table->string('pieza_garantia')->nullable()->after('ticket_garantia');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['ticket_garantia', 'pieza_garantia']);
        });
    }
};
