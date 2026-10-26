<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardRequest;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(DashboardRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->metricas($request->user(), $request)]);
    }

    public function exportarCsv(DashboardRequest $request): StreamedResponse
    {
        $nombre = 'bitacora_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($request): void {
            foreach ($this->dashboard->exportarCsv($request->user(), $request) as $linea) {
                echo $linea;
            }
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
