<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\ReservaNoche;
use App\Models\TipoHabitacion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservaNochesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Habitacion $habitacion;

    private Huesped $huesped;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['role' => 'recepcionista']);
        $this->habitacion = Habitacion::create([
            'numero' => '101',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Simple Noches',
                'tarifa' => 50,
                'capacidad' => 1,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);
        $this->huesped = Huesped::create([
            'nombre' => 'Cliente Noches',
            'documento' => 'DNI-NOC-1',
        ]);
    }

    private function datosReserva(string $entrada, string $salida, array $extra = []): array
    {
        $noches = max(1, (int) round((strtotime($salida) - strtotime($entrada)) / 86400));
        $total = 50 * $noches;

        return array_merge([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
            'num_huespedes' => 1,
            'estado' => 'confirmada',
            'anticipo_monto' => round($total * 0.3, 2),
            'anticipo_metodo' => 'efectivo',
            'anticipo_fecha' => now()->toDateString(),
        ], $extra);
    }

    public function test_crear_reserva_registra_sus_noches_ocupadas(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04'))
            ->assertRedirect('/reservas');

        $reserva = Reserva::first();

        $this->assertSame(3, ReservaNoche::where('reserva_id', $reserva->id)->count());
        $this->assertEqualsCanonicalizing(
            ['2027-01-01', '2027-01-02', '2027-01-03'],
            ReservaNoche::where('reserva_id', $reserva->id)->pluck('noche')->map->toDateString()->all()
        );
        $this->assertTrue(
            ReservaNoche::where('habitacion_id', $this->habitacion->id)->whereDate('noche', '2027-01-02')->exists()
        );
    }

    public function test_cancelar_reserva_libera_sus_noches(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-05'));

        $reserva = Reserva::first();
        $this->assertSame(4, ReservaNoche::count());

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertRedirect();

        $this->assertSame(0, ReservaNoche::count());

        // las noches liberadas vuelven a estar disponibles
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-02', '2027-01-04'))
            ->assertRedirect('/reservas');
    }

    public function test_actualizar_reserva_reemplaza_sus_noches(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04'));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva('2027-02-10', '2027-02-12'))
            ->assertRedirect('/reservas');

        $this->assertEqualsCanonicalizing(
            ['2027-02-10', '2027-02-11'],
            ReservaNoche::where('reserva_id', $reserva->id)->pluck('noche')->map->toDateString()->all()
        );
    }

    public function test_eliminar_reserva_borra_sus_noches(): void
    {
        // Reserva sin cobros: solo así procede la eliminación.
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-01-01',
            'fecha_salida' => '2027-01-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'confirmada',
        ]);

        DB::table('reservas_noches')->insert([
            ['reserva_id' => $reserva->id, 'habitacion_id' => $this->habitacion->id, 'noche' => '2027-01-01'],
            ['reserva_id' => $reserva->id, 'habitacion_id' => $this->habitacion->id, 'noche' => '2027-01-02'],
        ]);

        $this->assertSame(2, ReservaNoche::count());

        $admin = User::factory()->create(['role' => 'administrador']);

        $this->actingAs($admin)
            ->delete("/reservas/{$reserva->id}")
            ->assertRedirect('/reservas');

        $this->assertSame(0, ReservaNoche::count());
        $this->assertSoftDeleted('reservas', ['id' => $reserva->id]);
    }

    public function test_las_noches_guardan_fecha_pura_sin_truncar_timestamps(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-02'));

        $fila = DB::table('reservas_noches')->first();

        $this->assertSame('2027-01-01', $fila->noche);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $fila->created_at);
    }

    public function test_el_indice_unico_impide_registrar_la_misma_noche(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-03'));

        $this->expectException(QueryException::class);

        // Aunque otra escritura llegue a la tabla, el índice único
        // (habitación, noche) rechaza la duplicación.
        DB::table('reservas_noches')->insert([
            'reserva_id' => Reserva::first()->id,
            'habitacion_id' => $this->habitacion->id,
            'noche' => '2027-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
