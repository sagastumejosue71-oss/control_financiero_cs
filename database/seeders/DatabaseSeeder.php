<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Expansion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@control.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        // Create default expansions
        $expansiones = [
            ['nombre' => 'ingresos', 'descripcion' => 'Ingresos económicos', 'orden' => 1],
            ['nombre' => 'gastos_fijos', 'descripcion' => 'Gastos fijos mensuales', 'orden' => 2],
            ['nombre' => 'gastos_variables', 'descripcion' => 'Gastos variables', 'orden' => 3],
            ['nombre' => 'deudas', 'descripcion' => 'Deudas y préstamos', 'orden' => 4],
            ['nombre' => 'pagos_realizados', 'descripcion' => 'Pagos realizados', 'orden' => 5],
        ];

        foreach ($expansiones as $exp) {
            Expansion::firstOrCreate(['nombre' => $exp['nombre']], $exp);
        }
    }
}
