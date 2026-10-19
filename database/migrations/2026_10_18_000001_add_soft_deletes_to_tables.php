<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baja lógica (Soft Deletes) en las tablas centrales del sistema.
 *
 * La columna `deleted_at` permite eliminar registros sin perder
 * integridad referencial ni trazabilidad contable (auditoría, cobros,
 * reservas históricas). Los índices únicos naturales se desocupan en
 * el momento de la baja desde los controladores (ver UsuarioController,
 * HuespedController, HabitacionController y TipoHabitacionController).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tablas = ['users', 'huespedes', 'habitaciones', 'tipos_habitacion', 'reservas', 'cobros'];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tablas = ['users', 'huespedes', 'habitaciones', 'tipos_habitacion', 'reservas', 'cobros'];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
