<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
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
}
