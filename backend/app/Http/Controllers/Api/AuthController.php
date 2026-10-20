<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Hash bcrypt "dummy" para igualar el coste de CPU cuando el usuario no existe.
     * Debe tener el mismo coste (rounds) que config('hashing.bcrypt.rounds').
     */
    private const DUMMY_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $usuario = Usuario::with('rol')
            ->firstWhere('email', $validated['email']);

        // Ejecutamos SIEMPRE Hash::check (con hash dummy si no hay usuario)
        // para evitar fuga de información por timing.
        $passwordOk = Hash::check(
            $validated['password'],
            $usuario?->password ?? self::DUMMY_HASH
        );

        if (! $usuario || ! $passwordOk) {
            // 401 en lugar de 422: es un fallo de autenticación.
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
                'errors'  => [
                    'email' => ['Las credenciales proporcionadas son incorrectas.'],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $usuario->activo) {
            return response()->json([
                'message' => 'La cuenta está desactivada. Contacta al administrador.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Opcional: revocar tokens previos si solo quieres una sesión activa
        // $usuario->tokens()->delete();

        $token = $usuario->createToken('angular-app')->plainTextToken;

        return response()->json([
            'message'    => 'Inicio de sesión exitoso.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $this->userPayload($usuario),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'user' => $this->userPayload($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // Solo los tokens de Sanctum son borrables; los TransientToken
        // (auth por cookie/SPA) no lo son.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    private function userPayload(Usuario $usuario): array
    {
        return [
            'id'       => $usuario->id,
            'nombre'   => $usuario->nombre,
            'email'    => $usuario->email,
            'rol'      => $usuario->rol?->nombre,
            'rol_id'   => $usuario->rol_id,
        ];
    }
}