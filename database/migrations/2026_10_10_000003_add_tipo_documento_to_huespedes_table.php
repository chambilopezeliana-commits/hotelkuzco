<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->string('tipo_documento')->default('DNI')->after('nombre');
        });

        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropUnique(['documento']);
            $table->unique(['tipo_documento', 'documento']);
        });
    }

    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropUnique(['tipo_documento', 'documento']);
            $table->unique(['documento']);
            $table->dropColumn('tipo_documento');
        });
    }
};
