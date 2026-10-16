<?php

namespace App\Services\Cargue;

use App\Imports\CubitFilasImport;
use App\Models\InventarioGeneral;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Ingesta masiva de activos desde el formato de exportación de Cubit (Teleperformance).
 *
 * El servicio está aislado para poder sustituir la lectura de Excel/CSV por el
 * cliente HTTP de la API de Teleperformance sin tocar el resto del flujo.
 * Recibe un archivo (.csv o .xlsx) y el número de traslado; cada fila se mapea
 * estrictamente contra las columnas de `inventario_general`.
 */
final class CargueMasivoService
{
    /** Columnas obligatorias en la cabecera del archivo Cubit. */
    private const COLUMNAS_OBLIGATORIAS = ['placa_id', 'serial', 'descripcion'];

    /** Etiquetas humanas de las columnas obligatorias para los mensajes de error. */
    private const ETIQUETAS_COLUMNA = [
        'placa_id' => 'Identificador 1',
        'serial' => 'Identificador 2',
        'descripcion' => 'Descripción',
    ];

    /**
     * Alias de cabecera (normalizados) -> columna canónica de `inventario_general`.
     * Cubre las variantes reales del formato Cubit (espacios, tildes, "Ref. Principal").
     */
    private const ALIAS_COLUMNA = [
        'identificador_1' => 'placa_id',
        'identificador1' => 'placa_id',
        'identificador' => 'placa_id',
        'placa' => 'placa_id',
        'placa_id' => 'placa_id',
        'placaid' => 'placa_id',

        'identificador_2' => 'serial',
        'identificador2' => 'serial',
        'serial' => 'serial',
        'numero_serial' => 'serial',
        'num_serial' => 'serial',

        'marca' => 'marca',

        'referencia' => 'referencia',
        'ref' => 'referencia',
        'ref_principal' => 'referencia',
        'referencia_principal' => 'referencia',

        'descripcion' => 'descripcion',
        'tipo' => 'descripcion',
        'tipo_activo' => 'descripcion',

        'zona' => 'zona_actual',
        'zona_actual' => 'zona_actual',
    ];

    /**
     * Procesa el archivo y persiste los registros válidos en `inventario_general`.
     *
     * @return array{num_traslado: string, insertados: int, omitidos: int, errores: list<array{fila: int, error: string}>}
     */
    public function procesar(UploadedFile $archivo, string $numTraslado, int $usuarioCargue): array
    {
        $filas = $this->leerFilas($archivo);
        $cabecera = array_shift($filas) ?? [];

        [$mapeo, $erroresCabecera] = $this->resolverMapeo($cabecera);

        if ($erroresCabecera !== []) {
            throw ValidationException::withMessages([
                'archivo' => $erroresCabecera,
            ]);
        }

        $registros = [];
        $errores = [];
        $placasVistas = [];

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2; // +1 por la cabecera y +1 por ser base-1.

            $datos = $this->mapearFila($fila, $mapeo);

            if ($datos === []) {
                continue; // Fila completamente vacía: se ignora sin penalizar.
            }

            $fallo = $this->validarFila($datos);

            if ($fallo !== null) {
                $errores[] = ['fila' => $numeroFila, 'error' => $fallo];
                continue;
            }

            if (isset($placasVistas[$datos['placa_id']])) {
                $errores[] = [
                    'fila' => $numeroFila,
                    'error' => "Placa {$datos['placa_id']} duplicada dentro del archivo.",
                ];
                continue;
            }

            $placasVistas[$datos['placa_id']] = true;
            $registros[] = [
                'fila' => $numeroFila,
                'registro' => $this->construirRegistro($datos, $numTraslado, $usuarioCargue),
            ];
        }

        $insertados = $this->insertarMasivo($registros, $errores);

