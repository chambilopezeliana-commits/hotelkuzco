<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habitaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('tipo_habitacion_id')
                ->nullable()
                ->constrained('tipos_habitacion')
                ->nullOnDelete();
            $table->string('estado')->default('disponible');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE habitaciones ENGINE = InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('habitaciones');
    }
};
