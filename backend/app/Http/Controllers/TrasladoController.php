<?php

namespace App\Http\Controllers;

use App\Http\Requests\Traslados\AgregarEquipoTrasladoRequest;
use App\Http\Requests\Traslados\ImportarExcelTrasladoRequest;
use App\Http\Requests\Traslados\ListarTrasladosRequest;
use App\Http\Requests\Traslados\RecibirTrasladoRequest;
use App\Http\Requests\Traslados\RegistrarTrasladoRequest;
use App\Http\Requests\Traslados\VerificarEquipoTrasladoRequest;
use App\Models\Traslado;
use App\Services\Traslados\TrasladoService;
use Illuminate\Http\JsonResponse;

class TrasladoController extends Controller
{
    public function __construct(private readonly TrasladoService $traslados) {}

    public function index(ListarTrasladosRequest $request): JsonResponse
    {
        return response()->json($this->traslados->listar($request));
    }

    public function store(RegistrarTrasladoRequest $request): JsonResponse
    {
        $traslado = $this->traslados->registrar($request->validated(), $request->user());

        return response()->json(['data' => $traslado], 201);
    }

    public function show(Traslado $traslado): JsonResponse
    {
        return response()->json(['data' => $this->traslados->detalle($traslado)]);
    }

    public function buscar(string $placaId): JsonResponse
    {
        return response()->json(['data' => $this->traslados->buscarEquipo($placaId)]);
    }

    public function agregarEquipo(Traslado $traslado, AgregarEquipoTrasladoRequest $request): JsonResponse
    {
        $pivote = $this->traslados->agregarEquipo($traslado, $request->validated('placa_id'));

        return response()->json(['data' => $pivote], 201);
    }

    public function importarExcel(Traslado $traslado, ImportarExcelTrasladoRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->traslados->importarExcel(
                $traslado,
                $request->validated('archivo'),
                $request->user(),
            ),
        ]);
    }

    public function quitarEquipo(Traslado $traslado, int $equipoId): JsonResponse
    {
        $this->traslados->quitarEquipo($traslado, $equipoId);

        return response()->json(['data' => null]);
    }

    public function despachar(Traslado $traslado): JsonResponse
    {
        return response()->json([
            'data' => $this->traslados->despachar($traslado, request()->user()),
        ]);
    }

    public function verificarEquipo(Traslado $traslado, VerificarEquipoTrasladoRequest $request): JsonResponse
    {
        $pivote = $this->traslados->verificarEquipo($traslado, $request->validated('placa_id'), request()->user());

        return response()->json(['data' => $pivote]);
    }

    public function recibir(Traslado $traslado, RecibirTrasladoRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->traslados->recibir($traslado, request()->user()),
        ]);
    }

    public function revertirApertura(Traslado $traslado): JsonResponse
    {
        return response()->json([
            'data' => $this->traslados->revertirApertura($traslado, request()->user()),
        ]);
    }
}
