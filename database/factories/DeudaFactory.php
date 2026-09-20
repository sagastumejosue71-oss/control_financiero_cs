<?php

namespace Database\Factories;

use App\Models\Deuda;
use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deuda>
 */
class DeudaFactory extends Factory
{
    protected $model = Deuda::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'acreedor' => fake()->company(),
            'monto_total' => 1000,
            'saldo_pendiente' => 1000,
            'tasa_interes' => null,
            'fecha_inicio' => now()->toDateString(),
            'fecha_limite' => null,
            'saldada' => false,
        ];
    }
}
