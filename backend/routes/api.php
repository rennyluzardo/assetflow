<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquiposController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PortatilesController;
use App\Http\Controllers\SopladoController;
use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\TrasladoController;
use App\Http\Controllers\ZonaUbicacionController;
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

    Route::get('/portatiles', [PortatilesController::class, 'index'])
        ->middleware('permission:portatiles.ver_bitacora')
        ->name('portatiles.index');

    Route::get('/portatiles/buscar/{placaId}', [PortatilesController::class, 'buscar'])
        ->middleware('permission:portatiles.registrar')
        ->name('portatiles.buscar');

    Route::post('/portatiles', [PortatilesController::class, 'store'])
        ->middleware('permission:portatiles.registrar')
        ->name('portatiles.store');

    Route::patch('/portatiles/{id}/cerrar', [PortatilesController::class, 'cerrar'])
        ->middleware('permission:portatiles.registrar')
        ->name('portatiles.cerrar');

    Route::post('/portatiles/{id}/foto', [PortatilesController::class, 'foto'])
        ->middleware('permission:portatiles.registrar')
        ->name('portatiles.foto');

    Route::get('/trazabilidad', [TrazabilidadController::class, 'index'])
        ->middleware('permission:trazabilidad.ver')
        ->name('trazabilidad.index');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.ver_solo_propio')
        ->name('dashboard.index');

    Route::get('/dashboard/exportar', [DashboardController::class, 'exportar'])
        ->middleware('permission:dashboard.ver_solo_propio')
        ->name('dashboard.exportar');

    Route::get('/traslados', [TrasladoController::class, 'index'])
        ->middleware('permission:traslados.ver')
        ->name('traslados.index');

    Route::get('/traslados/buscar/{placaId}', [TrasladoController::class, 'buscar'])
        ->middleware('permission:traslados.ver')
        ->name('traslados.buscar');

    Route::get('/traslados/{traslado}', [TrasladoController::class, 'show'])
        ->middleware('permission:traslados.ver')
        ->name('traslados.show');

    Route::post('/traslados', [TrasladoController::class, 'store'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.store');

    Route::post('/traslados/{traslado}/equipos', [TrasladoController::class, 'agregarEquipo'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.agregar-equipo');

    Route::post('/traslados/{traslado}/equipos/importar', [TrasladoController::class, 'importarExcel'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.importar');

    Route::delete('/traslados/{traslado}/equipos/{equipoId}', [TrasladoController::class, 'quitarEquipo'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.quitar-equipo');

    Route::post('/traslados/{traslado}/despachar', [TrasladoController::class, 'despachar'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.despachar');

    Route::post('/traslados/{traslado}/equipos/verificar', [TrasladoController::class, 'verificarEquipo'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.verificar');

    Route::post('/traslados/{traslado}/recibir', [TrasladoController::class, 'recibir'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.recibir');

    Route::post('/traslados/{traslado}/revertir-apertura', [TrasladoController::class, 'revertirApertura'])
        ->middleware('permission:traslados.registrar')
        ->name('traslados.revertir-apertura');

    // Maestro de zonas y ubicaciones: selectores de destino (cualquier usuario)
    // y ABM de administración.
    Route::get('/zonas', [ZonaUbicacionController::class, 'zonasActivas'])
        ->name('zonas.activas');

    Route::middleware('permission:zonas_ubicaciones.gestionar')->group(function () {
        Route::get('/zonas-ubicaciones', [ZonaUbicacionController::class, 'gestion'])->name('zonas.gestion');
        Route::post('/zonas', [ZonaUbicacionController::class, 'crearZona'])->name('zonas.store');
        Route::put('/zonas/{zona}', [ZonaUbicacionController::class, 'actualizarZona'])->name('zonas.update');
        Route::patch('/zonas/{zona}/activar', [ZonaUbicacionController::class, 'activarZona'])->name('zonas.activar');
        Route::patch('/zonas/{zona}/inactivar', [ZonaUbicacionController::class, 'inactivarZona'])->name('zonas.inactivar');
        Route::post('/zonas/{zona}/ubicaciones', [ZonaUbicacionController::class, 'crearUbicacion'])->name('ubicaciones.store');
        Route::put('/ubicaciones/{ubicacion}', [ZonaUbicacionController::class, 'actualizarUbicacion'])->name('ubicaciones.update');
        Route::patch('/ubicaciones/{ubicacion}/activar', [ZonaUbicacionController::class, 'activarUbicacion'])->name('ubicaciones.activar');
        Route::patch('/ubicaciones/{ubicacion}/inactivar', [ZonaUbicacionController::class, 'inactivarUbicacion'])->name('ubicaciones.inactivar');
    });
});
