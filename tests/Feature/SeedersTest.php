<?php

namespace Tests\Feature;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\ReservaNoche;
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

    public function test_seeders_no_duplican_ni_reactivan_datos_operativos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $reservas = Reserva::count();
        $cobros = Cobro::count();
        $noches = ReservaNoche::count();

        $this->assertSame(0, ReservaNoche::whereHas(
            'reserva',
            fn ($query) => $query->where('estado', Reserva::ESTADO_FINALIZADA)
        )->count());
        $this->assertSame(0, ReservaNoche::whereHas(
            'reserva',
            fn ($query) => $query->where('estado', Reserva::ESTADO_CANCELADA)
        )->count());

        $habitacion = Habitacion::where('numero', '105')->firstOrFail();
        $habitacion->update(['estado' => 'mantenimiento']);

        $tipo = TipoHabitacion::where('nombre', 'Simple')->firstOrFail();
        $tipo->update(['activo' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($reservas, Reserva::count());
        $this->assertSame($cobros, Cobro::count());
        $this->assertSame($noches, ReservaNoche::count());
        $this->assertSame('mantenimiento', $habitacion->fresh()->estado);
        $this->assertFalse((bool) $tipo->fresh()->activo);
    }
}
