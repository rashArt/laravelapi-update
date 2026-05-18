<?php

namespace App\Http\Middleware;

use App\Http\Traits\ApiResponse;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegacyTokenAuth
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        // Extraer el token del header Authorization (formato: Bearer <token>)
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse('Token no proporcionado.', 401);
        }

        // Buscar usuario activo con el token proporcionado
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return $this->errorResponse('No autorizado.', 401);
        }

        // Registrar el usuario en el guard de Laravel para disponibilizar auth()->user() y $request->user()
        Auth::login($user);

        return $next($request);
    }
}

