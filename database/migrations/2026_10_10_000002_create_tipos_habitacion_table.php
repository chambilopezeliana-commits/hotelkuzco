<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_habitacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->decimal('tarifa', 10, 2);
            $table->integer('capacidad')->default(1);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('habitaciones', function (Blueprint $table) {
            $table->foreignId('tipo_habitacion_id')
                ->nullable()
                ->after('numero')
                ->constrained('tipos_habitacion')
                ->nullOnDelete();
        });

        $this->migrarTiposExistentes();

        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'capacidad', 'precio']);
        });
    }

    public function down(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->string('tipo')->default('Estándar');
            $table->integer('capacidad')->default(2);
            $table->decimal('precio', 8, 2)->default(0);
        });

        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_habitacion_id');
        });

        Schema::dropIfExists('tipos_habitacion');
    }

    private function migrarTiposExistentes(): void
    {
        if (! Schema::hasColumn('habitaciones', 'tipo')) {
            return;
        }

        $filas = DB::table('habitaciones')
            ->select('tipo', DB::raw('MAX(capacidad) as capacidad'), DB::raw('MAX(precio) as tarifa'))
            ->groupBy('tipo')
            ->get();

        $ahora = now();

        foreach ($filas as $fila) {
            $tipoId = DB::table('tipos_habitacion')->insertGetId([
                'nombre' => $fila->tipo ?: 'Estándar',
                'tarifa' => $fila->tarifa ?? 0,
                'capacidad' => $fila->capacidad ?? 1,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('habitaciones')
                ->where('tipo', $fila->tipo)
                ->update(['tipo_habitacion_id' => $tipoId]);
        }
    }
};
