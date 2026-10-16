<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\TipoHabitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacionTiposTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'administrador']);
    }

    public function test_tipo_documento_invalido_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Juan Perez',
                'tipo_documento' => 'PASSPORT',
                'documento' => 'X-1',
            ])
            ->assertSessionHasErrors('tipo_documento');

        $this->assertDatabaseCount('huespedes', 0);
    }

    public function test_tipo_documento_se_normaliza_a_mayusculas(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Maria Lopez',
                'tipo_documento' => 'dni',
                'documento' => 'X-2',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/huespedes');

        $this->assertDatabaseHas('huespedes', [
            'tipo_documento' => 'DNI',
            'documento' => 'X-2',
        ]);
    }

    public function test_telefono_con_letras_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Pedro Gomez',
                'tipo_documento' => 'DNI',
                'documento' => 'X-3',
                'telefono' => 'llamar a Pedro',
            ])
            ->assertSessionHasErrors('telefono');
    }

    public function test_telefono_valido_es_aceptado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Pedro Gomez',
                'tipo_documento' => 'DNI',
                'documento' => 'X-4',
                'telefono' => '+591 71234567',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/huespedes');

        $this->assertDatabaseHas('huespedes', ['telefono' => '+591 71234567']);
    }

    public function test_numero_de_habitacion_no_numerico_es_rechazado(): void
    {
        $tipo = TipoHabitacion::create([
            'nombre' => 'Simple Test',
            'tarifa' => 50,
            'capacidad' => 1,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post('/habitaciones', [
                'numero' => 'Suite Grande',
                'tipo_habitacion_id' => $tipo->id,
                'estado' => 'disponible',
            ])
            ->assertSessionHasErrors('numero');

        $this->assertDatabaseCount('habitaciones', 0);
    }

    public function test_reserva_excede_capacidad_es_rechazada(): void
    {
        $tipo = TipoHabitacion::create([
            'nombre' => 'Simple Test',
            'tarifa' => 50,
            'capacidad' => 1,
            'activo' => true,
        ]);

        $habitacion = Habitacion::create([
            'numero' => '101',
            'tipo_habitacion_id' => $tipo->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create([
            'nombre' => 'Carlos Test',
            'documento' => 'DNI-999',
        ]);

        $this->actingAs($this->admin)
            ->post('/reservas', [
                'habitacion_id' => $habitacion->id,
                'huesped_id' => $huesped->id,
                'fecha_entrada' => '2027-01-01',
                'fecha_salida' => '2027-01-03',
                'num_huespedes' => 4,
                'estado' => 'confirmada',
                'anticipo_monto' => 30,
                'anticipo_metodo' => 'efectivo',
                'anticipo_fecha' => '2027-01-01',
            ])
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'admite como máximo'));

        $this->assertDatabaseCount('reservas', 0);
    }
}
