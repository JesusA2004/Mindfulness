<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MongoDB\BSON\ObjectId;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['estatus'] = $data['estatus'] ?? 'activo';
        $data['institucion_id'] = new ObjectId($data['institucion_id']);
        $data['persona_id']     = new ObjectId($data['persona_id']);

        try {
            $user = User::create($data);
            return response()->json([
                'mensaje' => 'Usuario registrado correctamente.',
                'usuario' => $user,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'mensaje' => 'No se pudo registrar el usuario.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Datos de acceso incorrectos. Por favor, verifica tus credenciales.'], 401);
        }

        // Generar un JTI único y reemitir el token con ese claim
        $user = auth('api')->user();
        $jti = (string) Str::uuid();

        $customClaims = ['jti' => $jti];
        $token = JWTAuth::claims($customClaims)->fromUser($user);

        // Guardar el jti como sesión actual
        $user->current_jti = $jti;
        $user->save();

        return $this->createNewToken($token, $jti);
    }

    public function refresh(): JsonResponse
    {
        // Refrescar conserva claims por defecto. Volvemos a emitir con un jti nuevo
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $jti = (string) Str::uuid();
        $token = JWTAuth::claims(['jti' => $jti])->fromUser($user);

        // invalidar sesión anterior para “sesión única”
        if (!empty($user->current_jti) && $user->current_jti !== $jti) {
            event(new ForcedLogout($user->_id ?? $user->id, 'refresh'));
        }

        $user->current_jti = $jti;
        $user->save();

        return $this->createNewToken($token, $jti);
    }

    public function logout(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if ($user) {
                $user->current_jti = null; // limpiar sesión activa
                $user->save();
            }
        } catch (\Throwable $e) {
            // no-op
        }

        auth('api')->logout();
        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }

    public function userProfile(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    protected function createNewToken(string $token, ?string $jti = null): JsonResponse
    {
        $ttlSeconds = auth('api')->factory()->getTTL() * 60; // en segundos
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $ttlSeconds,
            'expires_at'   => now()->addSeconds($ttlSeconds)->toISOString(),
            'jti'          => $jti,
            'user'         => auth('api')->setToken($token)->user(),
        ]);
    }
}
