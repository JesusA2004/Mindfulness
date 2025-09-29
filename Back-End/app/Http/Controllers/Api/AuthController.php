<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MongoDB\BSON\ObjectId;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario (requiere persona_id e institucion_id válidos)
     */
    public function register(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Valor por defecto
        $data['estatus'] = $data['estatus'] ?? 'activo';

        // Casteo explícito a ObjectId para evitar problemas de tipo
        $data['institucion_id'] = new ObjectId($data['institucion_id']);
        $data['persona_id']     = new ObjectId($data['persona_id']);

        // El mutator setPasswordAttribute en User ya hashea si viene en texto plano.
        // Si prefieres hashear aquí también es válido: $data['password'] = bcrypt($data['password']);

        try {
            $user = User::create($data);

            return response()->json([
                'mensaje' => 'Usuario registrado correctamente.',
                'usuario' => $user,
            ], 201);
        } catch (\Throwable $e) {
            // Captura validaciones del booted() (ej. institución/persona inexistente) u otros errores
            return response()->json([
                'mensaje' => 'No se pudo registrar el usuario.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Login de usuario
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Datos de acceso incorrectos. Por favor, verifica tus credenciales.'
            ], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Refrescar token
     */
    public function refresh(): JsonResponse
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Cerrar sesión
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }

    /**
     * Perfil del usuario autenticado
     */
    public function userProfile(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Formatear respuesta del token
     */
    protected function createNewToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => auth('api')->setToken($token)->user(),
        ]);
    }
}
