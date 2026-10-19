<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Crea los usuarios del sistema.
     *
     * - Fuera de producción: usuarios de demostración con credenciales conocidas.
     * - En producción: solo el administrador inicial, y únicamente si
     *   SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD están definidos en el entorno
     *   (config('seed.admin_email') / config('seed.admin_password')).
     *   La contraseña debe tener al menos 12 caracteres.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->sembrarAdministradorProduccion();

            return;
        }

        $this->sembrarUsuariosDemo();
    }

    private function sembrarUsuariosDemo(): void
    {
        User::updateOrCreate(['email' => 'admin@hotel.com'], [
            'name' => 'Ana Administradora',
            'email' => 'admin@hotel.com',
            'password' => Hash::make('admin1234'),
            'role' => User::ROLE_ADMIN,
        ]);

        User::updateOrCreate(['email' => 'recepcion@hotel.com'], [
            'name' => 'Roberto Recepción',
            'email' => 'recepcion@hotel.com',
            'password' => Hash::make('recepcion1234'),
            'role' => User::ROLE_RECEPCIONISTA,
        ]);
    }

    private function sembrarAdministradorProduccion(): void
    {
        $email = config('seed.admin_email');
        $password = config('seed.admin_password');

        // Sin credenciales en el entorno no se crea ningún usuario por defecto.
        if (! $email || ! $password) {
            $this->command?->warn(
                'AVISO: SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD no están definidos. '
                .'No se creó el usuario administrador inicial.'
            );

            return;
        }

        if (strlen((string) $password) < 12) {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD debe tener al menos 12 caracteres en producción.'
            );
        }

        User::updateOrCreate(['email' => $email], [
            'name' => config('seed.admin_name', 'Administrador'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->command?->info('Usuario administrador inicial asegurado (credenciales desde variables de entorno).');
    }
}
