<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureValidSession
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $jti = $payload->get('jti');
            $user = auth('api')->user();

            if (!$user || !$jti) {
                return response()->json(['error' => 'No autenticado'], 401);
            }

            // Si el jti del token NO coincide con el current_jti del usuario => sesión revocada
            if (!empty($user->current_jti) && $user->current_jti !== $jti) {
                return response()->json(['error' => 'session_revoked'], 401);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        return $next($request);
    }
}
