<?php

namespace App\Http\Controllers;

use App\Http\Requests\Soplado\RegistrarSopladoRequest;
use App\Services\Equipos\EquipoService;
use App\Services\Soplado\SopladoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SopladoController extends Controller
{
    public function __construct(
        private readonly SopladoService $soplados,
        private readonly EquipoService $equipos,
    ) {}

    public function buscar(string $placaId): JsonResponse
    {
        return response()->json(['data' => $this->equipos->buscar($placaId)]);
    }

    public function store(RegistrarSopladoRequest $request): JsonResponse
    {
        $foto = $request->file('foto_ruta');
        $ruta = $foto->storeAs('soplado', (string) Str::uuid().'.'.$foto->extension(), 'public');

        $datos = $request->validated();
        $datos['foto_ruta'] = $ruta;

        $registro = $this->soplados->registrar(
            $request->validated('placa_id'),
            $datos,
            $request->destino(),
            $request->user()->id,
        );

        return response()->json(['data' => $registro], 201);
    }
}
