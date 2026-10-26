<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portatiles\CerrarPortatilRequest;
use App\Http\Requests\Portatiles\FotoPortatilRequest;
use App\Http\Requests\Portatiles\ListarPortatilesRequest;
use App\Http\Requests\Portatiles\RegistrarPortatilRequest;
use App\Models\GarantiaPortatil;
use App\Services\Portatiles\PortatilService;
use App\Services\Portatiles\PortatilStateMachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PortatilesController extends Controller
{
    public function __construct(
        private readonly PortatilService $portatiles,
        private readonly PortatilStateMachineService $stateMachine,
    ) {}

    public function index(ListarPortatilesRequest $request): JsonResponse
    {
        return response()->json($this->portatiles->listar($request));
    }

    public function buscar(string $placaId): JsonResponse
    {
        return response()->json(['data' => $this->portatiles->buscar($placaId)]);
    }

    public function store(RegistrarPortatilRequest $request): JsonResponse
    {
        $clasificacion = $request->clasificacion();
        $destino = $request->destino();

        $estado = $this->stateMachine->resolverEstado($clasificacion, $request->validated());
        $this->stateMachine->validarDestino($clasificacion, $request->validated(), $destino);

        $registro = $this->portatiles->registrar(
            $request->validated(),
            $estado,
            $destino,
            $request->user()->id,
        );

        return response()->json(['data' => $registro], 201);
    }

    public function cerrar(int $id, CerrarPortatilRequest $request): JsonResponse
    {
        $registro = GarantiaPortatil::findOrFail($id);
        $destino = $request->destino();

        $this->stateMachine->validarCierre($registro->estado, $destino);

        $registro = $this->portatiles->cerrar(
            $registro,
            $destino,
            $request->validated('reparado_por'),
            $request->user()->id,
            $request->validated('foto_evidencia'),
        );

        return response()->json(['data' => $registro]);
    }

    public function foto(int $id, FotoPortatilRequest $request): JsonResponse
    {
        $registro = GarantiaPortatil::findOrFail($id);

        $foto = $request->file('foto_evidencia');
        $ruta = $foto->storeAs('portatiles', (string) Str::uuid().'.'.$foto->extension(), 'public');

        $registro = $this->portatiles->adjuntarFoto($registro, $ruta);

        return response()->json(['data' => $registro]);
    }
}
