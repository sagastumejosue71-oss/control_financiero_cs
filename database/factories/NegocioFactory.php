<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    protected $model = Negocio::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake()->unique()->company(),
            'moneda' => 'GTQ',
            'activo' => true,
        ];
    }
}
