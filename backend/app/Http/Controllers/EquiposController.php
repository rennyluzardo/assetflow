<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipos\RegistrarEquipoRequest;
use App\Http\Requests\Equipos\TransicionarEquipoRequest;
use App\Services\Equipos\CpuStateMachineService;
use App\Services\Equipos\EquipoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EquiposController extends Controller
{
    public function __construct(
        private readonly CpuStateMachineService $stateMachine,
        private readonly EquipoService $equipos,
    ) {}

    public function buscar(string $placaId): JsonResponse
    {
        return response()->json(['data' => $this->equipos->buscar($placaId)]);
    }

    public function store(RegistrarEquipoRequest $request): JsonResponse
    {
        $this->equipos->asegurarIntervenible($request->validated('placa_id'), $request->user());

        $tipo = $request->tipoGestion();
        $destino = $request->destino();
        $respuestas = $request->respuestas();
        $datos = $request->validated();

        if ($foto = $request->file('foto_equipo')) {
            $datos['foto_equipo'] = $foto->storeAs(
                'equipos',
                (string) Str::uuid().'.'.$foto->extension(),
                'public',
            );
        } else {
            unset($datos['foto_equipo']);
        }

        $estado = $this->stateMachine->resolverEstado($tipo, $datos);
        $this->stateMachine->validarDestino($tipo, $datos, $destino);

        $auto = $this->stateMachine->camposAutomaticos($tipo, $respuestas);

        $equipo = $this->equipos->registrar(
            array_merge($datos, $auto),
            $estado,
            $destino,
            $request->user()->id,
        );

        return response()->json(['data' => $equipo], 201);
    }

    public function transicionar(TransicionarEquipoRequest $request): JsonResponse
    {
        $equipo = $this->equipos->flujoAbierto($request->validated('placa_id'));

        $destino = $request->destino();
        $respuestas = $request->respuestas();

        $estado = $this->stateMachine->resolverCierre($equipo->estado, $respuestas);
        $this->stateMachine->validarDestinoCierre($equipo->estado, $destino);

        $equipo = $this->equipos->transicionar($equipo, $estado, $destino, $request->user()->id, $respuestas);

        return response()->json(['data' => $equipo]);
    }
}
