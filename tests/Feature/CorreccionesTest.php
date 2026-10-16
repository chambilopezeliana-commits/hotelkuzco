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

class CorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Reserva $reserva;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'administrador']);

        $habitacion = Habitacion::create([
            'numero' => '201',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Suite Test',
                'tarifa' => 100,
                'capacidad' => 2,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create([
            'nombre' => 'Ana Test',
            'documento' => 'DNI-200',
        ]);

        $this->reserva = Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-06-01',
            'fecha_salida' => '2027-06-03',
            'num_huespedes' => 2,
            'total' => 200,
            'estado' => 'confirmada',
        ]);
    }

    private function crearCobro(float $monto): Cobro
    {
        return Cobro::create([
            'reserva_id' => $this->reserva->id,
            'user_id' => $this->admin->id,
            'monto' => $monto,
            'metodo' => 'efectivo',
            'concepto' => 'Estadía',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
        ]);
    }

    public function test_no_se_puede_eliminar_un_cobro_anulado(): void
    {
        $cobro = $this->crearCobro(50);

        $this->actingAs($this->admin)
            ->post("/cobros/{$cobro->id}/anular", ['motivo' => 'Error de caja'])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->delete("/cobros/{$cobro->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cobros', ['id' => $cobro->id, 'anulado' => true]);
    }

    public function test_no_se_puede_eliminar_un_contraasiento(): void
    {
        $cobro = $this->crearCobro(50);

        $this->actingAs($this->admin)
            ->post("/cobros/{$cobro->id}/anular", ['motivo' => 'Error de caja']);

        $contraasiento = Cobro::where('tipo', Cobro::TIPO_CONTRAASIENTO)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete("/cobros/{$contraasiento->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cobros', ['id' => $contraasiento->id]);
    }

    public function test_usuario_desactivado_pierde_la_sesion(): void
    {
        $inactivo = User::factory()->create(['role' => 'recepcionista', 'activo' => false]);

        $this->actingAs($inactivo)
            ->get('/panel')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_disponibilidad_rechaza_fechas_invertidas(): void
    {
        $this->actingAs($this->admin)
            ->get('/disponibilidad?entrada=2027-05-10&salida=2027-05-01')
            ->assertSessionHasErrors('salida');
    }

    public function test_login_bloquea_tras_demasiados_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'nadie@hotel.com', 'password' => 'mal']);
        }

        $this->post('/login', ['email' => 'nadie@hotel.com', 'password' => 'mal'])
            ->assertStatus(429);
    }

    public function test_documento_admite_letras_y_numeros(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Maria Lopez',
                'tipo_documento' => 'CI',
                'documento' => '1234567-1A',
            ])
            ->assertRedirect('/huespedes');

        $this->assertDatabaseHas('huespedes', [
            'tipo_documento' => 'CI',
            'documento' => '1234567-1A',
        ]);
    }

    public function test_documento_rechaza_simbolos_invalidos(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Pedro Gomez',
                'tipo_documento' => 'CI',
                'documento' => 'ABC#123',
            ])
            ->assertSessionHasErrors('documento');
    }

    public function test_reporte_con_fecha_invalida_no_lanza_error(): void
    {
        $this->actingAs($this->admin)
            ->get('/reportes?desde=no-es-fecha')
            ->assertRedirect();
    }

    public function test_panel_muestra_alerta_de_limpieza(): void
    {
        Habitacion::create([
            'numero' => '777',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Temporal',
                'tarifa' => 10,
                'capacidad' => 1,
                'activo' => true,
            ])->id,
            'estado' => 'limpieza',
        ]);

        $this->actingAs($this->admin)
            ->get('/panel')
            ->assertOk()
            ->assertSee('Alerta de limpieza');
    }
}
