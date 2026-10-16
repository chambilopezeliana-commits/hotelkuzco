<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Orden de pisos según el catálogo (por tarifa): Simple, Doble, Triple,
     * Familiar y Suite.
     */
    private array $plan = [
        'Simple' => ['desde' => 101, 'cantidad' => 8],
        'Doble' => ['desde' => 201, 'cantidad' => 8],
        'Triple' => ['desde' => 301, 'cantidad' => 4],
        'Familiar' => ['desde' => 401, 'cantidad' => 6],
        'Suite' => ['desde' => 501, 'cantidad' => 6],
    ];

    public function up(): void
    {
        $this->renumerar($this->plan);
    }

    public function down(): void
    {
        $this->renumerar([
            'Simple' => ['desde' => 101, 'cantidad' => 8],
            'Doble' => ['desde' => 201, 'cantidad' => 8],
            'Familiar' => ['desde' => 301, 'cantidad' => 6],
            'Suite' => ['desde' => 401, 'cantidad' => 6],
            'Triple' => ['desde' => 501, 'cantidad' => 4],
        ]);
    }

    private function renumerar(array $plan): void
    {
        if (DB::table('habitaciones')->count() === 0) {
            return;
        }

        DB::transaction(function () use ($plan) {
            // Paso 1: mover a un rango temporal para evitar colisiones de unicidad.
            $temp = 900000;

            foreach (DB::table('habitaciones')->orderBy('id')->pluck('id') as $id) {
                DB::table('habitaciones')->where('id', $id)->update(['numero' => (string) (++$temp)]);
            }

            // Paso 2: asignar los números definitivos por tipo, respetando el orden de creación.
            foreach ($plan as $nombre => $config) {
                $tipoId = DB::table('tipos_habitacion')->where('nombre', $nombre)->value('id');

                if (! $tipoId) {
                    continue;
                }

                $numero = $config['desde'];

                foreach (DB::table('habitaciones')->where('tipo_habitacion_id', $tipoId)->orderBy('id')->pluck('id') as $id) {
                    DB::table('habitaciones')->where('id', $id)->update(['numero' => (string) $numero]);
                    $numero++;
                }
            }
        });
    }
};
