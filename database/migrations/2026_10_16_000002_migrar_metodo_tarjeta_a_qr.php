<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sustituye el método de pago "tarjeta" por "qr" en los cobros existentes.
     */
    public function up(): void
    {
        DB::table('cobros')->where('metodo', 'tarjeta')->update(['metodo' => 'qr']);
    }

    public function down(): void
    {
        DB::table('cobros')->where('metodo', 'qr')->update(['metodo' => 'tarjeta']);
    }
};
