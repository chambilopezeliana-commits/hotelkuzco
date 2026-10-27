<?php

namespace Tests\Unit;

use App\Models\Cobro;
use App\Models\Habitacion;
use PHPUnit\Framework\TestCase;

class CobroCalculosTest extends TestCase
{
    public function test_cobro_constantes_metodos(): void
    {
        $this->assertContains('efectivo', Cobro::METODOS);
        $this->assertContains('qr', Cobro::METODOS);
        $this->assertContains('transferencia', Cobro::METODOS);
    }

    public function test_cobro_constantes_tipos(): void
    {
        $this->assertSame('pago', Cobro::TIPO_PAGO);
        $this->assertSame('contraasiento', Cobro::TIPO_CONTRAASIENTO);
    }

    public function test_cobro_etiqueta_metodos(): void
    {
        $this->assertSame('Efectivo', Cobro::etiqueta('efectivo'));
        $this->assertSame('QR', Cobro::etiqueta('qr'));
        $this->assertSame('Transferencia bancaria', Cobro::etiqueta('transferencia'));
    }

    public function test_habitacion_constantes_estados(): void
    {
        $this->assertContains('disponible', Habitacion::ESTADOS);
        $this->assertContains('ocupada', Habitacion::ESTADOS);
        $this->assertContains('mantenimiento', Habitacion::ESTADOS);
        $this->assertContains('limpieza', Habitacion::ESTADOS);
    }

    public function test_habitacion_estados_no_disponibles(): void
    {
        $this->assertContains('mantenimiento', Habitacion::ESTADOS_NO_DISPONIBLES);
        $this->assertContains('limpieza', Habitacion::ESTADOS_NO_DISPONIBLES);
        $this->assertNotContains('disponible', Habitacion::ESTADOS_NO_DISPONIBLES);
    }
}
