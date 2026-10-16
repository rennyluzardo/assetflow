<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Import mínimo para leer un XLSX sin transformación.
 *
 * La lectura completa se obtiene desde {@see \App\Services\Cargue\CargueMasivoService}
 * mediante `Excel::toArray()`, que expone las hojas como un arreglo crudo de filas.
 * Este objeto solo satisface el contrato `Import` requerido por la fachada.
 */
final class CubitFilasImport implements ToArray
{
    public function array(array $array): void
    {
        // Sin efecto: el mapeo estricto de columnas vive en el servicio.
    }
}
