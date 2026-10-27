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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $recepcionista;

    private User $admin;

    private Habitacion $habitacion;

    private Huesped $huesped;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recepcionista = User::factory()->create(['role' => 'recepcionista']);
        $this->admin = User::factory()->create(['role' => 'administrador']);

        $this->habitacion = Habitacion::create([
            'numero' => '101',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Simple API',
                'tarifa' => 50,
                'capacidad' => 2,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);

        $this->huesped = Huesped::create([
            'nombre' => 'Cliente API',
            'documento' => 'DNI-API-1',
        ]);
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('api-test', [$usuario->role])->plainTextToken;
    }

    private function api(string $metodo, string $ruta, array $datos = [], ?string $token = null)
    {
        // En pruebas, el guard de Sanctum reutiliza el usuario de la petición
        // anterior; se reinicia para que cada llamada use su propio token.
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

    // T01: login con credenciales válidas → 200 con token.
    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        $this->json('POST', '/api/v1/login', [
            'email' => $this->recepcionista->email,
            'password' => 'password',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['token', 'token_type', 'expires_in_minutes', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJson(['token_type' => 'Bearer', 'user' => ['role' => 'recepcionista']]);
    }

    // T02: login con credenciales inválidas → 401.
    public function test_login_con_credenciales_invalidas_devuelve_401(): void
    {
        $this->json('POST', '/api/v1/login', [
            'email' => $this->recepcionista->email,
            'password' => 'incorrecta',
        ])->assertStatus(401);
    }

    public function test_logout_revoca_el_token(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/logout', [], $token)->assertStatus(204);

        $this->api('GET', '/api/v1/me', [], $token)->assertStatus(401);
    }

    // T03: documento de huésped duplicado → 409.
    public function test_documento_de_huesped_duplicado_devuelve_409(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/guests', [
            'nombre' => 'Maria Lopez',
            'tipo_documento' => 'CI',
            'documento' => '7778899',
        ], $token)->assertStatus(201);

        $this->api('POST', '/api/v1/guests', [
            'nombre' => 'María López',
            'tipo_documento' => 'CI',
            'documento' => '7778899',
        ], $token)->assertStatus(409);

        $this->assertSame(1, Huesped::where('documento', '7778899')->count());
    }

    // T04: reserva con fechas válidas → 201 y noches registradas.
    public function test_reserva_valida_devuelve_201_y_registra_noches(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-01-01',
            'check_out' => '2027-01-04',
        ], $token)
            ->assertStatus(201)
            ->assertJsonPath('status', 'confirmada')
            ->assertJsonPath('nights', 3)
            ->assertJsonPath('total', fn ($total) => (float) $total === 150.0)
            ->assertJsonPath('paid', fn ($paid) => (float) $paid === 45.0);

        $this->assertDatabaseHas('reservas', [
            'habitacion_id' => $this->habitacion->id,
            'estado' => 'confirmada',
        ]);
        $this->assertSame(3, ReservaNoche::count());
        $this->assertSame(1, Cobro::count());
    }

    public function test_reserva_rechaza_anticipo_menor_al_minimo(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-01-01',
            'check_out' => '2027-01-03',
            'deposit_amount' => 1,
            'deposit_method' => 'efectivo',
        ], $token)
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'anticipo mínimo'));

        $this->assertSame(0, Reserva::count());
        $this->assertSame(0, Cobro::count());
        $this->assertSame(0, ReservaNoche::count());
    }

    public function test_reserva_exige_los_datos_del_anticipo(): void
    {
        $token = $this->token($this->recepcionista);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->json('POST', '/api/v1/reservations', [
                'guest_id' => $this->huesped->id,
                'room_id' => $this->habitacion->id,
                'check_in' => '2027-01-01',
                'check_out' => '2027-01-03',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['deposit_amount', 'deposit_method']);

        $this->assertSame(0, Reserva::count());
    }

    // T05: reserva solapada → 409.
    public function test_reserva_solapada_devuelve_409(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-01-01',
            'check_out' => '2027-01-05',
        ], $token)->assertStatus(201);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-01-03',
            'check_out' => '2027-01-06',
        ], $token)->assertStatus(409);

        $this->assertSame(1, Reserva::count());
    }

    // T12 (persistencia): el índice único (habitación, noche) rechaza la doble ocupación.
    public function test_indice_unico_rechaza_noche_duplicada(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-03-01',
            'check_out' => '2027-03-03',
        ], $token)->assertStatus(201);

        $this->expectException(QueryException::class);

        // Simula una segunda escritura concurrente que superó la validación
        // del servicio: el índice único (habitación, noche) la rechaza.
        ReservaNoche::create([
            'reserva_id' => Reserva::first()->id,
            'habitacion_id' => $this->habitacion->id,
            'noche' => '2027-03-01',
        ]);
    }

    // T06: pago mayor al saldo → 409.
    public function test_pago_mayor_al_saldo_devuelve_409(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-01-01',
            'check_out' => '2027-01-03',
        ], $token)->assertStatus(201);

        $reserva = Reserva::first();

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/payments", [
            'amount' => 500,
        ], $token)->assertStatus(409);

        $this->assertSame(1, Cobro::count());
    }

    // T07: pago duplicado por reintento (misma reference) → 409.
    public function test_pago_duplicado_por_reference_devuelve_409(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ], $token)->assertStatus(201);

        $reserva = Reserva::first();

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/payments", [
            'amount' => 30,
            'reference' => 'REF-001',
        ], $token)->assertStatus(201);

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/payments", [
            'amount' => 30,
            'reference' => 'REF-001',
        ], $token)->assertStatus(409);

        $this->assertSame(2, Cobro::count());
    }

    // T08: check-out con saldo pendiente → 409; excepción admin → 200.
    public function test_check_out_con_saldo_devuelve_409_y_excepcion_admin_200(): void
    {
        $token = $this->token($this->recepcionista);
        $tokenAdmin = $this->token($this->admin);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ], $token)->assertStatus(201);

        $reserva = Reserva::first();

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-in", [], $token)
            ->assertStatus(200)
            ->assertJsonPath('status', 'en_estadia');

        // sin saldo cero y sin excepción → 409
        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-out", [], $token)
            ->assertStatus(409);

        // el recepcionista no puede autorizar la excepción
        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-out", [
            'exception_reason' => 'Pago diferido autorizado',
        ], $token)->assertStatus(409);

        // el administrador sí, registrando el motivo
        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-out", [
            'exception_reason' => 'Pago diferido autorizado',
        ], $tokenAdmin)
            ->assertStatus(200)
            ->assertJsonPath('status', 'finalizada');

        $this->assertStringContainsString(
            'Pago diferido autorizado',
            (string) Auditoria::where('accion', 'check-out')->latest('id')->value('descripcion')
        );
    }

    // T11: toda escritura sensible queda en la bitácora con antes/después.
    public function test_auditoria_registra_la_creacion_de_reserva(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => '2027-02-01',
            'check_out' => '2027-02-03',
        ], $token)->assertStatus(201);

        $auditoria = Auditoria::where('entidad', 'reserva')
            ->where('accion', 'crear')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditoria);
        $this->assertSame($this->recepcionista->id, $auditoria->user_id);
        $this->assertSame('confirmada', $auditoria->datos_nuevos['estado'] ?? null);
    }

    public function test_disponibilidad_rechaza_fechas_invalidas(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('GET', '/api/v1/rooms/availability?check_in=2027-05-10&check_out=2027-05-01', [], $token)
            ->assertStatus(422);

        $this->api('GET', '/api/v1/rooms/availability?check_in=2027-05-01&check_out=2027-05-04', [], $token)
            ->assertStatus(200)
            ->assertJsonPath('nights', 3)
            ->assertJsonPath('available.0.room_number', '101');
    }

    public function test_rutas_api_requieren_token(): void
    {
        $this->json('GET', '/api/v1/dashboard')->assertStatus(401);
        $this->json('POST', '/api/v1/reservations')->assertStatus(401);
    }

    public function test_dashboard_limita_las_cifras_financieras_al_recepcionista(): void
    {
        $tokenRecepcion = $this->token($this->recepcionista);
        $tokenAdmin = $this->token($this->admin);

        $respuesta = $this->api('GET', '/api/v1/dashboard', [], $tokenRecepcion)
            ->assertStatus(200)
            ->assertJsonStructure(['date', 'rooms', 'reservations', 'guests']);

        $this->assertArrayNotHasKey('payments', $respuesta->json());

        $this->api('GET', '/api/v1/dashboard', [], $tokenAdmin)
            ->assertStatus(200)
            ->assertJsonStructure(['payments' => ['today', 'month_net']]);
    }

    public function test_walk_in_completo_por_web_y_api(): void
    {
        // flujo HU14 por API: reserva de hoy, check-in y estado de cuenta
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ], $token)->assertStatus(201);

        $reserva = Reserva::first();

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-in", [], $token)
            ->assertStatus(200);

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/payments", [
            'amount' => 35,
            'reference' => 'REF-FULL',
        ], $token)
            ->assertStatus(201)
            ->assertJsonPath('balance', fn ($balance) => (float) $balance === 0.0);

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/check-out", [], $token)
            ->assertStatus(200)
            ->assertJsonPath('status', 'finalizada');

        $this->assertSame('limpieza', $this->habitacion->fresh()->estado);
    }

    public function test_api_rechaza_nombre_con_numeros_y_telefono_invalido(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/guests', [
            'nombre' => 'Cliente 123',
            'tipo_documento' => 'DNI',
            'documento' => 'API-BAD-1',
        ], $token)->assertStatus(422);

        $this->api('POST', '/api/v1/guests', [
            'nombre' => 'Cliente Valido',
            'tipo_documento' => 'DNI',
            'documento' => 'API-BAD-2',
            'phone' => 'abc',
        ], $token)->assertStatus(422);

        $this->assertDatabaseMissing('huespedes', ['documento' => 'API-BAD-1']);
        $this->assertDatabaseMissing('huespedes', ['documento' => 'API-BAD-2']);
    }

    public function test_api_rechaza_reserva_con_entrada_en_el_pasado(): void
    {
        $token = $this->token($this->recepcionista);

        $this->api('POST', '/api/v1/reservations', [
            'guest_id' => $this->huesped->id,
            'room_id' => $this->habitacion->id,
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'deposit_amount' => 15,
            'deposit_method' => 'efectivo',
        ], $token)->assertStatus(422);

        $this->assertSame(0, Reserva::count());
    }

    public function test_api_rechaza_pagos_sobre_reserva_finalizada(): void
    {
        $token = $this->token($this->recepcionista);

        $reserva = Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $this->huesped->id,
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->addDay()->toDateString(),
            'num_huespedes' => 1,
            'total' => 50,
            'estado' => Reserva::ESTADO_FINALIZADA,
        ]);

        $this->api('POST', "/api/v1/reservations/{$reserva->id}/payments", [
            'amount' => 10,
        ], $token)->assertStatus(409);

        $this->assertSame(0, Cobro::count());
    }
}
