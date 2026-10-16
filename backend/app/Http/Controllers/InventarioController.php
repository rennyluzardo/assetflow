<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\ListarInventarioRequest;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\JsonResponse;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventario) {}

    public function index(ListarInventarioRequest $request): JsonResponse
    {
        return response()->json($this->inventario->listar($request));
    }
}
