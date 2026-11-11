<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de cálculo `.xlsx` de la bitácora de intervenciones del taller.
 *
 * Recibe las filas ya resueltas por `DashboardService` (con la columna
 * "Observaciones" incluida) para mantener la lógica de RBAC y de negocio
 * aislada del formato de exportación.
 */
final class BitacoraExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @param  list<string>  $cabeceras
     * @param  list<list<string>>  $filas
     */
    public function __construct(
        private readonly array $cabeceras,
        private readonly array $filas,
    ) {}

    /**
     * @return Collection<int, list<string>>
     */
    public function collection(): Collection
    {
        return collect($this->filas);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->cabeceras;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
