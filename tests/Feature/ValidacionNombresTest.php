<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacionNombresTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'administrador']);
    }

    public function test_huesped_con_numeros_en_nombre_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Carlos 123',
                'tipo_documento' => 'DNI',
                'documento' => 'DNI-1',
            ])
            ->assertSessionHasErrors('nombre');
    }

    public function test_huesped_con_simbolos_en_nombre_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'Ana@López',
                'tipo_documento' => 'DNI',
                'documento' => 'DNI-2',
            ])
            ->assertSessionHasErrors('nombre');
    }

    public function test_huesped_con_nombre_valido_es_aceptado(): void
    {
        $this->actingAs($this->admin)
            ->post('/huespedes', [
                'nombre' => 'María José Núñez',
                'tipo_documento' => 'DNI',
                'documento' => 'DNI-3',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/huespedes');

        $this->assertDatabaseHas('huespedes', ['nombre' => 'María José Núñez']);
    }

    public function test_usuario_con_numeros_en_nombre_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/usuarios', [
                'name' => 'Usuario 99',
                'email' => 'nuevo@hotel.com',
                'role' => 'recepcionista',
                'password' => 'secreto123',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_tipo_habitacion_con_simbolos_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post('/tipos-habitacion', [
                'nombre' => 'Suite#1',
                'tarifa' => 100,
                'capacidad' => 2,
            ])
            ->assertSessionHasErrors('nombre');
    }
}
