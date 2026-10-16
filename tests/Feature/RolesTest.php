<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\Huesped;
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

    public function test_recepcionista_puede_crear_reservas(): void
    {
        $habitacion = Habitacion::create([
            'numero' => '101',
            'tipo' => 'simple',
            'capacidad' => 1,
            'precio' => 45,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create([
            'nombre' => 'Cliente Test',
            'documento' => 'DNI-0001',
        ]);

        $this->actingAs($this->recepcionista())
            ->post('/reservas', [
                'habitacion_id' => $habitacion->id,
                'huesped_id' => $huesped->id,
                'fecha_entrada' => '2027-03-01',
                'fecha_salida' => '2027-03-04',
                'num_huespedes' => 1,
                'estado' => 'confirmada',
            ])
            ->assertRedirect('/reservas');

        $this->assertDatabaseHas('reservas', ['habitacion_id' => $habitacion->id, 'estado' => 'confirmada']);
    }
}
