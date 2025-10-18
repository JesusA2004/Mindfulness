<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\BackupController;
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
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserPointsController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (no requieren login)
|--------------------------------------------------------------------------
| - Bootstrap inicial del sistema.
| - Registro/login/refresh siguen siendo públicos (refresh requiere token válido pero NO valid.session).
*/

// Autenticación
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // Registro de usuario
    Route::post('login',    [AuthController::class, 'login']);    // Login y obtención de token
    Route::post('refresh',  [AuthController::class, 'refresh']);  // Refrescar token (sin valid.session)

    // Rutas protegidas bajo /auth (requieren token + sesión vigente)
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout',      [AuthController::class, 'logout']);
        Route::get('user-profile', [AuthController::class, 'userProfile']);
    });
});

// Registro de persona SIN autenticación (necesario antes de registrar user)
Route::post('personas', [PersonaController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas por JWT (requieren token válido y sesión vigente)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->group(function () {
    // Personas (todas excepto store que fue pública)
    Route::apiResource('personas', PersonaController::class)->except(['store']);

    // CRUD de los demás modelos
    Route::apiResource('users', UserController::class);
    Route::apiResource('actividads', ActividadController::class);
    Route::apiResource('bitacoras', BitacoraController::class);
    Route::apiResource('citas', CitaController::class);
    Route::apiResource('encuestas', EncuestaController::class);
    Route::apiResource('recompensas', RecompensaController::class);
    Route::apiResource('tecnicas', TecnicaController::class);
    Route::apiResource('tests', TestController::class);

    // Rutas especiales
    Route::put('encuestas/{encuesta}/responder', [EncuestaController::class, 'responder']);
    Route::put('tests/{test}/responder',         [TestController::class, 'responder']);

    // Subidas
    Route::post('/uploads', [UploadController::class, 'store']);

    // Backups
    Route::get('backups/export', [BackupController::class, 'export']);
    Route::post('backups/import', [BackupController::class, 'import']);

    // Puntos de usuario
    Route::post('/users/{id}/points/earn',   [UserPointsController::class, 'earn']);
    Route::post('/users/{id}/points/redeem', [UserPointsController::class, 'redeem']);
    Route::get('/users/{id}/points',         [UserPointsController::class, 'getPoints']);

    // Subida de foto (protegida)
    Route::post('/subir-foto', function (Request $request) {
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('public/fotos');
            $url  = Storage::url($path); // /storage/fotos/xxx.jpg
            return response()->json(['url' => asset($url)], 200);
        }
        return response()->json(['error' => 'No se recibió archivo'], 400);
    });
});
