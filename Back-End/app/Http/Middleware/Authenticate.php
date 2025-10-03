<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Si la petición es de la API o espera JSON, no redirige: responderá 401
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        // Si tuvieras login web con vistas, aquí podrías mantener la redirección:
        // return route('login');

        // Como no tienes ruta login definida, devolvemos null siempre
        return null;
    }
}
