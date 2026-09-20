<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuenta>
 */
class CuentaFactory extends Factory
{
    protected $model = Cuenta::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->unique()->words(2, true),
            'tipo' => 'efectivo',
            'saldo_inicial' => 0,
            'activa' => true,
        ];
    }
}
