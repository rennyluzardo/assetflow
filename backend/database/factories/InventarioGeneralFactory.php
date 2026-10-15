<?php

namespace Database\Factories;

use App\Enums\UbicacionDestinoEnum;
use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarioGeneralFactory extends Factory
{
    protected $model = InventarioGeneral::class;

    public function definition(): array
    {
        return [
            'placa_id' => 'AF-'.strtoupper($this->faker->unique()->bothify('######')),
            'serial' => 'SN-'.strtoupper($this->faker->unique()->bothify('######')),
            'marca' => $this->faker->randomElement(['Dell', 'HP', 'Lenovo']),
            'referencia' => 'OptiPlex 7050',
            'descripcion' => 'CPU',
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'num_traslado' => 'TR-'.strtoupper($this->faker->bothify('####')),
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => User::factory(),
        ];
    }
}
