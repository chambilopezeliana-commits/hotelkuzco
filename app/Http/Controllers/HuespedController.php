<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Huesped;
use App\Repositories\HuespedRepository;
use App\Rules\SoloLetras;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HuespedController extends Controller
{
    public function __construct(private readonly HuespedRepository $huespedes) {}

    public function index(Request $request): View
    {
        return view('huespedes.index', [
            'huespedes' => $this->huespedes->listar($request->get('buscar')),
            'buscar' => $request->get('buscar'),
        ]);
    }

    public function create(): View
    {
        return view('huespedes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $huesped = $this->huespedes->crear($datos);
        Auditoria::registrar('crear', 'huesped', $huesped->id, 'Huésped registrado', [], $huesped->toArray());

        return redirect()->route('huespedes.index')->with('exito', 'Huésped registrado correctamente.');
    }

    public function edit(Huesped $huesped): View
    {
        return view('huespedes.edit', compact('huesped'));
    }

    public function update(Request $request, Huesped $huesped): RedirectResponse
    {
        $datos = $this->validar($request, $huesped);
        $antes = $huesped->toArray();
        $huesped = $this->huespedes->actualizar($huesped, $datos);
        Auditoria::registrar('actualizar', 'huesped', $huesped->id, 'Huésped actualizado', $antes, $huesped->toArray());

        return redirect()->route('huespedes.index')->with('exito', 'Huésped actualizado correctamente.');
    }

    public function destroy(Huesped $huesped): RedirectResponse
    {
        // Validación previa: no eliminar huéspedes con relaciones activas (reservas en curso).
        if ($this->huespedes->tieneReservas($huesped->id)) {
            return back()->with('error', 'No se puede eliminar: el huésped tiene reservas registradas en el historial.');
        }

        $antes = $huesped->toArray();

        // Baja lógica: se desocupa el documento único (tipo_documento + documento)
        // para permitir registrar un nuevo huésped con ese documento.
        $documentoEliminado = $this->documentoEliminado($huesped);

        DB::transaction(function () use ($huesped, $documentoEliminado) {
            $huesped->update(['documento' => $documentoEliminado]);
            $this->huespedes->eliminar($huesped);
        });
        Auditoria::registrar('eliminar', 'huesped', $huesped->id, 'Huésped eliminado (baja lógica)', $antes, []);

        return redirect()->route('huespedes.index')->with('exito', 'Huésped eliminado correctamente.');
    }

    private function documentoEliminado(Huesped $huesped): string
    {
        do {
            $documento = 'ELIM-'.$huesped->id.'-'.Str::upper(Str::random(6));
        } while (Huesped::withTrashed()
            ->where('tipo_documento', $huesped->tipo_documento)
            ->where('documento', $documento)
            ->exists());

        return $documento;
    }

    private function validar(Request $request, ?Huesped $huesped = null): array
    {
        $request->merge([
            'tipo_documento' => strtoupper((string) $request->input('tipo_documento', 'DNI')),
            'documento' => trim((string) $request->input('documento', '')),
        ]);

        $reglasDocumento = [
            'required',
            'string',
            'max:30',
            'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s\-\/\.]+$/',
        ];

        if ($huesped) {
            $reglasDocumento[] = function ($attribute, $value, $fail) use ($request, $huesped) {
                $existe = Huesped::where('tipo_documento', $request->input('tipo_documento'))
                    ->where('documento', $value)
                    ->where('id', '!=', $huesped->id)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($existe) {
                    $fail('Ya existe un huésped con ese documento.');
                }
            };
        } else {
            $reglasDocumento[] = function ($attribute, $value, $fail) use ($request) {
                $existe = Huesped::where('tipo_documento', $request->input('tipo_documento'))
                    ->where('documento', $value)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($existe) {
                    $fail('Ya existe un huésped con ese documento.');
                }
            };
        }

        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', new SoloLetras],
            'tipo_documento' => ['required', 'string', 'max:20', Rule::in(Huesped::TIPOS_DOCUMENTO)],
            'documento' => $reglasDocumento,
            'email' => ['nullable', 'email', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento no es válido.',
            'documento.required' => 'El documento es obligatorio.',
            'documento.regex' => 'El número de documento admite letras, números, espacios y los signos - . /',
            'telefono.regex' => 'El teléfono solo admite números, espacios y los signos + - ( ).',
        ]);
    }
}
