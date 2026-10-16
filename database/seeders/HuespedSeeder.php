<?php

namespace Database\Seeders;

use App\Models\Huesped;
use Illuminate\Database\Seeder;

class HuespedSeeder extends Seeder
{
    public function run(): void
    {
        $huespedes = [
            ['nombre' => 'Carlos Mendoza', 'tipo_documento' => 'DNI', 'documento' => '10234567', 'email' => 'carlos@mail.com', 'telefono' => '555-0101'],
            ['nombre' => 'Laura Fernández', 'tipo_documento' => 'DNI', 'documento' => '20345678', 'email' => 'laura@mail.com', 'telefono' => '555-0102'],
            ['nombre' => 'Miguel Torres', 'tipo_documento' => 'PASAPORTE', 'documento' => '30456789', 'email' => 'miguel@mail.com', 'telefono' => '555-0103'],
            ['nombre' => 'Sofía Ramírez', 'tipo_documento' => 'DNI', 'documento' => '40567890', 'email' => 'sofia@mail.com', 'telefono' => '555-0104'],
            ['nombre' => 'Diego Herrera', 'tipo_documento' => 'DNI', 'documento' => '50678901', 'email' => 'diego@mail.com', 'telefono' => '555-0105'],
            ['nombre' => 'Valentina Cruz', 'tipo_documento' => 'DNI', 'documento' => '60789012', 'email' => 'valentina@mail.com', 'telefono' => '555-0106'],
        ];

        foreach ($huespedes as $huesped) {
            Huesped::updateOrCreate(['documento' => $huesped['documento']], $huesped);
        }
    }
}
