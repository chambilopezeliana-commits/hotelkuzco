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
        return array_merge([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
            'num_huespedes' => 1,
            'estado' => 'confirmada',
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

    public function test_check_in_y_check_out_con_cobro_total(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDays(2)->toDateString()));

        $reserva = Reserva::first();
        $this->assertEquals(100.0, (float) $reserva->total);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertRedirect();

        $this->assertEquals('hospedado', $reserva->fresh()->estado);
        $this->assertEquals('ocupada', $this->habitacion->fresh()->estado);

        // check-out con saldo pendiente debe fallar
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertSessionHas('error');
        $this->assertEquals('hospedado', $reserva->fresh()->estado);

        // cobro que excede el total debe fallar
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 150,
                'metodo' => 'efectivo',
                'concepto' => 'Estadía',
                'fecha' => now()->toDateString(),
            ])
            ->assertSessionHas('error');
        $this->assertEquals(0, Cobro::count());

        // cobro completo
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 100,
                'metodo' => 'tarjeta',
                'concepto' => 'Estadía',
                'fecha' => now()->toDateString(),
            ])
            ->assertRedirect("/reservas/{$reserva->id}/cobros");
        $this->assertEquals(1, Cobro::count());

        // check-out ahora sí
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertRedirect();

        $this->assertEquals('finalizada', $reserva->fresh()->estado);
        $this->assertEquals('disponible', $this->habitacion->fresh()->estado);
    }

    public function test_no_se_puede_check_out_sin_saldar(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDay()->toDateString()));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)->post("/reservas/{$reserva->id}/check-in");

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 25,
                'metodo' => 'efectivo',
                'concepto' => 'Anticipo',
                'fecha' => now()->toDateString(),
            ]);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertSessionHas('error');

        $this->assertEquals('hospedado', $reserva->fresh()->estado);
    }

    public function test_no_se_puede_editar_reserva_hospedada(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDay()->toDateString()));

        $reserva = Reserva::first();
        $this->actingAs($this->usuario)->post("/reservas/{$reserva->id}/check-in");

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva(now()->toDateString(), now()->addDays(3)->toDateString()))
            ->assertSessionHas('error');
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
            'monto' => 50,
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
