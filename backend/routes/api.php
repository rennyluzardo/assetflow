<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CargueMasivoController;
use App\Http\Controllers\EquiposController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\SopladoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/equipos/buscar/{placaId}', [EquiposController::class, 'buscar'])
        ->middleware('permission:cpus.ver_bitacora')
        ->name('equipos.buscar');

    Route::post('/equipos', [EquiposController::class, 'store'])
        ->middleware('permission:cpus.registrar')
        ->name('equipos.store');

    Route::post('/equipos/transicionar', [EquiposController::class, 'transicionar'])
        ->middleware('permission:cpus.registrar')
        ->name('equipos.transicionar');

    Route::get('/soplado/buscar/{placaId}', [SopladoController::class, 'buscar'])
        ->middleware('permission:soplado.ver_bitacora')
        ->name('soplado.buscar');

    Route::post('/soplado', [SopladoController::class, 'store'])
        ->middleware('permission:soplado.registrar')
        ->name('soplado.store');

    Route::get('/inventario', [InventarioController::class, 'index'])
        ->middleware('permission:inventario.ver')
        ->name('inventario.index');

    Route::post('/cargue-masivo', [CargueMasivoController::class, 'store'])
        ->middleware('permission:cargue_masivo.ejecutar')
        ->name('cargue-masivo.store');
});
