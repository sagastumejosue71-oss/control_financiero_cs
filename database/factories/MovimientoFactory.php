<?php

namespace Database\Factories;

use App\Models\Movimiento;
use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimiento>
 */
class MovimientoFactory extends Factory
{
    protected $model = Movimiento::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'cuenta_id' => null,
            'categoria_id' => null,
            'tipo' => 'ingreso',
            'monto' => fake()->randomFloat(2, 1, 500),
            'descripcion' => fake()->sentence(3),
            'fecha' => now()->toDateString(),
            'created_by' => null,
        ];
    }
}
