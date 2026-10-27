<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\ReservaNoche;
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

    public function test_no_se_puede_crear_reserva_con_entrada_en_el_pasado(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(
                now()->subDay()->toDateString(),
                now()->addDay()->toDateString()
            ))
            ->assertSessionHasErrors('fecha_entrada');

        $this->assertEquals(0, Reserva::count());
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
            'estado' => 'en_estadia',
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

    public function test_check_in_no_exige_pago_total_y_check_out_sí_saldo(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDays(2)->toDateString()));

        $reserva = Reserva::first();
        $this->assertEquals(100.0, (float) $reserva->total);
        $this->assertEquals(30.0, (float) Cobro::sum('monto'));

        // HU14: el check-in de una reserva confirmada no exige saldo cero
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertRedirect();
        $this->assertEquals('en_estadia', $reserva->fresh()->estado);
        $this->assertEquals('ocupada', $this->habitacion->fresh()->estado);

        // HU15: el check-out sí exige cuenta saldada
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'saldo pendiente'));
        $this->assertEquals('en_estadia', $reserva->fresh()->estado);

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

        // con saldo cero el check-out procede
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

    public function test_check_out_con_saldo_requiere_excepcion_del_administrador(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(now()->toDateString(), now()->addDays(2)->toDateString()));

        $reserva = Reserva::first();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertRedirect();

        // el recepcionista no puede autorizar la excepción
        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out", ['motivo_excepcion' => 'Error de caja'])
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'administrador'));
        $this->assertEquals('en_estadia', $reserva->fresh()->estado);

        // el administrador tampoco puede sin registrar motivo
        $admin = User::factory()->create(['role' => 'administrador']);

        $this->actingAs($admin)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'saldo pendiente'));
        $this->assertEquals('en_estadia', $reserva->fresh()->estado);

        // con motivo registrado el check-out excepcional procede y queda en auditoría
        $this->actingAs($admin)
            ->post("/reservas/{$reserva->id}/check-out", ['motivo_excepcion' => 'Huésped con pago pendiente autorizado'])
            ->assertRedirect();
        $this->assertEquals('finalizada', $reserva->fresh()->estado);

        $this->assertDatabaseHas('auditorias', [
            'entidad' => 'reserva',
            'entidad_id' => $reserva->id,
            'accion' => 'check-out',
        ]);

        $this->assertStringContainsString(
            'Huésped con pago pendiente autorizado',
            (string) Auditoria::where('accion', 'check-out')->latest('id')->value('descripcion')
        );
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

    public function test_no_se_puede_editar_reserva_en_estadia(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->subDay()->toDateString(),
            'fecha_salida' => now()->addDay()->toDateString(),
            'num_huespedes' => 1,
            'estado' => 'en_estadia',
            'total' => 100,
        ]);
        $this->habitacion->update(['estado' => 'ocupada']);

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva(now()->toDateString(), now()->addDays(3)->toDateString()))
            ->assertSessionHas('error');

        $this->assertEquals('en_estadia', $reserva->fresh()->estado);
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

    public function test_check_in_rechaza_reserva_vencida(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->subDays(3)->toDateString(),
            'fecha_salida' => now()->subDays(2)->toDateString(),
            'num_huespedes' => 1,
            'estado' => Reserva::ESTADO_CONFIRMADA,
            'total' => 100,
        ]);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'venció'));

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
        $this->assertSame('disponible', $this->habitacion->fresh()->estado);
    }

    public function test_solo_se_pueden_cancelar_reservas_confirmadas(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-01-01',
            'fecha_salida' => '2027-01-03',
            'num_huespedes' => 1,
            'estado' => Reserva::ESTADO_FINALIZADA,
            'total' => 100,
        ]);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'Solo se pueden cancelar reservas confirmadas.'));

        $this->assertSame(Reserva::ESTADO_FINALIZADA, $reserva->fresh()->estado);

        $reserva->update(['estado' => Reserva::ESTADO_CANCELADA]);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'Solo se pueden cancelar reservas confirmadas.'));

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
    }

    public function test_no_se_puede_reservar_habitacion_con_tipo_inactivo(): void
    {
        $tipoInactivo = TipoHabitacion::create([
            'nombre' => 'Tipo Inactivo Test',
            'tarifa' => 50,
            'capacidad' => 1,
            'activo' => false,
        ]);

        $habitacion = Habitacion::create([
            'numero' => '999',
            'tipo_habitacion_id' => $tipoInactivo->id,
            'estado' => 'disponible',
        ]);

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-03-01', '2027-03-03', ['habitacion_id' => $habitacion->id]))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'inactivo'));

        $this->assertSame(0, Reserva::count());
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

    public function test_cobro_web_es_idempotente_con_la_misma_clave(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04'));

        $reserva = Reserva::firstOrFail();
        $datos = [
            'monto' => 20,
            'metodo' => 'efectivo',
            'concepto' => 'Pago parcial',
            'fecha' => now()->toDateString(),
            'clave_idempotencia' => 'IDEM-WEB-1',
        ];

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", $datos)
            ->assertRedirect();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", $datos)
            ->assertRedirect();

        $this->assertSame(2, Cobro::count());
        $this->assertSame(1, Cobro::where('clave_idempotencia', 'IDEM-WEB-1')->count());
    }

    public function test_no_se_puede_reducir_el_total_por_debajo_de_lo_cobrado(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva('2027-01-01', '2027-01-04'));

        $reserva = Reserva::firstOrFail();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/cobros", [
                'monto' => 100,
                'metodo' => 'efectivo',
                'concepto' => 'Pago parcial',
                'fecha' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva('2027-01-01', '2027-01-02', [
                'estado' => 'confirmada',
            ]))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'cobrado'));

        $this->assertEquals(150.0, (float) $reserva->fresh()->total);
        $this->assertSame(3, ReservaNoche::where('reserva_id', $reserva->id)->count());
    }
}
