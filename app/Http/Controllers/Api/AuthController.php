<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /**
     * POST /api/v1/login — HU1: emite un token con expiración (Bearer).
     */
    public function login(Request $request): JsonResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $llave = 'api-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($llave, 5)) {
            return response()->json([
                'message' => 'Demasiados intentos. Intente nuevamente en un minuto.',
            ], 429);
        }

        $usuario = User::where('email', $credenciales['email'])->first();

        if (! $usuario || ! Hash::check($credenciales['password'], $usuario->password) || ! $usuario->estaActivo()) {
            RateLimiter::hit($llave, 60);

            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        RateLimiter::clear($llave);

        $minutos = (int) (config('sanctum.expiration') ?: 120);
        $token = $usuario->createToken('api', [$usuario->role], now()->addMinutes($minutos));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in_minutes' => $minutos,
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'role' => $usuario->role,
            ],
        ], 200);
    }

    /**
     * POST /api/v1/logout — HU2: revoca el token utilizado.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/me — usuario autenticado del token.
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role,
        ], 200);
    }
}
