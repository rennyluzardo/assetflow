<?php

namespace App\Http\Controllers;

use App\Exports\BitacoraExport;
use App\Http\Requests\Dashboard\DashboardRequest;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(DashboardRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->metricas($request->user(), $request)]);
    }

    public function exportar(DashboardRequest $request): BinaryFileResponse
    {
        $bitacora = $this->dashboard->bitacora($request->user(), $request);
        $nombre = 'bitacora_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new BitacoraExport($bitacora['cabeceras'], $bitacora['filas']),
            $nombre,
        );
    }
}
