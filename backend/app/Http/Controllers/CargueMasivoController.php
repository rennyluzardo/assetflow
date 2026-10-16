<?php

namespace App\Http\Controllers;

use App\Http\Requests\CargueMasivo\ProcesarCargueRequest;
use App\Services\Cargue\CargueMasivoService;
use Illuminate\Http\JsonResponse;

class CargueMasivoController extends Controller
{
    public function __construct(private readonly CargueMasivoService $cargue) {}

    public function store(ProcesarCargueRequest $request): JsonResponse
    {
        $resultado = $this->cargue->procesar(
            $request->file('archivo'),
            $request->validated('num_traslado'),
            $request->user()->id,
        );

        return response()->json(['data' => $resultado], 201);
    }
}
