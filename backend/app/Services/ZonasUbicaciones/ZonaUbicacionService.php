<?php

namespace App\Services\ZonasUbicaciones;

use App\Models\Ubicacion;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Collection;

/**
 * Maestro de Zonas y Ubicaciones: alimenta los selectores de destino de los
 * traslados (zonas activas, cualquier usuario autenticado) y el ABM de
 * administración (gestionar, solo admin).
 */
final class ZonaUbicacionService
{
    /**
     * Zonas activas con sus ubicaciones activas, ordenadas por sede y nombre.
     *
     * @return Collection<int, Zona>
     */
    public function zonasActivas(): Collection
    {
        return Zona::query()
            ->where('activo', true)
            ->with(['ubicaciones' => fn ($q) => $q->where('activo', true)->orderBy('nombre')])
            ->orderBy('sede')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Vista de gestión (admin): todas las zonas con todas sus ubicaciones,
     * activas e inactivas.
     *
     * @return Collection<int, Zona>
     */
    public function gestion(): Collection
    {
        return Zona::query()
            ->with(['ubicaciones' => fn ($q) => $q->orderBy('nombre')])
            ->orderBy('sede')
            ->orderBy('nombre')
            ->get();
    }

    public function crearZona(array $datos): Zona
    {
        return $this->conUbicaciones(Zona::create($datos));
    }

    public function actualizarZona(Zona $zona, array $datos): Zona
    {
        $zona->update($datos);

        return $this->conUbicaciones($zona->fresh());
    }

    public function activarZona(Zona $zona): Zona
    {
        $zona->update(['activo' => true]);

        return $this->conUbicaciones($zona->fresh());
    }

    public function inactivarZona(Zona $zona): Zona
    {
        $zona->update(['activo' => false]);

        return $this->conUbicaciones($zona->fresh());
    }

    /**
     * Toda respuesta de zona incluye su relación `ubicaciones` (aunque sea un
     * arreglo vacío) para respetar el contrato `Zona` que consume el frontend.
     */
    private function conUbicaciones(Zona $zona): Zona
    {
        return $zona->load('ubicaciones');
    }

    public function crearUbicacion(Zona $zona, array $datos): Ubicacion
    {
        return $zona->ubicaciones()->create([
            'nombre' => $datos['nombre'],
            'activo' => true,
        ]);
    }

    public function actualizarUbicacion(Ubicacion $ubicacion, array $datos): Ubicacion
    {
        $ubicacion->update([
            'zona_id' => $datos['zona_id'],
            'nombre' => $datos['nombre'],
        ]);

        return $ubicacion->fresh();
    }

    public function activarUbicacion(Ubicacion $ubicacion): Ubicacion
    {
        $ubicacion->update(['activo' => true]);

        return $ubicacion->fresh();
    }

    public function inactivarUbicacion(Ubicacion $ubicacion): Ubicacion
    {
        $ubicacion->update(['activo' => false]);

        return $ubicacion->fresh();
    }
}
