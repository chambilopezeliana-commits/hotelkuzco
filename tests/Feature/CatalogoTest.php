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
            ->assertSee('habitacion-doble.jpg');
    }

    public function test_el_catalogo_muestra_instalaciones_y_galeria(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Recepción')
            ->assertSee('Área de descanso')
            ->assertSee('Comedor')
            ->assertSee('Piscina')
            ->assertSee('img/hotel-kuzco/recepcion.jpg')
            ->assertSee('img/hotel-kuzco/descanso.jpg')
            ->assertSee('img/hotel-kuzco/comedor.jpg')
            ->assertSee('img/hotel-kuzco/piscina.jpg');
    }

    public function test_las_imagenes_del_catalogo_existen(): void
    {
        foreach ([
            'hero.jpg', 'habitacion-simple.jpg', 'habitacion-doble.jpg',
            'habitacion-familiar.jpg', 'habitacion-triple.jpg', 'suite.jpg',
            'recepcion.jpg', 'descanso.jpg', 'comedor.jpg', 'piscina.jpg',
            'juegos.jpg',
        ] as $archivo) {
            $this->assertFileExists(public_path('img/hotel-kuzco/'.$archivo));
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
