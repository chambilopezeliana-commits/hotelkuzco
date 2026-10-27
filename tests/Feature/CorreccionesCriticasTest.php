<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\ReservaNoche;
use App\Models\TipoHabitacion;
use App\Models\User;
use App\Services\OperacionInvalida;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CorreccionesCriticasTest extends TestCase
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
                'nombre' => 'Simple Crítica',
                'tarifa' => 50,
                'capacidad' => 2,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);
        $this->huesped = Huesped::create([
            'nombre' => 'Cliente Crítico',
            'documento' => 'DNI-CRIT-1',
        ]);
    }

    private function datosReserva(array $extra = []): array
    {
        return array_merge([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-06-01',
            'fecha_salida' => '2027-06-03',
            'num_huespedes' => 1,
            'estado' => 'confirmada',
            'anticipo_monto' => 30,
            'anticipo_metodo' => 'efectivo',
            'anticipo_fecha' => now()->toDateString(),
        ], $extra);
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('api-test', [$usuario->role])->plainTextToken;
    }

    private function api(string $metodo, string $ruta, array $datos = [], ?string $token = null)
    {
        $this->app['auth']->forgetGuards();

        if ($token) {
            $this->withHeader('Authorization', 'Bearer '.$token);
        } else {
            unset($this->defaultHeaders['Authorization']);
        }

        if ($metodo === 'POST' && $ruta === '/api/v1/reservations'
            && isset($datos['check_in'], $datos['check_out'])) {
            $noches = max(1, (int) round((strtotime($datos['check_out']) - strtotime($datos['check_in'])) / 86400));

            $datos += [
                'deposit_amount' => round((float) $this->habitacion->precio * $noches * ((float) config('hotel.anticipo_porcentaje') / 100), 2),
                'deposit_method' => 'efectivo',
            ];
        }

        return $this->json($metodo, $ruta, $datos);
    }

    public function test_no_se_puede_reservar_con_huesped_eliminado(): void
    {
        $this->huesped->delete();

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva())
            ->assertSessionHasErrors('huesped_id');

        $this->assertSame(0, Reserva::count());
    }

    public function test_no_se_puede_reservar_con_habitacion_eliminada(): void
    {
        $this->habitacion->delete();

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva())
            ->assertSessionHasErrors('habitacion_id');

        $this->assertSame(0, Reserva::count());
    }

    public function test_api_rechaza_huesped_y_habitacion_eliminados(): void
    {
        $token = $this->token($this->usuario);

        $this->huesped->delete();
        $this->habitacion->delete();

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-06-01',
            'check_out' => '2027-06-03',
        ], $token)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_id', 'room_id']);

        $this->assertSame(0, Reserva::count());
    }

    public function test_token_de_usuario_desactivado_es_revocado(): void
    {
        $tokenCreado = $this->usuario->createToken('api', [$this->usuario->role]);
        $this->usuario->update(['activo' => false]);

        $this->api('GET', '/api/v1/me', [], $tokenCreado->plainTextToken)
            ->assertStatus(401)
            ->assertJsonPath('message', 'Su cuenta se encuentra desactivada.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenCreado->accessToken->id,
        ]);
    }

    public function test_desactivar_usuario_desde_el_panel_revoca_sus_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $this->token($this->usuario);
        $tokenId = $this->usuario->tokens()->firstOrFail()->id;

        $this->actingAs($admin)
            ->patch("/usuarios/{$this->usuario->id}/toggle")
            ->assertRedirect();

        $this->assertFalse($this->usuario->fresh()->activo);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_pago_con_referencia_de_otra_reserva_es_rechazado(): void
    {
        $token = $this->token($this->usuario);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-06-01',
            'check_out' => '2027-06-03',
        ], $token)->assertStatus(201);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-07-01',
            'check_out' => '2027-07-03',
        ], $token)->assertStatus(201);

        $reservas = Reserva::orderBy('id')->get();

        $this->api('POST', "/api/v1/reservations/{$reservas[0]->id}/payments", [
            'amount' => 10,
            'reference' => 'REF-COMPARTIDA',
        ], $token)->assertStatus(201);

        $this->api('POST', "/api/v1/reservations/{$reservas[1]->id}/payments", [
            'amount' => 10,
            'reference' => 'REF-COMPARTIDA',
        ], $token)
            ->assertStatus(409)
            ->assertJsonPath('message', 'La referencia de pago ya fue utilizada en otra reserva.');

        $this->assertSame(3, Cobro::count());
        $this->assertSame($reservas[0]->id, (int) Cobro::first()->reserva_id);
    }

    public function test_actualizar_no_permite_asignar_habitacion_en_mantenimiento(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva())
            ->assertRedirect('/reservas');

        $reserva = Reserva::firstOrFail();
        $this->habitacion->update(['estado' => 'mantenimiento']);

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva([
                'fecha_entrada' => '2027-06-02',
                'fecha_salida' => '2027-06-04',
            ]))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'mantenimiento'));

        $this->assertSame('2027-06-01', $reserva->fresh()->fecha_entrada->toDateString());
    }

    public function test_crear_reserva_ignora_estado_cancelada_y_registra_confirmada(): void
    {
        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva(['estado' => 'cancelada']))
            ->assertRedirect('/reservas');

        $reserva = Reserva::firstOrFail();

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->estado);
        $this->assertSame(2, ReservaNoche::where('reserva_id', $reserva->id)->count());
        $this->assertSame(1, Cobro::count());
    }

    public function test_disponibilidad_usa_noches_ocupadas_y_excluye_tipos_inactivos(): void
    {
        $token = $this->token($this->usuario);

        $tipoInactivo = TipoHabitacion::create([
            'nombre' => 'Tipo inactivo',
            'tarifa' => 10,
            'capacidad' => 1,
            'activo' => false,
        ]);
        Habitacion::create([
            'numero' => '999',
            'tipo_habitacion_id' => $tipoInactivo->id,
            'estado' => 'disponible',
        ]);

        $disponibles = $this->api(
            'GET',
            '/api/v1/rooms/availability?check_in=2027-08-01&check_out=2027-08-03',
            [],
            $token
        )->assertStatus(200)->json('available');

        $this->assertSame(['101'], collect($disponibles)->pluck('room_number')->all());

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-08-01',
            'check_out' => '2027-08-03',
        ], $token)->assertStatus(201);

        $this->api(
            'GET',
            '/api/v1/rooms/availability?check_in=2027-08-01&check_out=2027-08-03',
            [],
            $token
        )->assertStatus(200)->assertJsonCount(0, 'available');
    }

    public function test_top_habitaciones_no_cuenta_contraasientos_ni_eliminadas(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-09-01',
            'fecha_salida' => '2027-09-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'confirmada',
        ]);

        $pago = Cobro::create([
            'reserva_id' => $reserva->id,
            'user_id' => $this->usuario->id,
            'monto' => 100,
            'metodo' => 'efectivo',
            'concepto' => 'Estadía',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => true,
            'fecha' => now()->toDateString(),
        ]);

        Cobro::create([
            'reserva_id' => $reserva->id,
            'cobro_origen_id' => $pago->id,
            'user_id' => $this->usuario->id,
            'monto' => -100,
            'metodo' => 'efectivo',
            'concepto' => 'Anulación',
            'tipo' => Cobro::TIPO_CONTRAASIENTO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
        ]);

        $top = $this->topHabitaciones();

        $this->assertCount(1, $top);
        $this->assertSame(0, $top->first()['pagos']);
        $this->assertEquals(0.0, $top->first()['ingresos']);

        $this->habitacion->delete();

        $this->assertCount(0, $this->topHabitaciones());
    }

    public function test_check_out_anticipado_libera_las_noches_futuras(): void
    {
        $entrada = now()->toDateString();
        $salida = now()->addDays(3)->toDateString();

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva([
                'fecha_entrada' => $entrada,
                'fecha_salida' => $salida,
                'anticipo_monto' => 45,
            ]))
            ->assertRedirect('/reservas');

        $reserva = Reserva::firstOrFail();

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-in")
            ->assertRedirect();

        Cobro::create([
            'reserva_id' => $reserva->id,
            'user_id' => $this->usuario->id,
            'monto' => 105,
            'metodo' => 'efectivo',
            'concepto' => 'Saldo',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($this->usuario)
            ->post("/reservas/{$reserva->id}/check-out")
            ->assertRedirect();

        $this->assertSame(Reserva::ESTADO_FINALIZADA, $reserva->fresh()->estado);
        $this->assertSame(0, ReservaNoche::where('reserva_id', $reserva->id)->count());

        Habitacion::whereKey($this->habitacion->id)->update(['estado' => 'disponible']);

        $this->actingAs($this->usuario)
            ->post('/reservas', $this->datosReserva([
                'fecha_entrada' => now()->addDay()->toDateString(),
                'fecha_salida' => now()->addDays(2)->toDateString(),
                'anticipo_monto' => 15,
            ]))
            ->assertRedirect('/reservas');

        $this->assertSame(2, Reserva::count());
    }

    public function test_reserva_cancelada_no_puede_reactivarse(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-06-01',
            'fecha_salida' => '2027-06-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => Reserva::ESTADO_CANCELADA,
        ]);

        $this->actingAs($this->usuario)
            ->put("/reservas/{$reserva->id}", $this->datosReserva([
                'estado' => Reserva::ESTADO_CONFIRMADA,
                'anticipo_monto' => 30,
            ]))
            ->assertSessionHasErrors('estado');

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
        $this->assertSame(0, Cobro::count());
    }

    public function test_cancelar_revalida_el_estado_con_un_modelo_obsoleto(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-06-01',
            'fecha_salida' => '2027-06-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);

        $obsoleta = Reserva::findOrFail($reserva->id);

        Reserva::whereKey($reserva->id)->update(['estado' => Reserva::ESTADO_EN_ESTADIA]);

        try {
            $this->app->make(ReservaService::class)->cancelar($obsoleta);
            $this->fail('La cancelación debía rechazarse tras el cambio de estado.');
        } catch (OperacionInvalida) {
        }

        $this->assertSame(Reserva::ESTADO_EN_ESTADIA, $reserva->fresh()->estado);
    }

    public function test_check_in_revalida_el_estado_con_un_modelo_obsoleto(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->addDays(2)->toDateString(),
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);

        $obsoleta = Reserva::findOrFail($reserva->id);

        Reserva::whereKey($reserva->id)->update(['estado' => Reserva::ESTADO_CANCELADA]);

        try {
            $this->app->make(ReservaService::class)->checkIn($obsoleta);
            $this->fail('El check-in debía rechazarse tras la cancelación.');
        } catch (OperacionInvalida) {
        }

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
        $this->assertSame('disponible', $this->habitacion->fresh()->estado);
    }

    public function test_actualizar_revalida_el_estado_con_un_modelo_obsoleto(): void
    {
        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => '2027-06-01',
            'fecha_salida' => '2027-06-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);

        $obsoleta = Reserva::findOrFail($reserva->id);

        Reserva::whereKey($reserva->id)->update(['estado' => Reserva::ESTADO_FINALIZADA]);

        try {
            $this->app->make(ReservaService::class)->actualizar($obsoleta, [
                'habitacion_id' => $this->habitacion->id,
                'huesped_id' => $this->huesped->id,
                'fecha_entrada' => '2027-06-01',
                'fecha_salida' => '2027-06-02',
                'num_huespedes' => 1,
                'estado' => Reserva::ESTADO_CONFIRMADA,
                'notas' => null,
            ]);
            $this->fail('La edición debía rechazarse tras finalizar la reserva.');
        } catch (OperacionInvalida) {
        }

        $this->assertSame(Reserva::ESTADO_FINALIZADA, $reserva->fresh()->estado);
        $this->assertSame('2027-06-03', $reserva->fresh()->fecha_salida->toDateString());
    }

    public function test_check_out_libera_tambien_las_noches_pasadas(): void
    {
        $entrada = now()->subDays(3)->toDateString();
        $salida = now()->subDay()->toDateString();

        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => Reserva::ESTADO_EN_ESTADIA,
        ]);

        $this->habitacion->update(['estado' => 'ocupada']);

        ReservaNoche::create([
            'reserva_id' => $reserva->id,
            'habitacion_id' => $this->habitacion->id,
            'noche' => $entrada,
        ]);
        ReservaNoche::create([
            'reserva_id' => $reserva->id,
            'habitacion_id' => $this->habitacion->id,
            'noche' => now()->subDays(2)->toDateString(),
        ]);

        Cobro::create([
            'reserva_id' => $reserva->id,
            'user_id' => $this->usuario->id,
            'monto' => 100,
            'metodo' => 'efectivo',
            'concepto' => 'Pago total',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
        ]);

        $this->app->make(ReservaService::class)->checkOut($reserva->fresh());

        $this->assertSame(Reserva::ESTADO_FINALIZADA, $reserva->fresh()->estado);
        $this->assertSame(0, ReservaNoche::where('reserva_id', $reserva->id)->count());
        $this->assertSame('limpieza', $this->habitacion->fresh()->estado);
    }

    private function topHabitaciones()
    {
        $controlador = $this->app->make(ReporteController::class);
        $metodo = new ReflectionMethod($controlador, 'topHabitaciones');
        $metodo->setAccessible(true);

        return $metodo->invoke($controlador, Carbon::now()->startOfDay(), Carbon::now()->endOfDay());
    }
}
