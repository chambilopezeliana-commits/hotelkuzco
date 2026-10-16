<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->constrained('habitaciones')->cascadeOnDelete();
            $table->foreignId('huesped_id')->constrained('huespedes')->restrictOnDelete();
            $table->date('fecha_entrada');
            $table->date('fecha_salida');
            $table->integer('num_huespedes')->default(1);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('estado')->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['habitacion_id', 'fecha_entrada', 'fecha_salida']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement(<<<'SQL'
                ALTER TABLE reservas ADD CONSTRAINT reservas_sin_solape
                EXCLUDE USING gist (
                    habitacion_id WITH =,
                    daterange(fecha_entrada, fecha_salida, '[)') WITH &&
                )
                WHERE (estado <> 'cancelada')
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reservas DROP CONSTRAINT IF EXISTS reservas_sin_solape');
        }

        Schema::dropIfExists('reservas');
    }
};
