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
            'Doble' => ['desde' => 201, 'cantidad' => 8, 'descripcion' => 'Cama doble, TV, aire acondicionado'],
            'Triple' => ['desde' => 301, 'cantidad' => 4, 'descripcion' => 'Tres camas, ideal para grupos o familias pequeñas'],
            'Familiar' => ['desde' => 401, 'cantidad' => 6, 'descripcion' => 'Dos camas dobles, espacio para familia'],
            'Suite' => ['desde' => 501, 'cantidad' => 6, 'descripcion' => 'Sala de estar, jacuzzi, vista panorámica'],
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

        // Deja una suite en mantenimiento como ejemplo.
        Habitacion::where('numero', '503')->update(['estado' => 'mantenimiento']);
    }
}