        return [
            'num_traslado' => $numTraslado,
            'insertados' => $insertados,
            'omitidos' => count($errores),
            'errores' => array_slice($errores, 0, 100),
        ];
    }

    /**
     * Lee el archivo a una lista de filas (arrays indexados por posición de celda).
     *
     * @return list<list<string>>
     */
    private function leerFilas(UploadedFile $archivo): array
    {
        return match (strtolower($archivo->getClientOriginalExtension())) {
            'csv' => $this->leerCsv($archivo),
            'xlsx' => $this->leerXlsx($archivo),
            default => throw ValidationException::withMessages([
                'archivo' => 'Formato de archivo no soportado. Use CSV o XLSX.',
            ]),
        };
    }

    /**
     * @return list<list<string>>
     */
    private function leerCsv(UploadedFile $archivo): array
    {
        $manejador = fopen($archivo->getRealPath(), 'r');

        if ($manejador === false) {
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo leer el archivo CSV.',
            ]);
        }

        $primeraLinea = fgets($manejador);
        $delimitador = $primeraLinea === false ? ',' : $this->detectarDelimitador($primeraLinea);

        rewind($manejador);

        $filas = [];

        while (($fila = fgetcsv($manejador, null, $delimitador, '"', '')) !== false) {
            $filas[] = array_map(fn ($celda) => (string) $celda, $fila);
        }

        fclose($manejador);

        return $filas;
    }

    /**
     * @return list<list<string>>
     */
    private function leerXlsx(UploadedFile $archivo): array
    {
        $hojas = Excel::toArray(new CubitFilasImport, $archivo->getRealPath(), null, Excel::XLSX);

        $filas = $hojas[0] ?? [];

        return array_map(
            fn (array $fila) => array_map(fn ($celda) => $celda === null ? '' : (string) $celda, $fila),
            $filas,
        );
    }

    /**
     * Detecta el separador de columnas más frecuente de la cabecera (CSV español
     * suele usar ';', el inglés ',', y algunos exportes tabulaciones).
     */
    private function detectarDelimitador(string $linea): string
    {
        $mejor = ',';
        $maximo = -1;

        foreach ([',', ';', "\t", '|'] as $candidato) {
            $ocurrencias = substr_count($linea, $candidato);

            if ($ocurrencias > $maximo) {
                $maximo = $ocurrencias;
                $mejor = $candidato;
            }
        }

        return $mejor;
    }

    /**
     * Normaliza la cabecera y construye el mapeo [índice de columna -> canónica].
     *
     * @param  list<string>  $cabecera
     * @return array{0: array<int, string>, 1: list<string>}
     */
    private function resolverMapeo(array $cabecera): array
    {
        $mapeo = [];
        $presentes = [];

        foreach ($cabecera as $indice => $nombre) {
            $normalizado = $this->normalizarCabecera((string) $nombre);

            if ($normalizado === '') {
                continue;
            }

            $canonica = self::ALIAS_COLUMNA[$normalizado] ?? null;

            if ($canonica === null) {
                continue; // Columnas extra del formato Cubit: se ignoran.
            }

            $mapeo[$indice] = $canonica;
            $presentes[$canonica] = true;
        }

        $errores = [];

        foreach (self::COLUMNAS_OBLIGATORIAS as $columna) {
            if (! isset($presentes[$columna])) {
                $errores[] = "Falta la columna obligatoria \"".self::ETIQUETAS_COLUMNA[$columna].'".';
            }
        }

        return [$mapeo, $errores];
    }

    /**
     * Convierte una celda de cabecera a una clave normalizada (minúscula, sin
     * tildes, sin BOM, con espacios y puntuación colapsados a '_').
     */
    private function normalizarCabecera(string $valor): string
    {
        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor);
        $valor = mb_strtolower(trim($valor), 'UTF-8');
        $valor = strtr($valor, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $valor = preg_replace('/[^a-z0-9]+/', '_', $valor);

        return trim($valor ?? '', '_');
    }

    /**
     * @param  array<int, string>  $mapeo
     * @return array<string, string>
     */
    private function mapearFila(array $fila, array $mapeo): array
    {
        $datos = [];

        foreach ($mapeo as $indice => $canonica) {
            $valor = trim((string) ($fila[$indice] ?? ''));

            if ($valor !== '') {
                $datos[$canonica] = $valor;
            }
        }

        return $datos;
    }

    /**
     * @param  array<string, string>  $datos
     */
    private function validarFila(array $datos): ?string
    {
        foreach (self::COLUMNAS_OBLIGATORIAS as $columna) {
            if (($datos[$columna] ?? '') === '') {
                return 'Falta "'.self::ETIQUETAS_COLUMNA[$columna].'".';
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $datos
     * @return array<string, mixed>
     */
    private function construirRegistro(array $datos, string $numTraslado, int $usuarioCargue): array
    {
        $ahora = now();

        return [
            'placa_id' => $datos['placa_id'],
            'serial' => $datos['serial'],
            'marca' => $datos['marca'] ?? null,
            'referencia' => $datos['referencia'] ?? null,
            'descripcion' => $datos['descripcion'],
            'zona_actual' => $datos['zona_actual'] ?? null,
            // Un activo recién ingerido arranca siempre en Almacén; la ubicación
            // operativa se deriva posteriormente de la máquina de estados.
            'ubicacion_actual' => 'ALMACEN',
            'num_traslado' => $numTraslado,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $usuarioCargue,
            'analista_intervencion' => null,
            'modulo_intervencion' => null,
            'fecha_intervencion' => null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }

    /**
     * Inserta por lotes descartando placas ya presentes en la base de datos.
     *
     * @param  list<array{fila: int, registro: array<string, mixed>}>  $registros
     * @param  list<array{fila: int, error: string}>  $errores
     */
    private function insertarMasivo(array $registros, array &$errores): int
    {
        if ($registros === []) {
            return 0;
        }

        $placas = array_column(array_column($registros, 'registro'), 'placa_id');

        $existentes = InventarioGeneral::query()
            ->whereIn('placa_id', $placas)
            ->pluck('placa_id')
            ->flip();

        $nuevos = [];

        foreach ($registros as $entrada) {
            if (isset($existentes[$entrada['registro']['placa_id']])) {
                $errores[] = [
                    'fila' => $entrada['fila'],
                    'error' => "Placa {$entrada['registro']['placa_id']} ya existe en el inventario.",
                ];
                continue;
            }

            $nuevos[] = $entrada['registro'];
        }

        DB::transaction(function () use ($nuevos): void {
            foreach (array_chunk($nuevos, 500) as $lote) {
                DB::table('inventario_general')->insert($lote);
            }
        });

        return count($nuevos);
    }
}
