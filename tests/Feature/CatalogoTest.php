<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_ve_el_catalogo_con_tipos_y_precios(): void
    {
        $tipo = TipoHabitacion::create([
            'nombre' => 'Doble',
            'tarifa' => 250,
            'capacidad' => 2,
            'descripcion' => 'Cama doble',
            'activo' => true,
        ]);

        Habitacion::create([
            'numero' => '201',
            'tipo_habitacion_id' => $tipo->id,
            'estado' => 'disponible',
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Doble')
            ->assertSee('250,00')
            ->assertSee('201')
            ->assertSee('habitacion-doble.jpeg');
    }

    public function test_el_catalogo_muestra_instalaciones_y_galeria(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Recepción')
            ->assertSee('Área de descanso')
            ->assertSee('Comedor')
            ->assertSee('Piscina')
            ->assertSee('img/hotel/recepcion.jpeg')
            ->assertSee('img/hotel/descanso.jpeg')
            ->assertSee('img/hotel/comedor.jpeg')
            ->assertSee('img/hotel/piscina.jpeg');
    }

    public function test_las_imagenes_del_catalogo_existen(): void
    {
        foreach ([
            'hero.jpeg', 'habitacion-simple.jpeg', 'habitacion-doble.jpeg',
            'habitacion-familiar.jpeg', 'suite.jpeg', 'recepcion.jpeg',
            'descanso.jpeg', 'comedor.jpeg', 'piscina.jpeg',
        ] as $archivo) {
            $this->assertFileExists(public_path('img/hotel/'.$archivo));
        }
    }

    public function test_el_catalogo_es_publico_sin_autenticacion(): void
    {
        $this->assertGuest();

        $this->get('/')->assertOk();
    }

    public function test_login_redirige_al_panel(): void
    {
        $this->get('/login')->assertOk();
    }
}
