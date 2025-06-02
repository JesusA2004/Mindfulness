<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario
     */
    public function register(UserRequest $request)
    {
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
            'mensaje' => 'Usuario registrado correctamente',
            'usuario'    => $user
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
