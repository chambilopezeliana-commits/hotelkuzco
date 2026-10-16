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
            'hero' => asset('img/hotel/hero.jpeg'),
            'instalaciones' => [
                [
                    'nombre' => 'Recepción',
                    'descripcion' => 'Bienvenida de la realeza desde tu llegada.',
                    'imagen' => asset('img/hotel/recepcion.jpeg'),
                ],
                [
                    'nombre' => 'Área de descanso',
                    'descripcion' => 'Salones cálidos para reposar y conversar.',
                    'imagen' => asset('img/hotel/descanso.jpeg'),
                ],
                [
                    'nombre' => 'Comedor',
                    'descripcion' => 'Banquete andino en un salón majestuoso.',
                    'imagen' => asset('img/hotel/comedor.jpeg'),
                ],
                [
                    'nombre' => 'Piscina',
                    'descripcion' => 'Aguas templadas bajo el sol del imperio.',
                    'imagen' => asset('img/hotel/piscina.jpeg'),
                ],
            ],
            'galeria' => array_map(fn ($archivo) => asset('img/hotel/'.$archivo), [
                'recepcion.jpeg', 'descanso.jpeg', 'comedor.jpeg',
                'piscina.jpeg', 'habitacion-simple-2.jpeg', 'instalaciones-extra.jpeg',
            ]),
            'totalHabitaciones' => $tipos->sum('total'),
            'whatsappUrl' => 'https://wa.me/'.preg_replace('/\D/', '', (string) config('hotel.whatsapp')),
        ]);
    }

    private function imagen(string $nombre): string
    {
        $mapa = [
            'simple' => 'habitacion-simple.jpeg',
            'doble' => 'habitacion-doble.jpeg',
            'familiar' => 'habitacion-familiar.jpeg',
            'suite' => 'suite.jpeg',
        ];

        $archivo = $mapa[Str::slug($nombre)] ?? null;
        $ruta = $archivo ? 'img/hotel/'.$archivo : null;

        if (! $ruta || ! file_exists(public_path($ruta))) {
            $ruta = 'img/habitaciones/default.svg';
        }

        return asset($ruta);
    }
}
