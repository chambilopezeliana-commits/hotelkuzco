<?php

namespace Tests\Feature;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Models\User;
use App\Services\CobroService;
use App\Services\OperacionInvalida;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnulacionCobroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $recepcionista;

    private Cobro $cobro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);

        $this->admin = User::where('email', 'admin@hotel.com')->first();
        $this->recepcionista = User::where('email', 'recepcion@hotel.com')->first();

        $this->cobro = $this->crearCobro();
    }

    private function crearCobro(): Cobro
    {
        $habitacion = Habitacion::create([
            'numero' => '901',
            'tipo_habitacion_id' => TipoHabitacion::create([
                'nombre' => 'Simple Anulación',
                'tarifa' => 60,
                'capacidad' => 1,
                'activo' => true,
            ])->id,
            'estado' => 'disponible',
        ]);

        $huesped = Huesped::create([
            'nombre' => 'Cliente Anulación',
            'documento' => 'DNI-ANU-1',
        ]);

        $reserva = Reserva::create([
            'habitacion_id' => $habitacion->id,
            'huesped_id' => $huesped->id,
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->addDays(2)->toDateString(),
            'num_huespedes' => 1,
            'total' => 120,
            'estado' => 'confirmada',
        ]);

        return Cobro::create([
            'reserva_id' => $reserva->id,
            'user_id' => $this->recepcionista->id,
            'monto' => 60,
            'metodo' => 'efectivo',
            'concepto' => 'Anticipo',
            'tipo' => Cobro::TIPO_PAGO,
            'anulado' => false,
            'fecha' => now()->toDateString(),
            'clave_idempotencia' => 'ANU-REF-1',
        ]);
    }

    // T09: el recepcionista no puede anular pagos (matriz RBAC 4.5) → 403.
    public function test_recepcionista_no_puede_anular_pagos(): void
    {
        $this->actingAs($this->recepcionista)
            ->post("/cobros/{$this->cobro->id}/anular", ['motivo' => 'Error de caja'])
            ->assertStatus(403);

        $this->assertFalse($this->cobro->fresh()->anulado);
        $this->assertSame(1, Cobro::count());
    }

    // T10: el administrador anula mediante contraasiento con motivo → 200.
    public function test_admin_anula_pago_contraasiento_con_motivo(): void
    {
        $this->actingAs($this->admin)
            ->post("/cobros/{$this->cobro->id}/anular", ['motivo' => 'Error de caja'])
            ->assertRedirect();

        $this->assertTrue($this->cobro->fresh()->anulado);

        $contraasiento = Cobro::where('tipo', Cobro::TIPO_CONTRAASIENTO)->firstOrFail();
        $this->assertSame(-60.0, (float) $contraasiento->monto);
        $this->assertSame('Error de caja', $contraasiento->motivo);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'anulacion',
            'entidad' => 'cobro',
            'entidad_id' => $this->cobro->id,
        ]);
    }

    public function test_anulacion_requiere_motivo(): void
    {
        $this->actingAs($this->admin)
            ->post("/cobros/{$this->cobro->id}/anular", [])
            ->assertSessionHasErrors('motivo');
    }

    public function test_no_se_puede_anular_dos_veces_con_un_modelo_obsoleto(): void
    {
        $this->actingAs($this->admin)
            ->post("/cobros/{$this->cobro->id}/anular", ['motivo' => 'Error de caja'])
            ->assertRedirect();

        $this->expectException(OperacionInvalida::class);

        $this->app->make(CobroService::class)->anular($this->cobro, 'Reintento', $this->admin);
    }
}
