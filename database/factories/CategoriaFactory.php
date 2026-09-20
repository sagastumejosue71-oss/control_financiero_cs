<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->unique()->word(),
            'tipo' => 'gasto',
        ];
    }
}
