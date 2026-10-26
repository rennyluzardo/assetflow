<?php

namespace App\Support;

/**
 * Fuente única de verdad de las placas reservadas para la suite E2E de Playwright.
 *
 * Tanto `InventarioMockSeeder` (que siembra) como el comando
 * `e2e:reset-fixtures` (que restaura) comparten esta definición para que el
 * estado "virgen" de cada placa sea idéntico: identificación original intacta,
 * sin intervención y sin analista.
 *
 * Reserva dos bloques: CPUs (AF-100001 .. AF-100015) y portátiles
 * (AF-100101 .. AF-100110), ambos reiniciables por `e2e:reset-fixtures`.
 */
final class InventarioE2eFixtures
{
    /**
     * Placas reservadas con su identificación virgen.
     *
     * @var list<array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}>
     */
    private const PLACAS = [
        // CPUs (AF-100001 .. AF-100015).
        ['placa_id' => 'AF-100001', 'serial' => 'SN-100001', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7010', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100002', 'serial' => 'SN-100002', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7010', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100003', 'serial' => 'SN-100003', 'marca' => 'HP', 'referencia' => 'ProDesk 400 G5', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100004', 'serial' => 'SN-100004', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7050', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100005', 'serial' => 'SN-100005', 'marca' => 'HP', 'referencia' => 'ProDesk 600 G4', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100006', 'serial' => 'SN-100006', 'marca' => 'HP', 'referencia' => 'ProDesk 600 G4', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100007', 'serial' => 'SN-100007', 'marca' => 'Lenovo', 'referencia' => 'ThinkCentre M720', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100008', 'serial' => 'SN-100008', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7050', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1003'],
        ['placa_id' => 'AF-100009', 'serial' => 'SN-100009', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7050', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1003'],
        ['placa_id' => 'AF-100010', 'serial' => 'SN-100010', 'marca' => 'HP', 'referencia' => 'ProDesk 600 G4', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1003'],
        ['placa_id' => 'AF-100011', 'serial' => 'SN-100011', 'marca' => 'Lenovo', 'referencia' => 'ThinkCentre M720', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1004'],
        ['placa_id' => 'AF-100012', 'serial' => 'SN-100012', 'marca' => 'Lenovo', 'referencia' => 'ThinkCentre M720', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1004'],
        ['placa_id' => 'AF-100013', 'serial' => 'SN-100013', 'marca' => 'Dell', 'referencia' => 'OptiPlex 7010', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1004'],
        ['placa_id' => 'AF-100014', 'serial' => 'SN-100014', 'marca' => 'HP', 'referencia' => 'ProDesk 600 G4', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1004'],
        ['placa_id' => 'AF-100015', 'serial' => 'SN-100015', 'marca' => 'Lenovo', 'referencia' => 'ThinkCentre M720', 'descripcion' => 'CPU', 'num_traslado' => 'TR-1004'],

        // Portátiles (AF-100101 .. AF-100110): vírgenes para el flujo /portatiles.
        ['placa_id' => 'AF-100101', 'serial' => 'SN-100101', 'marca' => 'Lenovo', 'referencia' => 'LAPTOP LENOVO THINKPAD T14 G2', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100102', 'serial' => 'SN-100102', 'marca' => 'HP', 'referencia' => 'LAPTOP HP ELITEBOOK 840 G8', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100103', 'serial' => 'SN-100103', 'marca' => 'Lenovo', 'referencia' => 'LAPTOP LENOVO THINKPAD T14 G2', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1001'],
        ['placa_id' => 'AF-100104', 'serial' => 'SN-100104', 'marca' => 'HP', 'referencia' => 'LAPTOP HP ELITEBOOK 840 G8', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100105', 'serial' => 'SN-100105', 'marca' => 'Lenovo', 'referencia' => 'LAPTOP LENOVO THINKPAD T14 G2', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100106', 'serial' => 'SN-100106', 'marca' => 'HP', 'referencia' => 'LAPTOP HP ELITEBOOK 840 G8', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1002'],
        ['placa_id' => 'AF-100107', 'serial' => 'SN-100107', 'marca' => 'Lenovo', 'referencia' => 'LAPTOP LENOVO THINKPAD T14 G2', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1003'],
        ['placa_id' => 'AF-100108', 'serial' => 'SN-100108', 'marca' => 'HP', 'referencia' => 'LAPTOP HP ELITEBOOK 840 G8', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1003'],
        ['placa_id' => 'AF-100109', 'serial' => 'SN-100109', 'marca' => 'Lenovo', 'referencia' => 'LAPTOP LENOVO THINKPAD T14 G2', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1004'],
        ['placa_id' => 'AF-100110', 'serial' => 'SN-100110', 'marca' => 'HP', 'referencia' => 'LAPTOP HP ELITEBOOK 840 G8', 'descripcion' => 'PORTATIL', 'num_traslado' => 'TR-1004'],
    ];

    /**
     * @return list<string>
     */
    public static function placas(): array
    {
        return array_column(self::PLACAS, 'placa_id');
    }

    /**
     * Placas de portátiles reservadas para el flujo /portatiles.
     *
     * @return list<string>
     */
    public static function portatiles(): array
    {
        return array_column(
            array_filter(self::PLACAS, static fn (array $placa): bool => $placa['descripcion'] === 'PORTATIL'),
            'placa_id',
        );
    }

    /**
     * @return list<array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}>
     */
    public static function definiciones(): array
    {
        return self::PLACAS;
    }
}
