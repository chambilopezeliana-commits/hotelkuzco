<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $query = Auditoria::query()->with('usuario')->orderByDesc('created_at');

        if ($request->filled('entidad')) {
            $query->where('entidad', $request->get('entidad'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->get('accion'));
        }

        if (isset($datos['desde'])) {
            $query->whereDate('created_at', '>=', $datos['desde']);
        }

        if (isset($datos['hasta'])) {
            $query->whereDate('created_at', '<=', $datos['hasta']);
        }

        return view('auditorias.index', [
            'auditorias' => $query->paginate(20)->withQueryString(),
            'entidad' => $request->get('entidad'),
            'accion' => $request->get('accion'),
            'desde' => $datos['desde'] ?? null,
            'hasta' => $datos['hasta'] ?? null,
        ]);
    }
}
