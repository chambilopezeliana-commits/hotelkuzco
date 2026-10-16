<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_muestra_el_formulario(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Inicio de sesión');
    }

    public function test_login_correcto_redirige_al_panel(): void
    {
        $usuario = User::factory()->create([
            'email' => 'test@hotel.com',
            'password' => 'secreta123',
            'role' => 'recepcionista',
        ]);

        $this->post('/login', [
            'email' => 'test@hotel.com',
            'password' => 'secreta123',
        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_incorrecto_falla(): void
    {
        User::factory()->create([
            'email' => 'test@hotel.com',
            'password' => 'secreta123',
        ]);

        $this->post('/login', [
            'email' => 'test@hotel.com',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $usuario = User::factory()->create(['password' => 'secreta123']);

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_rutas_protegidas_requieren_sesion(): void
    {
        $this->get('/reservas')->assertRedirect('/login');
        $this->get('/habitaciones')->assertRedirect('/login');
    }
}
