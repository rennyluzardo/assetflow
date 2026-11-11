<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Import mínimo para leer un XLSX de Cubit sin transformación.
 *
 * La lectura completa se obtiene desde {@see \App\Services\Cargue\CargueMasivoService}
 * mediante `Excel::toArray()`, que expone las hojas como un arreglo crudo de filas.
 * Este objeto solo satisface el contrato `Import` requerido por la fachada.
 *
 * El exporte nativo de Cubit trae 3 filas de títulos institucionales antes de la
 * cabecera real de columnas ("Identificador 1", "Identificador 2", ...). Se usa
 * `WithStartRow` (y no `WithHeadingRow`) porque el servicio extrae la cabecera por
 * sí mismo con `array_shift`; `WithHeadingRow` haría que Laravel Excel descartara
 * también la fila de cabecera y arrancara en la fila 5, rompiendo ese mapeo.
 */
final class CubitFilasImport implements ToArray, WithStartRow
{
    /** Fila (base-1) donde comienza la cabecera real de columnas del XLSX de Cubit. */
    public const FILA_CABECERA = 4;

    public function startRow(): int
    {
        return self::FILA_CABECERA;
    }

    public function array(array $array): void
    {
        // Sin efecto: el mapeo estricto de columnas vive en el servicio.
    }
}
