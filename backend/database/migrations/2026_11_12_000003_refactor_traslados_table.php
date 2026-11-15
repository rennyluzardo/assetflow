<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración quirúrgica del módulo de traslados hacia la máquina de 3 estados
 * (ABIERTO → EN_TRANSITO → RECIBIDO). No reescribe las migraciones
 * fundacionales: añade columnas, relaja `zona_origen` a nullable y elimina las
 * columnas obsoletas del diseño anterior, preservando los datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Nuevas columnas (nullable en DB para no romper filas preexistentes;
        //    las que son obligatorias se exigen en los FormRequest).
        Schema::table('traslados', function (Blueprint $table) {
            $table->string('ubicacion_origen')->nullable()->after('zona_origen');
            $table->string('ubicacion_destino')->nullable()->after('zona_destino');
            $table->text('observaciones')->nullable()->after('estado');
            $table->text('observacion_cierre_parcial')->nullable()->after('observaciones');
            $table->foreignId('creado_por_id')->nullable()->after('observacion_cierre_parcial')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('despachado_por_id')->nullable()->after('creado_por_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('despachado_at')->nullable()->after('despachado_por_id');
            $table->foreignId('recibido_por_id')->nullable()->after('despachado_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('recibido_at')->nullable()->after('recibido_por_id');
        });

        // 2. El origen nace en NULL al crear el traslado (se "bautiza" con el
        //    primer equipo escaneado).
        Schema::table('traslados', function (Blueprint $table) {
            $table->string('zona_origen')->nullable()->change();
        });

        // 3. Estado inicial del nuevo ciclo de vida.
        Schema::table('traslados', function (Blueprint $table) {
            $table->string('estado')->default('ABIERTO')->change();
        });

        // 4. Columnas del diseño anterior (reemplazadas por el nuevo esquema).
        // `analista_id` se conserva como columna residual: SQLite no permite
        // DROP COLUMN sobre una columna con FK sin reconstruir la tabla, y el
        // nuevo modelo ya no la utiliza (queda reemplazada por `creado_por_id`).
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropColumn(['ubicacion_origen_exacta', 'ubicacion_destino_exacta']);
        });

        // 5. Índices de consulta por filtro y por ciclo de vida.
        Schema::table('traslados', function (Blueprint $table) {
            $table->index('ubicacion_destino');
            $table->index('creado_por_id');
            $table->index('despachado_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropIndex(['despachado_por_id']);
            $table->dropIndex(['creado_por_id']);
            $table->dropIndex(['ubicacion_destino']);
        });

        Schema::table('traslados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recibido_por_id');
            $table->dropConstrainedForeignId('despachado_por_id');
            $table->dropConstrainedForeignId('creado_por_id');
            $table->dropColumn([
                'observacion_cierre_parcial',
                'observaciones',
                'ubicacion_destino',
                'ubicacion_origen',
            ]);
        });

        Schema::table('traslados', function (Blueprint $table) {
            $table->string('estado')->default('EN_TRANSITO')->change();
        });

        Schema::table('traslados', function (Blueprint $table) {
            $table->string('zona_origen')->nullable(false)->change();
        });
    }
};
