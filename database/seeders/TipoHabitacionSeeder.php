<?php

namespace Database\Seeders;

use App\Models\TipoHabitacion;
use Illuminate\Database\Seeder;

class TipoHabitacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Simple', 'tarifa' => 150, 'capacidad' => 1, 'descripcion' => 'Habitación individual con cama sencilla y baño privado.'],
            ['nombre' => 'Doble', 'tarifa' => 250, 'capacidad' => 2, 'descripcion' => 'Cama doble o dos camas, TV y aire acondicionado.'],
            ['nombre' => 'Familiar', 'tarifa' => 350, 'capacidad' => 4, 'descripcion' => 'Dos camas dobles, espacio para familia.'],
            ['nombre' => 'Suite', 'tarifa' => 600, 'capacidad' => 3, 'descripcion' => 'Sala de estar, jacuzzi y vista panorámica.'],
        ];

        foreach ($tipos as $tipo) {
            TipoHabitacion::updateOrCreate(
                ['nombre' => $tipo['nombre']],
                $tipo + ['activo' => true],
            );
        }
    }
}
