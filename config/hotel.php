<?php

return [
    'nombre' => env('HOTEL_NOMBRE', 'Kuzco'),

    'lema' => env('HOTEL_LEMA', 'Palacio del Emperador'),

    'eslogan' => env('HOTEL_ESLOGAN', 'Una estadía de reyes'),

    'descripcion' => env(
        'HOTEL_DESCRIPCION',
        'Inspirado en el esplendor del imperio inca, Kuzco combina el lujo de un palacio '
        .'con la calidez de su gente. Habitaciones majestuosas, atención de la realeza y '
        .'una ubicación privilegiada para una estadía inolvidable.'
    ),

    'direccion' => env('HOTEL_DIRECCION', 'Av. Principal 123, Ciudad'),

    'telefono' => env('HOTEL_TELEFONO', '+591 700-00000'),

    'whatsapp' => env('HOTEL_WHATSAPP', '59170000000'),

    'email' => env('HOTEL_EMAIL', 'reservas@hotelkuzco.com'),

    'horario' => env('HOTEL_HORARIO', 'Recepción abierta 24 horas'),

    'facebook' => env('HOTEL_FACEBOOK', 'https://www.facebook.com/hotelkuzco'),

    'instagram' => env('HOTEL_INSTAGRAM', 'https://www.instagram.com/hotelkuzco'),

    'tiktok' => env('HOTEL_TIKTOK', 'https://www.tiktok.com/@hotelkuzco'),
];
