<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_generan_32_habitaciones_con_tipo_triple(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(32, Habitacion::count());

        $triple = TipoHabitacion::where('nombre', 'Triple')->firstOrFail();
        $this->assertSame(3, (int) $triple->capacidad);

        $triples = Habitacion::where('tipo_habitacion_id', $triple->id)
            ->orderBy('numero')
            ->pluck('numero')
            ->all();

        $this->assertSame(['301', '302', '303', '304'], $triples);
    }
}
