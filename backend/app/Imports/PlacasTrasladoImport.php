<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Import mínimo para leer un CSV/XLSX con la lista de placas a agregar a un
 * traslado. La lectura cruda se expone mediante `Excel::toArray` desde
 * {@see \App\Services\Traslados\TrasladoService}; este objeto solo satisface el
 * contrato `Import` de la fachada de Maatwebsite/Excel.
 */
final class PlacasTrasladoImport implements ToArray
{
    public function array(array $array): void
    {
        // Sin efecto: el parseo de placas vive en el servicio.
    }
}
