<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\UserController;

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

    // Rutas CRUD protegidas por JWT
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('users',                UserController::class);

    });
