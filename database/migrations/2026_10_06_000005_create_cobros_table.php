<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('cobro_origen_id')
                ->nullable()
                ->constrained('cobros')
                ->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo')->default('efectivo');
            $table->string('concepto')->default('Estadía');
            $table->string('tipo')->default('pago');
            $table->string('motivo')->nullable();
            $table->boolean('anulado')->default(false);
            $table->date('fecha');
            $table->string('clave_idempotencia')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cobros ENGINE = InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
