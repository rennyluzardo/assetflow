<?php

/*
| Raíz del disco `public` para evidencias fotográficas. Normaliza las barras
| (Windows suele escribir `D:\Fotos` o `D://Fotos`) e ignora rutas con letra
| de unidad de Windows cuando se ejecuta en macOS o Linux.
*/
$rawFotosPath = env('FOTOS_STORAGE_PATH');

if (
    is_string($rawFotosPath) &&
    $rawFotosPath !== '' &&
    !(PHP_OS_FAMILY !== 'Windows' && preg_match('/^[A-Za-z]:/', $rawFotosPath))
) {
    $fotosRoot = rtrim(preg_replace('#/+#', '/', str_replace('\\', '/', $rawFotosPath)), '/');
} else {
    $fotosRoot = storage_path('app/public');
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // El framework registra `GET /storage/{path}` para cada disco con
            // `serve => true`; esa URI choca con la ruta de `routes/web.php`
            // que sirve las evidencias del disco `public`. Este proyecto no
            // sirve archivos privados, así que se desactiva aquí.
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => $fotosRoot,
            // Ruta relativa: cuando la app se expone por ngrok o Cloudflare
            // Tunnel, el navegador remoto la resuelve contra el mismo origen
            // público en lugar de intentar `http://localhost:8000/storage/...`.
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => $fotosRoot,
    ],

];
