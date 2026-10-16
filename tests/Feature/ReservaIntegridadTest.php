<?php

namespace Tests\Feature;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaIntegridadTest extends TestCase
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
                'nombre' => 'Simple Test',
                'tarifa' => 50,
                'capacidad' => 1,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);
        $this->huesped = Huesped::create([
            'nombre' => 'Carlos Test',
            'documento' => 'DNI-100',
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

    public function test_se_crea_reserva_con_total_calculado(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04'))
            ->assertRedirect('/reservas');

        $reserva = Reserva::first();
        $this->assertNotNull($reserva);
        $this->assertEquals(150.0, (float) $reserva->total);
    }

    public function test_se_rechaza_reserva_solapada(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-05'));

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-03', '2027-01-06', ['huesped_id' => $this->huesped->id]))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'ya está reservada'));

        $this->assertEquals(1, Reserva::count());
    }

    public function test_no_se_puede_reservar_habitacion_ocupada(): void
    {
        Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->subDay()->toDateString(),
            'fecha_salida' => now()->addDays(3)->toDateString(),
            'num_huespedes' => 1,
            'estado' => 'hospedado',
            'total' => 200,
        ]);

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDay()->toDateString()))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'está ocupada por'));

        $this->assertEquals(1, Reserva::count());
    }

    public function test_no_se_puede_reservar_habitacion_en_mantenimiento(): void
    {
        $this->habitacion->update(['estado' => 'mantenimiento']);

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-02-01', '2027-02-03'))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'mantenimiento'));

        $this->assertEquals(0, Reserva::count());
    }

    public function test_no_se_puede_reservar_habitacion_pendiente_de_limpieza(): void
    {
        $this->habitacion->update(['estado' => 'limpieza']);

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-02-01', '2027-02-03'))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'limpieza'));

        $this->assertEquals(0, Reserva::count());
    }

    public function test_marcar_limpia_deja_la_habitacion_disponible(): void
    {
        $this->habitacion->update(['estado' => 'limpieza']);

        $this->actingAs($this->usuario)
            ->post("/habitaciones/{$this->habitacion->id}/limpiar")
            ->assertRedirect();

        $this->assertEquals('disponible', $this->habitacion->fresh()->estado);
    }

    public function test_cancelar_reserva_libera_las_fechas(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-05'));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertRedirect();

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-02', '2027-01-04'))
            ->assertRedirect('/reservas');

        $this->assertEquals(2, Reserva::count());
    }

    public function test_anticipo_minimo_se_exige_al_crear(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04', ['anticipo_monto' => 10]))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'anticipo mínimo'));

        $this->assertEquals(0, Reserva::count());
        $this->assertEquals(0, Cobro::count());
    }

    public function test_check_in_exige_pago_total(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDays(2)->toDateString()));

        $reserva = Reserva::first();
        $this->assertEquals(100.0, (float) $reserva->total);
        $this->assertEquals(30.0, (float) Cobro::sum('monto'));

        // el check-in exige el pago total: con saldo pendiente debe fallar
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'saldo pendiente'));
        $this->assertEquals('confirmada', $reserva->fresh()->estado);
        $this->assertEquals('disponible', $this->habitacion->fresh()->estado);

        // un cobro que excede el saldo pendiente debe fallar
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 150,
                'metodo' => 'efectivo',
                'concepto' => 'Estadía',
                'fecha' => now()->toDateString(),
            ])
            ->assertSessionHas('error');
        $this->assertEquals(1, Cobro::count());

        // cobro del saldo restante
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 70,
                'metodo' => 'qr',
                'concepto' => 'Estadía',
                'fecha' => now()->toDateString(),
            ])
            ->assertRedirect("/reservas/{$reserva->id}/cobros");
        $this->assertEquals(2, Cobro::count());

        // ahora sí el check-in procede
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertRedirect();
        $this->assertEquals('hospedado', $reserva->fresh()->estado);
        $this->assertEquals('ocupada', $this->habitacion->fresh()->estado);

        // el check-out ya no exige saldo
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertRedirect();
        $this->assertEquals('finalizada', $reserva->fresh()->estado);
        $this->assertEquals('limpieza', $this->habitacion->fresh()->estado);

        // marcar como limpia la deja disponible de nuevo
        $this->actingAs($this->usuario)
            ->post("/habitaciones/{$this->habitacion->id}/limpiar")
            ->assertRedirect();
        $this->assertEquals('disponible', $this->habitacion->fresh()->estado);
    }

    public function test_check_out_no_exige_saldo(): void
    {
        Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->subDay()->toDateString(),
            'fecha_salida' => now()->addDay()->toDateString(),
            'num_huespedes' => 1,
            'estado' => 'hospedado',
            'total' => 100,
        ]);
        $this->habitacion->update(['estado' => 'ocupada']);
        $reserva = Reserva::first();

        // sin cobros registrados el check-out debe proceder
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertRedirect();

        $this->assertEquals('finalizada', $reserva->fresh()->estado);
        $this->assertEquals('limpieza', $this->habitacion->fresh()->estado);
    }

    public function test_metodo_tarjeta_ya_no_es_valido(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDay()->toDateString()));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 35,
                'metodo' => 'tarjeta',
                'concepto' => 'Estadía',
                'fecha' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('metodo');
    }

    public function test_no_se_puede_editar_reserva_hospedada(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->subDay()->toDateString(),
            'fecha_salida' => now()->addDay()->toDateString(),
            'num_huespedes' => 1,
            'estado' => 'hospedado',
            'total' => 100,
        ]);
        $this->habitacion->update(['estado' => 'ocupada']);

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva(now()->toDateString(), now()->addDays(3)->toDateString()))
            ->assertSessionHas('error');

        $this->assertEquals('hospedado', $reserva->fresh()->estado);
    }

    public function test_no_se_puede_eliminar_habitacion_con_reservas_activas(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-05'));

        $admin = User::factory()->create(['role' => 'administrador']);

        $this->actingAs($admin)
            ->delete("/habitaciones/{$this->habitacion->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('habitaciones', ['id' => $this->habitacion->id]);
    }

    public function test_no_se_puede_eliminar_usuario_con_cobros(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDay()->toDateString()));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)->post("/reservas/{$reserva->id}/cobros", [
            'monto' => 35,
            'metodo' => 'efectivo',
            'concepto' => 'Estadía',
            'fecha' => now()->toDateString(),
        ]);

        $admin = User::factory()->create(['role' => 'administrador']);

        $this->actingAs($admin)
            ->delete("/usuarios/{$this->usuario->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->usuario->id]);
    }
}
