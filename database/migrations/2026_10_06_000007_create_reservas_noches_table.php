<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas_noches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('habitacion_id')->constrained('habitaciones')->cascadeOnDelete();
            $table->date('noche');
            $table->timestamps();

            $table->unique(['habitacion_id', 'noche']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reservas_noches ENGINE = InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas_noches');
    }
};
