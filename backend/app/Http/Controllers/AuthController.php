<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = User::where('email', $request->validated('email'))->first();

        if (! $usuario || ! Hash::check($request->validated('password'), $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas.',
            ]);
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json($this->payload($usuario) + ['token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    /**
     * Payload homogéneo de sesión: usuario + roles + array plano de permisos.
     */
    private function payload(User $usuario): array
    {
        return [
            'user' => $usuario->only(['id', 'name', 'email']),
            'roles' => $usuario->getRoleNames()->values()->all(),
            'permissions' => $usuario->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
