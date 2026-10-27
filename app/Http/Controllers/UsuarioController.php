<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use App\Repositories\UsuarioRepository;
use App\Rules\SoloLetras;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioRepository $usuarios) {}

    public function index(Request $request): View
    {
        return view('usuarios.index', [
            'usuarios' => $this->usuarios->listar($request->get('buscar')),
            'buscar' => $request->get('buscar'),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $this->usuarios->crear($this->validar($request));
        Auditoria::registrar('crear', 'usuario', $usuario->id, 'Usuario creado', [], $usuario->toArray());

        return redirect()->route('usuarios.index')->with('exito', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        if (
            $usuario->role === User::ROLE_ADMIN
            && ($datos['role'] ?? $usuario->role) !== User::ROLE_ADMIN
            && ! User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('activo', true)
                ->whereKeyNot($usuario->id)
                ->exists()
        ) {
            return back()->withInput()->with('error', 'No se puede cambiar el rol del ultimo administrador activo.');
        }

        $antes = $usuario->toArray();
        $usuario = $this->usuarios->actualizar($usuario, $datos);

        if (array_key_exists('password', $datos)) {
            $usuario->tokens()->delete();
        }

        Auditoria::registrar('actualizar', 'usuario', $usuario->id, 'Usuario actualizado', $antes, $usuario->toArray());

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puede eliminar su propio usuario.');
        }

        // Validación previa: el usuario no debe tener dependencias activas (cobros registrados).
        if ($usuario->cobros()->withTrashed()->exists()) {
            return back()->with('error', 'No se puede eliminar: el usuario tiene cobros registrados.');
        }

        $antes = $usuario->toArray();

        // Baja lógica: además de Soft Deletes se desocupan las claves únicas
        // y se revocan credenciales para liberar el email y bloquear el acceso.
        $emailEliminado = $this->emailEliminado($usuario);

        DB::transaction(function () use ($usuario, $emailEliminado) {
            $usuario->update([
                'email' => $emailEliminado,
                'password' => Str::random(40),
                'remember_token' => null,
                'activo' => false,
            ]);

            $usuario->tokens()->delete();
            $this->usuarios->eliminar($usuario);
        });
        Auditoria::registrar('eliminar', 'usuario', $usuario->id, 'Usuario eliminado (baja lógica)', $antes, []);

        return redirect()->route('usuarios.index')->with('exito', 'Usuario eliminado correctamente.');
    }

    public function toggleEstado(Request $request, User $usuario): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puede cambiar el estado de su propio usuario.');
        }

        $usuario->activo = ! $usuario->activo;

        if (! $usuario->activo) {
            $usuario->remember_token = null;
            $usuario->tokens()->delete();
        }

        $usuario->save();

        $accion = $usuario->activo ? 'activar' : 'desactivar';
        Auditoria::registrar($accion, 'usuario', $usuario->id, 'Usuario '.$accion, [], ['activo' => $usuario->activo]);

        return back()->with('exito', 'Estado del usuario actualizado correctamente.');
    }

    private function emailEliminado(User $usuario): string
    {
        do {
            $email = 'user-'.$usuario->id.'-'.Str::lower(Str::random(8)).'@eliminado.invalid';
        } while (User::withTrashed()->where('email', $email)->exists());

        return $email;
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $password = $usuario ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'];

        return $request->validate([
            'name' => ['required', 'string', 'max:100', new SoloLetras],
            'email' => ['required', 'email', Rule::unique('users', 'email')
                ->whereNull('deleted_at')
                ->ignore($usuario?->id)],
            'role' => ['required', 'in:administrador,recepcionista'],
            'password' => $password,
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'role.in' => 'Rol no válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);
    }
}
