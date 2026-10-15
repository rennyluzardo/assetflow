<?php

namespace App\Http\Controllers;

use App\Http\Requests\Soplado\RegistrarSopladoRequest;
use App\Services\Soplado\SopladoService;
use Illuminate\Http\JsonResponse;

class SopladoController extends Controller
{
    public function __construct(private readonly SopladoService $soplados) {}

    public function store(RegistrarSopladoRequest $request): JsonResponse
    {
        $registro = $this->soplados->registrar(
            $request->validated('placa_id'),
            $request->validated(),
            $request->destino(),
            $request->user()->id,
        );

        return response()->json(['data' => $registro], 201);
    }
}
