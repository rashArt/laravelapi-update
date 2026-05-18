<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Verificar que el correo corresponde a un usuario registrado
        if (!$user) {
            return $this->validationErrorResponse(['email' => ['El correo electrónico no está registrado.']]);
        }

        // Verificar que la contraseña proporcionada es correcta
        if (!Hash::check($request->password, $user->password)) {
            return $this->validationErrorResponse(['password' => ['La contraseña es incorrecta.']]);
        }

        // Generar nuevo token y persistirlo en la base de datos
        $token = Str::random(60);
        $user->update(['api_token' => $token]);

        return $this->successResponse([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->auth_user_id);

        return $this->successResponse([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Invalidar el token eliminándolo del registro del usuario
        User::where('id', $request->auth_user_id)->update(['api_token' => null]);

        return $this->successResponse();
    }
}

