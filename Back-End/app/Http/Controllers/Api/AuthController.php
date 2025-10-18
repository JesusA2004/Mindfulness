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
    /**
     * POST /api/auth/register
     */
    public function register(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Valores por defecto / normalización de ObjectId (Mongo)
        $data['estatus']        = $data['estatus']        ?? 'activo';
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

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Datos de acceso incorrectos. Por favor, verifica tus credenciales.'
            ], 401);
        }

        $user = auth('api')->user();

        // Leer el jti generado por jwt-auth (está en required_claims)
        $payload = JWTAuth::setToken($token)->getPayload();
        $jti     = $payload->get('jti');

        // (Opcional) Guardar jti como sesión actual
        try {
            $user->current_jti = $jti;
            $user->save();
        } catch (\Throwable $e) {
            // no-op si tu esquema no tiene este campo
        }

        return $this->createNewToken($token, $jti);
    }

    /**
     * POST /api/auth/refresh
     * Requiere Authorization: Bearer <token actual>
     */
    public function refresh(): JsonResponse
    {
        // auth('api')->refresh() rota el token y, con blacklist_enabled=true,
        // invalida el token anterior. No lanzamos eventos de cierre forzado.
        try {
            $newToken = auth('api')->refresh();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = auth('api')->user();

        // Obtener el nuevo jti del token recién emitido
        $payload = JWTAuth::setToken($newToken)->getPayload();
        $jti     = $payload->get('jti');

        // (Opcional) Actualizar current_jti
        try {
            if ($user) {
                $user->current_jti = $jti;
                $user->save();
            }
        } catch (\Throwable $e) {
            // no-op si tu esquema no tiene este campo
        }

        return $this->createNewToken($newToken, $jti);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if ($user) {
                // (Opcional) limpiar sesión activa almacenada
                $user->current_jti = null;
                $user->save();
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // Blacklistea el token actual (si blacklist_enabled=true)
        auth('api')->logout();

        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }

    /**
     * GET /api/auth/me
     */
    public function userProfile(): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        return response()->json($user);
    }

    /**
     * Estructura estándar de respuesta de token
     */
    protected function createNewToken(string $token, ?string $jti = null): JsonResponse
    {
        // TTL en segundos
        $ttlSeconds = auth('api')->factory()->getTTL() * 60;

        // Para devolver al front el usuario asociado al token recién emitido
        $user = auth('api')->setToken($token)->user();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $ttlSeconds,                                  // ej. 300 si ttl=5
            'expires_at'   => now()->addSeconds($ttlSeconds)->toISOString(), // ISO8601
            'jti'          => $jti,
            'user'         => [
                'name'           => $user->name,
                'matricula'      => $user->matricula ?? null,
                'email'          => $user->email,
                'rol'            => $user->rol ?? null,
                'estatus'        => $user->estatus ?? null,
                'urlFotoPerfil'  => $user->urlFotoPerfil ?? null,
                'persona_id'     => isset($user->persona_id) ? (string) $user->persona_id : null,
                'institucion_id' => isset($user->institucion_id) ? (string) $user->institucion_id : null,
                'puntosCanjeo'   => $user->puntosCanjeo ?? 0,
                'current_jti'    => $user->current_jti ?? $jti,
                'id'             => (string) ($user->_id ?? $user->id),
                'updated_at'     => optional($user->updated_at)->toISOString() ?: null,
                'created_at'     => optional($user->created_at)->toISOString() ?: null,
            ],
        ]);
    }
}
