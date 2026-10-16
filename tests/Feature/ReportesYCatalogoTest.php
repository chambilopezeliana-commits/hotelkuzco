<?php

namespace Tests\Feature;

use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesYCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $recepcionista;

    private Habitacion $habitacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'administrador']);
        $this->recepcionista = User::factory()->create(['role' => 'recepcionista']);

        $this->habitacion = Habitacion::create([
            'numero' => '101',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Doble',
                'tarifa' => 200,
                'capacidad' => 2,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);
    }

    private function reserva(): Reserva
    {
        $huesped = Huesped::create(['nombre' => 'Test', 'documento' => 'DNI-1']);

        return Reserva::create([
            'habitacion_id' => $this->habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->addDays(2)->toDateString(),
            'num_huespedes' => 1,
            'estado' => 'confirmada',
            'total' => 400,
        ]);
    }

    public function test_admin_ve_disponibilidad(): void
    {
        $this->actingAs($this->admin)
            ->get('/disponibilidad')
            ->assertStatus(200)
            ->assertSee('Disponibilidad de habitaciones');
    }

    public function test_admin_ve_reporte_con_indicadores(): void
    {
        $this->actingAs($this->admin)
            ->get('/reportes')
            ->assertStatus(200)
            ->assertSee('RevPAR')
            ->assertSee('ADR');
    }

    public function test_admin_ve_reporte_imprimible(): void
    {
        $this->actingAs($this->admin)
            ->get('/reportes/imprimir')
            ->assertStatus(200)
            ->assertSee('Reporte de cálculo');
    }

    public function test_admin_ve_reportes_analiticos(): void
    {
        $this->actingAs($this->admin)
            ->get('/reportes')
            ->assertStatus(200)
            ->assertSee('Estadía promedio')
            ->assertSee('Tasa de cancelación')
            ->assertSee('Top habitaciones por ingresos')
            ->assertSee('Detalle diario');
    }

    public function test_admin_descarga_reporte_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get('/reportes/pdf');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_recepcionista_no_descarga_reporte_pdf(): void
    {
        $this->actingAs($this->recepcionista)
            ->get('/reportes/pdf')
            ->assertStatus(403);
    }

    public function test_admin_ve_auditorias(): void
    {
        $this->actingAs($this->admin)
            ->get('/auditorias')
            ->assertStatus(200)
            ->assertSee('Bitácora de auditoría');
    }

    public function test_recepcionista_no_ve_auditorias(): void
    {
        $this->actingAs($this->recepcionista)
            ->get('/auditorias')
            ->assertStatus(403);
    }

    public function test_admin_gestiona_tipos_habitacion(): void
    {
        $this->actingAs($this->admin)
            ->get('/tipos-habitacion')
            ->assertStatus(200)
            ->assertSee('Doble');
    }

    public function test_recepcionista_no_gestiona_tipos_habitacion(): void
    {
        $this->actingAs($this->recepcionista)
            ->get('/tipos-habitacion/create')
            ->assertStatus(403);
    }

    public function test_admin_cambia_estado_usuario(): void
    {
        $this->assertTrue($this->recepcionista->fresh()->activo);

        $this->actingAs($this->admin)
            ->patch("/usuarios/{$this->recepcionista->id}/toggle")
            ->assertRedirect();

        $this->assertFalse($this->recepcionista->fresh()->activo);
    }

    public function test_admin_ve_formulario_walk_in(): void
    {
        $this->actingAs($this->admin)
            ->get('/reservas/walk-in')
            ->assertStatus(200);
    }

    public function test_admin_ve_comprobante_de_reserva(): void
    {
        $reserva = $this->reserva();

        $this->actingAs($this->admin)
            ->get("/reservas/{$reserva->id}/comprobante")
            ->assertStatus(200)
            ->assertSee('Comprobante');
    }

    public function test_importacion_es_solo_admin(): void
    {
        $this->actingAs($this->recepcionista)
            ->get('/huespedes/importar/csv')
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->get('/huespedes/importar/csv')
            ->assertStatus(200);
    }
}
