<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        return view('reportes.index', $this->datos($request));
    }

    public function imprimir(Request $request): View
    {
        return view('reportes.imprimir', $this->datos($request));
    }

    public function pdf(Request $request): Response
    {
        $datos = $this->datos($request);

        $pdf = Pdf::loadView('reportes.pdf', $datos)
            ->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $nombre = 'reporte_'.$datos['desde']->format('Ymd').'_'.$datos['hasta']->format('Ymd').'.pdf';

        return $pdf->download($nombre);
    }

    private function datos(Request $request): array
    {
        $entrada = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ], [
            'desde.date' => 'La fecha "desde" no es válida.',
            'hasta.date' => 'La fecha "hasta" no es válida.',
        ]);

        $desde = ! empty($entrada['desde'])
            ? Carbon::parse($entrada['desde'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $hasta = ! empty($entrada['hasta'])
            ? Carbon::parse($entrada['hasta'])->endOfDay()
            : Carbon::now()->endOfDay();

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $tope = $desde->copy()->addYear();
        if ($hasta->gt($tope)) {
            $hasta = $tope->copy()->endOfDay();
        }

        $nochesPeriodo = max(1, (int) $desde->startOfDay()->diffInDays($hasta->copy()->addDay()->startOfDay()));

        $totalHabitaciones = Habitacion::count();

        $nochesVendidas = $this->nochesVendidas($desde, $hasta);
        $ocupacion = $totalHabitaciones > 0 && $nochesPeriodo > 0
            ? round($nochesVendidas / ($totalHabitaciones * $nochesPeriodo) * 100, 2)
            : 0.0;

        $cobros = Cobro::whereBetween('fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()]);

        $ingresosNetos = (float) (clone $cobros)->sum('monto');
        $pagosBrutos = (float) (clone $cobros)->where('monto', '>', 0)->sum('monto');
        $contraasientos = (float) (clone $cobros)->where('monto', '<', 0)->sum('monto');
        $cantidadPagos = (int) (clone $cobros)->where('monto', '>', 0)->count();

        $adr = $nochesVendidas > 0 ? round($ingresosNetos / $nochesVendidas, 2) : 0.0;
        $revpar = $totalHabitaciones > 0 && $nochesPeriodo > 0
            ? round($ingresosNetos / ($totalHabitaciones * $nochesPeriodo), 2)
            : 0.0;

        $porTipo = $this->porTipoHabitacion($desde, $hasta);
        $porMetodo = $this->porMetodoPago($desde, $hasta);
        $qrTransferencia = $this->detalleQrTransferencia($desde, $hasta);
        $reservasPeriodo = Reserva::whereBetween('created_at', [$desde, $hasta])
            ->get(['id', 'estado', 'fecha_entrada', 'fecha_salida']);
        $reservasPorEstado = $reservasPeriodo
            ->groupBy('estado')
            ->map->count();

        $totalReservasPeriodo = $reservasPeriodo->count();
        $canceladas = (int) ($reservasPorEstado['cancelada'] ?? 0);
        $tasaCancelacion = $totalReservasPeriodo > 0
            ? round($canceladas / $totalReservasPeriodo * 100, 2)
            : 0.0;
        $estadiaPromedio = $totalReservasPeriodo > 0
            ? round((float) $reservasPeriodo->avg(fn (Reserva $r) => $r->noches()), 2)
            : 0.0;

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'nochesPeriodo' => $nochesPeriodo,
            'nochesVendidas' => $nochesVendidas,
            'totalHabitaciones' => $totalHabitaciones,
            'ocupacion' => $ocupacion,
            'ingresosNetos' => $ingresosNetos,
            'pagosBrutos' => $pagosBrutos,
            'contraasientos' => $contraasientos,
            'cantidadPagos' => $cantidadPagos,
            'adr' => $adr,
            'revpar' => $revpar,
            'porTipo' => $porTipo,
            'porMetodo' => $porMetodo,
            'detalleQrTransferencia' => $qrTransferencia['detalle'],
            'totalesQrTransferencia' => $qrTransferencia['totales'],
            'reservasPorEstado' => $reservasPorEstado,
            'totalReservasPeriodo' => $totalReservasPeriodo,
            'canceladas' => $canceladas,
            'tasaCancelacion' => $tasaCancelacion,
            'estadiaPromedio' => $estadiaPromedio,
            'serieDiaria' => $this->serieDiaria($desde, $hasta, $totalHabitaciones),
            'topHabitaciones' => $this->topHabitaciones($desde, $hasta),
        ];
    }

    private function nochesVendidas(Carbon $desde, Carbon $hasta): int
    {
        $inicioDespues = $desde->toDateString();
        $finAntes = $hasta->toDateString();

        $reservas = Reserva::whereIn('estado', ['confirmada', 'hospedado', 'finalizada'])
            ->whereDate('fecha_entrada', '<=', $finAntes)
            ->whereDate('fecha_salida', '>=', $inicioDespues)
            ->get(['fecha_entrada', 'fecha_salida']);

        $total = 0;

        foreach ($reservas as $reserva) {
            $entrada = $reserva->fecha_entrada->startOfDay();
            $salida = $reserva->fecha_salida->startOfDay();

            $desdeSolape = $entrada->gt($desde->startOfDay()) ? $entrada : $desde->startOfDay();
            $hastaSolape = $salida->lt($hasta->copy()->addDay()->startOfDay()) ? $salida : $hasta->copy()->addDay()->startOfDay();

            $noches = max(0, (int) $desdeSolape->diffInDays($hastaSolape));
            $total += $noches;
        }

        return $total;
    }

    private function porTipoHabitacion(Carbon $desde, Carbon $hasta)
    {
        $tipos = TipoHabitacion::orderBy('nombre')->get();

        return $tipos->map(function ($tipo) use ($desde, $hasta) {
            $reservas = Reserva::whereIn('estado', ['confirmada', 'hospedado', 'finalizada'])
                ->whereHas('habitacion', fn ($q) => $q->where('tipo_habitacion_id', $tipo->id))
                ->whereDate('fecha_entrada', '<=', $hasta->toDateString())
                ->whereDate('fecha_salida', '>=', $desde->toDateString())
                ->get(['id', 'fecha_entrada', 'fecha_salida', 'habitacion_id']);

            $noches = 0;
            foreach ($reservas as $reserva) {
                $entrada = $reserva->fecha_entrada->startOfDay();
                $salida = $reserva->fecha_salida->startOfDay();
                $desdeSolape = $entrada->gt($desde->startOfDay()) ? $entrada : $desde->startOfDay();
                $hastaSolape = $salida->lt($hasta->copy()->addDay()->startOfDay()) ? $salida : $hasta->copy()->addDay()->startOfDay();
                $noches += max(0, (int) $desdeSolape->diffInDays($hastaSolape));
            }

            $ingresos = (float) Cobro::whereBetween('fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()])
                ->whereIn('reserva_id', $reservas->pluck('id'))
                ->sum('monto');

            return [
                'nombre' => $tipo->nombre,
                'habitaciones' => Habitacion::where('tipo_habitacion_id', $tipo->id)->count(),
                'noches' => $noches,
                'ingresos' => $ingresos,
                'adr' => $noches > 0 ? round($ingresos / $noches, 2) : 0.0,
            ];
        })->values();
    }

    private function porMetodoPago(Carbon $desde, Carbon $hasta)
    {
        return Cobro::whereBetween('fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()])
            ->selectRaw('metodo, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('metodo')
            ->get()
            ->map(fn ($fila) => [
                'metodo' => Cobro::etiqueta($fila->metodo),
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ]);
    }

    /**
     * Detalle de cobros realizados por QR y transferencia bancaria, con sus totales.
     *
     * @return array{detalle: Collection, totales: Collection}
     */
    private function detalleQrTransferencia(Carbon $desde, Carbon $hasta): array
    {
        $metodos = ['qr', 'transferencia'];

        $cobros = Cobro::with(['reserva.huesped', 'reserva.habitacion', 'usuario'])
            ->whereBetween('fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()])
            ->whereIn('metodo', $metodos)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $detalle = $cobros->map(fn (Cobro $cobro) => [
            'fecha' => $cobro->fecha,
            'metodo' => $cobro->metodo,
            'metodo_label' => Cobro::etiqueta($cobro->metodo),
            'monto' => (float) $cobro->monto,
            'reserva_id' => $cobro->reserva_id,
            'huesped' => $cobro->reserva->huesped->nombre ?? '—',
            'documento' => $cobro->reserva->huesped->documento ?? '',
            'habitacion' => $cobro->reserva->habitacion->numero ?? '—',
            'registrado_por' => $cobro->usuario->name ?? '—',
            'anulado' => (bool) $cobro->anulado,
        ]);

        $totales = collect($metodos)->map(fn (string $metodo) => [
            'metodo' => $metodo,
            'metodo_label' => Cobro::etiqueta($metodo),
            'cantidad' => $cobros->where('metodo', $metodo)->count(),
            'total' => round((float) $cobros->where('metodo', $metodo)->sum('monto'), 2),
        ]);

        return ['detalle' => $detalle, 'totales' => $totales];
    }

    /**
     * Serie diaria de ocupación e ingresos para el período.
     *
     * @return array<int, array{fecha: Carbon, noches: int, ingresos: float, ocupacion: float}>
     */
    private function serieDiaria(Carbon $desde, Carbon $hasta, int $totalHabitaciones): array
    {
        $reservas = Reserva::whereIn('estado', ['confirmada', 'hospedado', 'finalizada'])
            ->whereDate('fecha_entrada', '<=', $hasta->toDateString())
            ->whereDate('fecha_salida', '>=', $desde->toDateString())
            ->get(['fecha_entrada', 'fecha_salida']);

        $ingresosPorDia = Cobro::whereBetween('fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()])
            ->get(['fecha', 'monto'])
            ->groupBy(fn (Cobro $cobro) => $cobro->fecha->toDateString())
            ->map(fn ($grupo) => (float) $grupo->sum('monto'));

        $serie = [];
        $cursor = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->startOfDay();

        while ($cursor->lte($fin)) {
            $noches = 0;

            foreach ($reservas as $reserva) {
                $entrada = $reserva->fecha_entrada->startOfDay();
                $salida = $reserva->fecha_salida->startOfDay();

                if ($entrada->lte($cursor) && $salida->gt($cursor)) {
                    $noches++;
                }
            }

            $serie[] = [
                'fecha' => $cursor->copy(),
                'noches' => $noches,
                'ingresos' => $ingresosPorDia[$cursor->toDateString()] ?? 0.0,
                'ocupacion' => $totalHabitaciones > 0 ? round($noches / $totalHabitaciones * 100, 1) : 0.0,
            ];

            $cursor->addDay();
        }

        return $serie;
    }

    private function topHabitaciones(Carbon $desde, Carbon $hasta)
    {
        return DB::table('cobros')
            ->join('reservas', 'reservas.id', '=', 'cobros.reserva_id')
            ->join('habitaciones', 'habitaciones.id', '=', 'reservas.habitacion_id')
            ->whereNotNull('cobros.reserva_id')
            ->whereBetween('cobros.fecha', [$desde->toDateTimeString(), $hasta->toDateTimeString()])
            ->selectRaw('habitaciones.numero as numero, SUM(cobros.monto) as ingresos, COUNT(*) as pagos')
            ->groupBy('habitaciones.numero')
            ->orderByDesc('ingresos')
            ->limit(10)
            ->get()
            ->map(fn ($fila) => [
                'numero' => $fila->numero,
                'ingresos' => (float) $fila->ingresos,
                'pagos' => (int) $fila->pagos,
            ]);
    }
}
