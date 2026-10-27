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
            $table->string('estado')->default('confirmada');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['habitacion_id', 'fecha_entrada', 'fecha_salida']);
            $table->index('estado');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reservas ENGINE = InnoDB');
            try {
                DB::statement('ALTER TABLE reservas ADD CONSTRAINT reservas_fechas_validas CHECK (fecha_salida > fecha_entrada)');
            } catch (\Throwable $e) {
                // Ignorar si la restricción ya existe o no es soportada
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement(<<<'SQL'
                ALTER TABLE reservas ADD CONSTRAINT reservas_sin_solape
                EXCLUDE USING gist (
                    habitacion_id WITH =,
                    daterange(fecha_entrada, fecha_salida, '[)') WITH &&
                ) WHERE (estado NOT IN ('cancelada', 'finalizada') AND deleted_at IS NULL)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
