<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZonasUbicaciones\GuardarUbicacionRequest;
use App\Http\Requests\ZonasUbicaciones\GuardarZonaRequest;
use App\Models\Ubicacion;
use App\Models\Zona;
use App\Services\ZonasUbicaciones\ZonaUbicacionService;
use Illuminate\Http\JsonResponse;

class ZonaUbicacionController extends Controller
{
    public function __construct(private readonly ZonaUbicacionService $zonas) {}

    public function zonasActivas(): JsonResponse
    {
        return response()->json(['data' => $this->zonas->zonasActivas()]);
    }

    public function gestion(): JsonResponse
    {
        return response()->json(['data' => $this->zonas->gestion()]);
    }

    public function crearZona(GuardarZonaRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->zonas->crearZona($request->validated())], 201);
    }

    public function actualizarZona(Zona $zona, GuardarZonaRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->zonas->actualizarZona($zona, $request->validated())]);
    }

    public function activarZona(Zona $zona): JsonResponse
    {
        return response()->json(['data' => $this->zonas->activarZona($zona)]);
    }

    public function inactivarZona(Zona $zona): JsonResponse
    {
        return response()->json(['data' => $this->zonas->inactivarZona($zona)]);
    }

    public function crearUbicacion(Zona $zona, GuardarUbicacionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->zonas->crearUbicacion($zona, $request->validated())], 201);
    }

    public function actualizarUbicacion(Ubicacion $ubicacion, GuardarUbicacionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->zonas->actualizarUbicacion($ubicacion, $request->validated())]);
    }

    public function activarUbicacion(Ubicacion $ubicacion): JsonResponse
    {
        return response()->json(['data' => $this->zonas->activarUbicacion($ubicacion)]);
    }

    public function inactivarUbicacion(Ubicacion $ubicacion): JsonResponse
    {
        return response()->json(['data' => $this->zonas->inactivarUbicacion($ubicacion)]);
    }
}
