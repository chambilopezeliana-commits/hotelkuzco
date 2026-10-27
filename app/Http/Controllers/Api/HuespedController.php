<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Huesped;
use App\Rules\SoloLetras;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HuespedController extends Controller
{
    /**
     * POST /api/v1/guests — HU8/HU9: registra un huésped sin duplicar
     * el documento (tipo + número) y con historial único.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'tipo_documento' => strtoupper((string) $request->input('tipo_documento', 'DNI')),
            'documento' => trim((string) $request->input('documento', '')),
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', new SoloLetras],
            'tipo_documento' => ['required', 'string', 'max:20', Rule::in(Huesped::TIPOS_DOCUMENTO)],
            'documento' => ['required', 'string', 'max:30', 'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s\-\/\.]+$/'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ]);

        $telefono = $datos['telefono'] ?? $datos['phone'] ?? null;
        unset($datos['phone'], $datos['telefono']);
        $datos['telefono'] = $telefono;

        $duplicado = Huesped::where('tipo_documento', $datos['tipo_documento'])
            ->where('documento', $datos['documento'])
            ->whereNull('deleted_at')
            ->exists();

        if ($duplicado) {
            return response()->json([
                'message' => 'Ya existe un huésped con ese documento (tipo y número).',
            ], 409);
        }

        try {
            $huesped = Huesped::create($datos);
        } catch (QueryException $e) {
            if (
                in_array((string) $e->getCode(), ['23000', '23505'], true)
                && str_contains(strtolower($e->getMessage()), 'documento')
            ) {
                return response()->json([
                    'message' => 'Ya existe un huesped con ese documento (tipo y numero).',
                ], 409);
            }

            throw $e;
        }

        Auditoria::registrar('crear', 'huesped', $huesped->id, 'Huésped registrado vía API', [], $huesped->toArray());

        return response()->json([
            'id' => $huesped->id,
            'document_type' => $huesped->tipo_documento,
            'document_number' => $huesped->documento,
            'name' => $huesped->nombre,
            'phone' => $huesped->telefono,
            'email' => $huesped->email,
        ], 201);
    }
}
