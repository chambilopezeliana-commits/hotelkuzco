<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Huesped;
use App\Repositories\HuespedRepository;
use App\Rules\SoloLetras;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HuespedImportController extends Controller
{
    public function __construct(private readonly HuespedRepository $huespedes) {}

    public function formulario(Request $request): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        return view('huespedes.importar');
    }

    public function importar(Request $request): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
            'accion' => ['required', 'in:importar,depurar'],
        ]);

        $archivo = $request->file('archivo');
        $contenido = file_get_contents($archivo->getRealPath());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $lineas = preg_split('/\R/', $contenido) ?: [];

        if (count($lineas) === 0) {
            return back()->with('error', 'El archivo CSV está vacío.');
        }

        $encabezados = str_getcsv(array_shift($lineas), ',', '"', '\\');
        $columnasEsperadas = ['nombre', 'tipo_documento', 'documento', 'email', 'telefono', 'direccion'];
        $mapa = [];

        foreach ($columnasEsperadas as $col) {
            $indice = array_search($col, $encabezados);
            if ($indice === false) {
                $indice = array_search(strtolower($col), array_map('strtolower', $encabezados));
            }
            $mapa[$col] = $indice === false ? null : $indice;
        }

        $insertados = 0;
        $actualizados = 0;
        $omitidos = 0;
        $nuevos = 0;
        $errores = [];
        $depurados = [];

        try {
            DB::beginTransaction();

            foreach ($lineas as $i => $linea) {
                $linea = trim($linea);
                if ($linea === '') {
                    continue;
                }

                $fila = str_getcsv($linea, ',', '"', '\\');

                $datos = [];
                foreach ($mapa as $col => $idx) {
                    $datos[$col] = $idx !== null && isset($fila[$idx]) ? trim((string) $fila[$idx]) : null;
                }

                if (empty($datos['nombre']) || empty($datos['documento'])) {
                    $omitidos++;

                    continue;
                }

                if (empty($datos['tipo_documento'])) {
                    $datos['tipo_documento'] = 'DNI';
                }

                $datos['tipo_documento'] = strtoupper($datos['tipo_documento']);
                $datos['documento'] = trim((string) $datos['documento']);

                $val = Validator::make($datos, [
                    'nombre' => ['required', 'string', 'max:100', new SoloLetras],
                    'tipo_documento' => ['required', 'string', 'max:20', Rule::in(Huesped::TIPOS_DOCUMENTO)],
                    'documento' => ['required', 'string', 'max:30', 'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s\-\/\.]+$/'],
                    'email' => ['nullable', 'email', 'max:100'],
                    'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
                    'direccion' => ['nullable', 'string', 'max:200'],
                ], [
                    'tipo_documento.in' => 'El tipo de documento no es válido (use DNI, CI, PASAPORTE, CE o NIT).',
                    'documento.regex' => 'El número de documento admite letras, números, espacios y los signos - . /',
                    'telefono.regex' => 'El teléfono solo admite números, espacios y los signos + - ( ).',
                ]);

                if ($val->fails()) {
                    $errores[] = 'Línea '.($i + 2).': '.implode(', ', $val->errors()->all());
                    $omitidos++;

                    continue;
                }

                $existente = $this->huespedes->porDocumento($datos['tipo_documento'], $datos['documento']);

                if ($existente) {
                    $depurados[] = [
                        'id' => $existente->id,
                        'nombre' => $existente->nombre,
                        'tipo_documento' => $existente->tipo_documento,
                        'documento' => $existente->documento,
                        'email' => $existente->email,
                    ];
                    if ($request->get('accion') === 'importar') {
                        $existente->fill($datos);
                        $existente->save();
                        $actualizados++;
                    } else {
                        $omitidos++;
                    }

                    continue;
                }

                $nuevos++;

                if ($request->get('accion') === 'importar') {
                    $huesped = $this->huespedes->crear($datos);
                    $insertados++;
                    Auditoria::registrar('crear', 'huesped', $huesped->id, 'Huésped importado', [], $huesped->toArray());
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error durante la importación: '.$e->getMessage());
        }

        $mensaje = 'Importación finalizada: '.$nuevos.' nuevos, '.$actualizados.' actualizados, '.$omitidos.' omitidos.';
        if (! empty($errores)) {
            $mensaje .= ' Errores: '.count($errores).'.';
        }

        Auditoria::registrar('actualizar', 'huesped', null, 'Importación de huéspedes desde CSV', [], [
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'omitidos' => $omitidos,
        ]);

        return back()->with('exito', $mensaje)->with('errores', $errores)->with('depurados', $depurados);
    }
}
