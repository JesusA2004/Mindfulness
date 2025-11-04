<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\ObjectId;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use MongoDB\BSON\Regex;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     */
    public function register(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Normaliza email en minúsculas
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        // Estatus por defecto
        $data['estatus'] = $data['estatus'] ?? 'activo';

        // persona_id: sólo si viene y es válido como ObjectId de 24 hex
        if (!empty($data['persona_id'])) {
            try {
                $hex = (string) $data['persona_id'];
                $data['persona_id'] = (strlen($hex) === 24) ? new ObjectId($hex) : $data['persona_id'];
            } catch (\Throwable $e) {
                // Si no es un ObjectId válido, lo dejamos tal cual o puedes retornar 422
            }
        }

        // Evita correos duplicados
        if (!empty($data['email']) && User::where('email', $data['email'])->exists()) {
            return response()->json([
                'mensaje' => 'Ya existe un usuario registrado con ese correo.',
                'error'   => 'duplicate_email',
            ], 409);
        }

        // Asegura hash de contraseña (por si el modelo no tiene mutator)
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

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
     * Diferencia entre: correo inexistente (404) y contraseña incorrecta (401).
     */
    public function login(Request $request): JsonResponse
{
    $request->validate([
        'email'    => ['required','email'],
        'password' => ['required','string','min:6'],
    ]);

    $email = mb_strtolower(trim($request->input('email')));
    $plain = (string) $request->input('password');

    // Búsqueda exacta, case-insensitive en Mongo
    $regex = new Regex('^' . preg_quote($email, '/') . '$', 'i');
    $user = User::where('email', $regex)->first();

    if (!$user) {
        return response()->json(['error' => 'No existe un usuario registrado con ese correo.'], 404);
    }

    if (empty($user->password) || !is_string($user->password)) {
        return response()->json(['error' => 'La cuenta no tiene contraseña definida.'], 422);
    }

    // Si quedó en texto plano por legado, re-hash al primer login
    if ($plain === $user->password) {
        $user->password = $plain; // tu mutator lo hashea (no empieza con $2y$)
        $user->save();
    }

    if (!Hash::check($plain, (string) $user->password)) {
        return response()->json(['error' => 'Contraseña incorrecta.'], 401);
    }

    try {
        $token = JWTAuth::fromUser($user);
    } catch (\Throwable $e) {
        return response()->json(['error' => 'No se pudo generar el token de acceso.'], 500);
    }

    $jti = null;
    try {
        $payload = JWTAuth::setToken($token)->getPayload();
        $jti     = $payload->get('jti');
        $user->current_jti = $jti;
        $user->save();
    } catch (\Throwable $e) {
        // no-op
    }

    return $this->createNewToken($token, $jti);
}

    /**
     * POST /api/auth/refresh
     * Requiere Authorization: Bearer <token actual>
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = auth('api')->user();

        try {
            $payload = JWTAuth::setToken($newToken)->getPayload();
            $jti     = $payload->get('jti');

            if ($user) {
                $user->current_jti = $jti;
                $user->save();
            }
        } catch (\Throwable $e) {
            $jti = null;
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
                $user->current_jti = null;
                $user->save();
            }
        } catch (\Throwable $e) {
            // no-op
        }

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
        $ttlSeconds = auth('api')->factory()->getTTL() * 60;
        $user = auth('api')->setToken($token)->user();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $ttlSeconds,
            'expires_at'   => now()->addSeconds($ttlSeconds)->toISOString(),
            'jti'          => $jti,
            'user'         => [
                'name'           => $user->name,
                'matricula'      => $user->matricula ?? null,
                'email'          => $user->email,
                'rol'            => $user->rol ?? null,
                'estatus'        => $user->estatus ?? null,
                'urlFotoPerfil'  => $user->urlFotoPerfil ?? null,
                'persona_id'     => isset($user->persona_id) ? (string) $user->persona_id : null,
                'puntosCanjeo'   => $user->puntosCanjeo ?? 0,
                'current_jti'    => $user->current_jti ?? $jti,
                'id'             => (string) ($user->_id ?? $user->id),
                'updated_at'     => optional($user->updated_at)->toISOString() ?: null,
                'created_at'     => optional($user->created_at)->toISOString() ?: null,
            ],
        ]);
    }
}
