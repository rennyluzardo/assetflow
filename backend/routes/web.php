<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\PathTraversalDetected;

/*
| Evidencias fotográficas del disco `public`.
|
| Se sirven con una ruta de Laravel (no con el symlink de `storage:link`)
| para que en Windows la raíz personalizada `C:/Fotos` funcione sin permisos
| de Administrador y para que el túnel público exponga las fotos junto con
| la app. La URL relativa `/storage/...` la resuelve el navegador contra el
| mismo origen (Vite la proxya al backend en desarrollo).
*/
Route::get('/storage/{path}', function (string $path) {
    $disco = Storage::disk('public');

    try {
        abort_unless($disco->exists($path), 404);

        return $disco->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    } catch (PathTraversalDetected) {
        abort(404);
    }
})
    ->where('path', '.*')
    ->name('storage.public')
    ->withoutMiddleware('web');
