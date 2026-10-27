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

class SoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TipoHabitacion $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'administrador']);

        $this->tipo = TipoHabitacion::create([
            'nombre' => 'Simple SD',
            'tarifa' => 50,
            'capacidad' => 1,
            'activo' => true,
        ]);
    }

    public function test_eliminar_huesped_es_baja_logica(): void
    {
        $huesped = Huesped::create(['nombre' => 'Ana Baja', 'documento' => 'DNI-SD-1']);

        $this->actingAs($this->admin)
            ->delete("/huespedes/{$huesped->id}")
            ->assertRedirect('/huespedes');

        $this->assertSoftDeleted('huespedes', ['id' => $huesped->id]);
    }

    public function test_huesped_eliminado_libera_el_documento_unico(): void
    {
        $huesped = Huesped::create(['nombre' => 'Ana Baja', 'documento' => 'DNI-SD-2']);

        $this->actingAs($this->admin)->delete("/huespedes/{$huesped->id}");

        // El documento original queda libre para un nuevo registro.
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Ana Nueva',
                'tipo_documento' => 'DNI',
                'documento' => 'DNI-SD-2',
            ])
            ->assertRedirect('/huespedes');

        $this->assertDatabaseHas('huespedes', [
            'documento' => 'DNI-SD-2',
            'nombre' => 'Ana Nueva',
            'deleted_at' => null,
        ]);
    }

    public function test_usuario_eliminado_libera_el_email_y_revoca_credenciales(): void
    {
        $usuario = User::factory()->create(['role' => 'recepcionista']);
        $emailOriginal = $usuario->email;

        $this->actingAs($this->admin)
            ->delete("/usuarios/{$usuario->id}")
            ->assertRedirect('/usuarios');

        $this->assertSoftDeleted('users', ['id' => $usuario->id]);

        // Credenciales revocadas en la baja lógica.
        $eliminado = User::withTrashed()->findOrFail($usuario->id);
        $this->assertNotEquals($emailOriginal, $eliminado->email);
        $this->assertFalse($eliminado->activo);

        // El email original queda libre para un nuevo usuario.
        $this->actingAs($this->admin)
            ->post('/usuarios', [
                'name' => 'Nuevo Admin',
                'email' => $emailOriginal,
                'role' => 'recepcionista',
                'password' => 'secreta12345',
            ])
            ->assertRedirect('/usuarios');

        $this->assertDatabaseHas('users', ['email' => $emailOriginal, 'deleted_at' => null]);
    }

    public function test_habitacion_eliminada_libera_el_numero_y_desaparece_del_listado(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '801',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $this->actingAs($this->admin)
            ->delete("/habitaciones/{$habitacion->id}")
            ->assertRedirect('/habitaciones');

        $this->assertSoftDeleted('habitaciones', ['id' => $habitacion->id]);

        // No aparece en el listado.
        $this->actingAs($this->admin)
            ->get('/habitaciones')
            ->assertOk()
            ->assertDontSee('>801<', false);

        // El número queda libre para una nueva habitación.
        $this->actingAs($this->admin)
            ->post('/habitaciones', [
                'numero' => '801',
                'tipo_habitacion_id' => $this->tipo->id,
                'estado' => 'disponible',
            ])
            ->assertRedirect('/habitaciones');

        $this->assertDatabaseHas('habitaciones', ['numero' => '801', 'deleted_at' => null]);
    }

    public function test_tipo_habitacion_eliminado_libera_el_nombre(): void
    {
        $this->actingAs($this->admin)
            ->delete("/tipos-habitacion/{$this->tipo->id}")
            ->assertRedirect('/tipos-habitacion');

        $this->assertSoftDeleted('tipos_habitacion', ['id' => $this->tipo->id]);

        $this->actingAs($this->admin)
            ->post('/tipos-habitacion', [
                'nombre' => 'Simple SD',
                'tarifa' => 60,
                'capacidad' => 1,
            ])
            ->assertRedirect('/tipos-habitacion');

        $this->assertDatabaseHas('tipos_habitacion', ['nombre' => 'Simple SD', 'deleted_at' => null]);
    }

    public function test_reserva_con_cobros_no_se_puede_eliminar(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '802',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create(['nombre' => 'Cliente SD', 'documento' => 'DNI-SD-3']);

        $reserva = Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-08-01',
            'fecha_salida' => '2027-08-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'confirmada',
        ]);

        Cobro::create([
            'reserva_id' => $reserva->id,
            'user_id' => $this->admin->id,
            'monto' => 30,
            'metodo' => 'efectivo',
            'concepto' => 'Anticipo',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete("/reservas/{$reserva->id}")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'cobros registrados'));

        $this->assertDatabaseHas('reservas', ['id' => $reserva->id, 'deleted_at' => null]);
    }

    public function test_reserva_sin_cobros_se_elimina_logicamente(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '803',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create(['nombre' => 'Cliente SD', 'documento' => 'DNI-SD-4']);

        $reserva = Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-09-01',
            'fecha_salida' => '2027-09-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'confirmada',
        ]);

        $this->actingAs($this->admin)
            ->delete("/reservas/{$reserva->id}")
            ->assertRedirect('/reservas');

        $this->assertSoftDeleted('reservas', ['id' => $reserva->id]);
    }

    public function test_no_se_elimina_habitacion_con_reservas_historicas(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '804',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create(['nombre' => 'Cliente Historial', 'documento' => 'DNI-SD-5']);

        Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-10-01',
            'fecha_salida' => '2027-10-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'finalizada',
        ]);

        $this->actingAs($this->admin)
            ->delete("/habitaciones/{$habitacion->id}")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'historial'));

        $this->assertDatabaseHas('habitaciones', [
            'id' => $habitacion->id,
            'numero' => '804',
            'deleted_at' => null,
        ]);
    }

    public function test_no_se_elimina_huesped_con_reservas_historicas(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '805',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create(['nombre' => 'Cliente Historial', 'documento' => 'DNI-SD-6']);

        Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-10-01',
            'fecha_salida' => '2027-10-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'cancelada',
        ]);

        $this->actingAs($this->admin)
            ->delete("/huespedes/{$huesped->id}")
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'historial'));

        $this->assertDatabaseHas('huespedes', [
            'id' => $huesped->id,
            'documento' => 'DNI-SD-6',
            'deleted_at' => null,
        ]);
    }

    public function test_no_se_elimina_tipo_de_habitacion_con_habitaciones_asociadas(): void
    {
        Habitacion::create([
            'numero' => '806',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $this->actingAs($this->admin)
            ->delete("/tipos-habitacion/{$this->tipo->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tipos_habitacion', [
            'id' => $this->tipo->id,
            'deleted_at' => null,
        ]);
    }

    public function test_la_baja_de_un_huesped_no_colisiona_con_un_documento_marcador_existente(): void
    {
        $objetivo = Huesped::create(['nombre' => 'Objetivo Baja', 'documento' => 'DNI-COL-1']);
        Huesped::create(['nombre' => 'Documento Ocupado', 'documento' => 'ELIM-'.$objetivo->id]);

        $this->actingAs($this->admin)
            ->delete("/huespedes/{$objetivo->id}")
            ->assertRedirect('/huespedes');

        $eliminado = Huesped::withTrashed()->findOrFail($objetivo->id);

        $this->assertSoftDeleted('huespedes', ['id' => $objetivo->id]);
        $this->assertNotSame('ELIM-'.$objetivo->id, $eliminado->documento);
    }

    public function test_la_baja_de_un_usuario_no_colisiona_con_un_email_marcador_existente(): void
    {
        $objetivo = User::factory()->create(['role' => 'recepcionista']);
        $marcador = 'user-'.$objetivo->id.'@eliminado.invalid';
        User::factory()->create(['email' => $marcador]);

        $this->actingAs($this->admin)
            ->delete("/usuarios/{$objetivo->id}")
            ->assertRedirect('/usuarios');

        $eliminado = User::withTrashed()->findOrFail($objetivo->id);

        $this->assertSoftDeleted('users', ['id' => $objetivo->id]);
        $this->assertNotSame($marcador, $eliminado->email);
    }

    public function test_la_baja_de_una_habitacion_no_colisiona_con_un_numero_marcador_existente(): void
    {
        $objetivo = Habitacion::create([
            'numero' => '850',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        Habitacion::create([
            'numero' => '0'.$objetivo->id,
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $this->actingAs($this->admin)
            ->delete("/habitaciones/{$objetivo->id}")
            ->assertRedirect('/habitaciones');

        $eliminada = Habitacion::withTrashed()->findOrFail($objetivo->id);

        $this->assertSoftDeleted('habitaciones', ['id' => $objetivo->id]);
        $this->assertNotSame('0'.$objetivo->id, $eliminada->numero);
    }

    public function test_la_baja_de_un_tipo_no_colisiona_con_un_nombre_marcador_existente(): void
    {
        $objetivo = TipoHabitacion::create([
            'nombre' => 'Doble Colision',
            'tarifa' => 80,
            'capacidad' => 2,
            'activo' => true,
        ]);

        TipoHabitacion::create([
            'nombre' => 'ELIMINADO #'.$objetivo->id,
            'tarifa' => 80,
            'capacidad' => 2,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete("/tipos-habitacion/{$objetivo->id}")
            ->assertRedirect('/tipos-habitacion');

        $eliminado = TipoHabitacion::withTrashed()->findOrFail($objetivo->id);

        $this->assertSoftDeleted('tipos_habitacion', ['id' => $objetivo->id]);
        $this->assertNotSame('ELIMINADO #'.$objetivo->id, $eliminado->nombre);
    }

    public function test_el_historial_sigue_visible_aunque_los_padres_esten_dados_de_baja(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '807',
            'tipo_habitacion_id' => $this->tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create(['nombre' => 'Cliente Historial', 'documento' => 'DNI-SD-7']);

        $reserva = Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => '2027-11-01',
            'fecha_salida' => '2027-11-03',
            'num_huespedes' => 1,
            'total' => 100,
            'estado' => 'confirmada',
        ]);

        $habitacion->delete();
        $huesped->delete();

        $this->actingAs($this->admin)
            ->get('/reservas')
            ->assertOk()
            ->assertSee('807')
            ->assertSee('Cliente Historial');

        $this->actingAs($this->admin)
            ->get("/reservas/{$reserva->id}/cobros")
            ->assertOk();

        $this->actingAs($this->admin)
            ->get("/reservas/{$reserva->id}/comprobante")
            ->assertOk();
    }
}
