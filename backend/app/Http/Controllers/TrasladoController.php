<?php

namespace App\Http\Controllers;

use App\Http\Requests\Traslados\ListarTrasladosRequest;
use App\Http\Requests\Traslados\RegistrarTrasladoRequest;
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

    public function buscar(string $placaId): JsonResponse
    {
        return response()->json(['data' => $this->traslados->buscarEquipo($placaId)]);
    }

    public function equipos(string $codigo): JsonResponse
    {
        return response()->json(['data' => $this->traslados->equipos($codigo)]);
    }

    public function recibir(string $codigo): JsonResponse
    {
        return response()->json(['data' => $this->traslados->recibir($codigo, request()->user())]);
    }
}
