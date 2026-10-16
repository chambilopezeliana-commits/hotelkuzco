<?php

namespace Database\Seeders;

use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Illuminate\Database\Seeder;

class HabitacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = TipoHabitacion::pluck('id', 'nombre');

        $plan = [
            'Simple' => ['desde' => 101, 'cantidad' => 8, 'descripcion' => 'Individual, cama sencilla, baño privado'],
            'Doble' => ['desde' => 201, 'cantidad' => 10, 'descripcion' => 'Cama doble, TV, aire acondicionado'],
            'Familiar' => ['desde' => 301, 'cantidad' => 8, 'descripcion' => 'Dos camas dobles, espacio para familia'],
            'Suite' => ['desde' => 401, 'cantidad' => 6, 'descripcion' => 'Sala de estar, jacuzzi, vista panorámica'],
        ];

        foreach ($plan as $tipo => $datos) {
            $tipoId = $tipos[$tipo] ?? null;

            for ($i = 0; $i < $datos['cantidad']; $i++) {
                $numero = (string) ($datos['desde'] + $i);

                Habitacion::updateOrCreate(['numero' => $numero], [
                    'numero' => $numero,
                    'tipo_habitacion_id' => $tipoId,
                    'estado' => 'disponible',
                    'descripcion' => $datos['descripcion'],
                ]);
            }
        }

        Habitacion::where('numero', '403')->update(['estado' => 'mantenimiento']);
    }
}
