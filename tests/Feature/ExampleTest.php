<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_publico_es_visible_sin_sesion(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_ruta_protegida_redirige_al_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
    }
}
