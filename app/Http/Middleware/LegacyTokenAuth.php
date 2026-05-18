<?php

namespace App\Http\Middleware;

use App\Http\Traits\ApiResponse;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

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

        // Inyectar ID del usuario autenticado en el request para uso en controladores
        $request->merge(['auth_user_id' => $user->id]);

        return $next($request);
    }
}

