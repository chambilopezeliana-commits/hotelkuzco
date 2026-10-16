<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->string('clave_idempotencia')->nullable()->unique()->after('fecha');
            $table->string('tipo')->default('pago')->after('concepto');
            $table->string('motivo')->nullable()->after('tipo');
            $table->boolean('anulado')->default(false)->after('motivo');
            $table->foreignId('cobro_origen_id')
                ->nullable()
                ->after('reserva_id')
                ->constrained('cobros')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cobro_origen_id');
            $table->dropColumn(['clave_idempotencia', 'tipo', 'motivo', 'anulado']);
        });
    }
};
