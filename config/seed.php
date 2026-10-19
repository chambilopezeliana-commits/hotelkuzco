<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credenciales del administrador inicial (solo siembra en producción)
    |--------------------------------------------------------------------------
    |
    | Se leen desde el entorno para no hardcodear credenciales en el código.
    | El seeder solo las utiliza cuando APP_ENV=production y ambas variables
    | están definidas; si faltan, no se crea ningún usuario por defecto.
    |
    */

    'admin_name' => env('SEED_ADMIN_NAME', 'Administrador'),

    'admin_email' => env('SEED_ADMIN_EMAIL'),

    'admin_password' => env('SEED_ADMIN_PASSWORD'),

];
