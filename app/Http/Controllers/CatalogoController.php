<?php

namespace App\Http\Controllers;

use App\Models\TipoHabitacion;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(): View
    {
        $tipos = TipoHabitacion::query()
            ->activos()
            ->with(['habitaciones' => fn ($query) => $query
                ->where('estado', '!=', 'mantenimiento')
                ->orderBy('numero')])
            ->orderBy('tarifa')
            ->get()
            ->map(fn (TipoHabitacion $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'tarifa' => (float) $tipo->tarifa,
                'capacidad' => (int) $tipo->capacidad,
                'descripcion' => $tipo->descripcion,
                'imagen' => $this->imagen($tipo->nombre),
                'disponibles' => $tipo->habitaciones->where('estado', '!=', 'ocupada')->count(),
                'total' => $tipo->habitaciones->count(),
                'numeros' => $tipo->habitaciones->pluck('numero')->all(),
            ]);

        return view('catalogo', [
            'tipos' => $tipos,
            'contacto' => config('hotel'),
            'hero' => asset('img/hotel-kuzco/hero.jpg'),
            'instalaciones' => [
                [
                    'nombre' => 'Recepción',
                    'descripcion' => 'Bienvenida de la realeza desde tu llegada.',
                    'imagen' => asset('img/hotel-kuzco/recepcion.jpg'),
                ],
                [
                    'nombre' => 'Área de descanso',
                    'descripcion' => 'Salones cálidos para reposar y conversar.',
                    'imagen' => asset('img/hotel-kuzco/descanso.jpg'),
                ],
                [
                    'nombre' => 'Comedor',
                    'descripcion' => 'Banquete andino en un salón majestuoso.',
                    'imagen' => asset('img/hotel-kuzco/comedor.jpg'),
                ],
                [
                    'nombre' => 'Piscina',
                    'descripcion' => 'Aguas templadas bajo el sol del imperio.',
                    'imagen' => asset('img/hotel-kuzco/piscina.jpg'),
                ],
            ],
            'galeria' => array_map(fn ($archivo) => asset('img/hotel-kuzco/'.$archivo), [
                'recepcion.jpg', 'descanso.jpg', 'comedor.jpg',
                'piscina.jpg', 'juegos.jpg', 'habitacion-doble.jpg',
            ]),
            'totalHabitaciones' => $tipos->sum('total'),
            'whatsappUrl' => 'https://wa.me/'.preg_replace('/\D/', '', (string) config('hotel.whatsapp')),
        ]);
    }

    private function imagen(string $nombre): string
    {
        $mapa = [
            'simple' => ['habitacion-simple.jpg'],
            'doble' => ['habitacion-doble.jpg'],
            'triple' => ['habitacion-triple.jpg', 'habitacion-familiar.jpg'],
            'familiar' => ['habitacion-familiar.jpg'],
            'suite' => ['suite.jpg'],
        ];

        $candidatos = $mapa[Str::slug($nombre)] ?? [];

        foreach ($candidatos as $archivo) {
            $ruta = 'img/hotel-kuzco/'.$archivo;

            if (file_exists(public_path($ruta))) {
                return asset($ruta);
            }
        }

        return asset('img/habitaciones/default.svg');
    }
}
