<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trazabilidad\TrazabilidadRequest;
use App\Services\Trazabilidad\TrazabilidadService;
use Illuminate\Http\JsonResponse;

class TrazabilidadController extends Controller
{
    public function __construct(private readonly TrazabilidadService $trazabilidad) {}

    public function index(TrazabilidadRequest $request): JsonResponse
    {
        return response()->json($this->trazabilidad->consultar($request->validated('q')));
    }
}
