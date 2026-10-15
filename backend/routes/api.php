<?php

use App\Http\Controllers\EquiposController;
use App\Http\Controllers\SopladoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/equipos/buscar/{placaId}', [EquiposController::class, 'buscar'])
        ->middleware('permission:cpus.ver_bitacora')
        ->name('equipos.buscar');

    Route::post('/equipos', [EquiposController::class, 'store'])
        ->middleware('permission:cpus.registrar')
        ->name('equipos.store');

    Route::post('/equipos/transicionar', [EquiposController::class, 'transicionar'])
        ->middleware('permission:cpus.registrar')
        ->name('equipos.transicionar');

    Route::post('/soplado', [SopladoController::class, 'store'])
        ->middleware('permission:soplado.registrar')
        ->name('soplado.store');
});
