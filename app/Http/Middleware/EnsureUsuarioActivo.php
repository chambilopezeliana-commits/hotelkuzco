<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->estaActivo()) {
            if ($request->is('api/*')) {
                $token = $usuario->currentAccessToken();

                if ($token instanceof PersonalAccessToken) {
                    $token->delete();
                }

                return response()->json(['message' => 'Su cuenta se encuentra desactivada.'], 401);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Su cuenta se encuentra desactivada.');
        }

        return $next($request);
    }
}
