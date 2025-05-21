<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\UserController;
    use App\Http\Controllers\Api\ActividadController;
    use App\Http\Controllers\Api\BitacoraController;
    use App\Http\Controllers\Api\CitaController;
    use App\Http\Controllers\Api\EncuestaController;
    use App\Http\Controllers\Api\PersonaController;
    use App\Http\Controllers\Api\RecompensaController;
    use App\Http\Controllers\Api\TecnicaController;
    use App\Http\Controllers\Api\TestController;

    // Rutas de autenticación
    Route::prefix('auth')->group(function () {

        Route::post('register', [AuthController::class, 'register']);    // Registro de usuario
        Route::post('login',    [AuthController::class, 'login']);       // Login y obtención de token
        Route::post('refresh',  [AuthController::class, 'refresh']);     // Refrescar token

        // Rutas protegidas (necesitan token válido)
        Route::middleware('auth:api')->group(function () {
            Route::post('logout',      [AuthController::class, 'logout']);
            Route::get('user-profile', [AuthController::class, 'userProfile']);
        });
        
    });

    // Rutas protegidas por JWT
    Route::middleware('auth:api')->group(function () {

        // Rutas CRUD para los modelos
        Route::apiResource('users', UserController::class);
        Route::apiResource('actividads', ActividadController::class);
        Route::apiResource('bitacoras', BitacoraController::class);
        Route::apiResource('citas', CitaController::class);
        Route::apiResource('encuestas', EncuestaController::class);
        Route::apiResource('personas', PersonaController::class);
        Route::apiResource('recompensas', RecompensaController::class);
        Route::apiResource('tecnicas', TecnicaController::class);
        Route::apiResource('tests', TestController::class);
        
        Route::post('/subir-foto', function (Request $request) {
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('public/fotos');
                $url = Storage::url($path); // Devuelve /storage/fotos/xxx.jpg
                return response()->json(['url' => asset($url)], 200);
            }
            return response()->json(['error' => 'No se recibió archivo'], 400);
        });
        
    });
