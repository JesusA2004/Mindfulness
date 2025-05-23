<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'matricula'      => 'required|string|max:50|unique:users',
            'email'          => 'required|string|email|max:255|unique:users',
            'password'       => 'required|string|min:6|confirmed',
            'rol'            => 'required|string|in:estudiante,profesor,admin',
            'urlFotoPerfil'  => 'nullable|url',
            'persona_id'     => 'required|string|size:24',
            'estatus'        => 'nullable|string|in:activo,bajaSistema,bajaTemporal',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'           => $request->name,
            'matricula'      => $request->matricula,
            'email'          => $request->email,
            'password'       => bcrypt($request->password),
            'rol'            => $request->rol,
            'urlFotoPerfil'  => $request->urlFotoPerfil,
            'persona_id'     => $request->persona_id,
            'estatus'        => $request->estatus ?? 'activo',
        ]);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user'    => $user
        ], 201);
    }

    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Datos de acceso incorrectos. Por favor, verifica tus credenciales.'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Refrescar token
     */
    public function refresh()
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }

    /**
     * Perfil del usuario autenticado
     */
    public function userProfile()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Formatear respuesta del token
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => auth('api')->setToken($token)->user(),
        ]);
    }
    
}
