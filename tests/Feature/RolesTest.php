<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\TipoHabitacion;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(UserSeeder::class);

        return User::where('email', 'admin@hotel.com')->first();
    }

    private function recepcionista(): User
    {
        $this->seed(UserSeeder::class);

        return User::where('email', 'recepcion@hotel.com')->first();
    }

    public function test_recepcionista_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/usuarios')
            ->assertStatus(403);
    }

    public function test_admin_puede_gestionar_usuarios(): void
    {
        $this->actingAs($this->admin())
            ->get('/usuarios')
            ->assertStatus(200);
    }

    public function test_no_se_puede_degradar_al_ultimo_admin_activo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/usuarios/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'recepcionista',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'administrador',
            'deleted_at' => null,
        ]);
    }

    public function test_se_puede_degradar_un_admin_si_queda_otro_activo(): void
    {
        $admin = $this->admin();
        User::factory()->create(['role' => 'administrador', 'activo' => true]);

        $this->actingAs($admin)
            ->put("/usuarios/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'recepcionista',
            ])
            ->assertRedirect('/usuarios');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'recepcionista',
        ]);
    }

    public function test_recepcionista_no_puede_crear_habitaciones(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/habitaciones/create')
            ->assertStatus(403);
    }

    public function test_recepcionista_si_puede_ver_habitaciones(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/habitaciones')
            ->assertStatus(200);
    }

    public function test_recepcionista_no_ve_cifras_financieras_en_el_panel(): void
    {
        $respuesta = $this->actingAs($this->recepcionista())
            ->get('/panel')
            ->assertOk();

        $respuesta->assertViewMissing('datos.ingresosMes');
        $respuesta->assertViewMissing('datos.cobrosHoy');
        $respuesta->assertDontSee('Cobros de hoy');
        $respuesta->assertDontSee('Ingresos del mes');
    }

    public function test_admin_ve_cifras_financieras_en_el_panel(): void
    {
        $this->actingAs($this->admin())
            ->get('/panel')
            ->assertOk()
            ->assertViewHas('datos', fn (array $datos) => array_key_exists('ingresosMes', $datos)
                && array_key_exists('cobrosHoy', $datos))
            ->assertSee('Cobros de hoy')
            ->assertSee('Ingresos del mes');
    }

    public function test_recepcionista_puede_crear_reservas(): void
    {
        $tipo = TipoHabitacion::create([
            'nombre' => 'Simple Test',
            'tarifa' => 45,
            'capacidad' => 2,
            'activo' => true,
        ]);

        $habitacion = Habitacion::create([
            'numero' => '101',
            'tipo_habitacion_id' => $tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create([
            'nombre' => 'Cliente Test',
            'documento' => 'DNI-0001',
        ]);

        // 3 noches × 45 = 135; anticipo mínimo = 30% = 40,50
        $this->actingAs($this->recepcionista())
            ->post('/reservas', [
                'habitacion_id' => $habitacion->id,
                'huesped_id' => $huesped->id,
                'fecha_entrada' => '2027-03-01',
                'fecha_salida' => '2027-03-04',
                'num_huespedes' => 1,
                'estado' => 'confirmada',
                'anticipo_monto' => 40.5,
                'anticipo_metodo' => 'efectivo',
                'anticipo_fecha' => '2027-03-01',
            ])
            ->assertRedirect('/reservas');

        $this->assertDatabaseHas('reservas', ['habitacion_id' => $habitacion->id, 'estado' => 'confirmada']);
    }
}
