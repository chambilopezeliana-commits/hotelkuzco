<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra la base de datos.
     *
     * En producción SOLO se crea el usuario administrador inicial y únicamente
     * si las credenciales seguras fueron provistas por variables de entorno
     * (SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD). Los datos de demostración
     * (habitaciones, huéspedes, reservas de ejemplo) nunca se cargan en producción.
     */
    public function run(): void
    {
        // En producción solo se siembra el administrador inicial (con credenciales del .env).
        if (app()->isProduction()) {
            $this->call(UserSeeder::class);

            return;
        }

        // Entornos locales/desarrollo: datos completos de demostración.
        $this->call([
            UserSeeder::class,
            TipoHabitacionSeeder::class,
            HabitacionSeeder::class,
            HuespedSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
