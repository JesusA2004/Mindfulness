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
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Api\ActividadesAsignadasController;
use App\Http\Controllers\Api\ReporteController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (no requieren login)
|--------------------------------------------------------------------------
| - Bootstrap inicial del sistema.
| - Registro/login/refresh siguen siendo públicos (refresh requiere token válido).
*/

// Autenticación
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // Registro de usuario
    Route::post('login',    [AuthController::class, 'login']);    // Login y obtención de token
    Route::post('refresh',  [AuthController::class, 'refresh']);  // Refrescar token

    // Rutas protegidas bajo /auth (requieren token)
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout',      [AuthController::class, 'logout']);
        Route::get('user-profile', [AuthController::class, 'userProfile']);
    });
});

// Registro de persona SIN autenticación (necesario antes de registrar user)
Route::post('personas', [PersonaController::class, 'store']);

Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
Route::post('/password/reset',  [PasswordResetController::class, 'reset']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas por JWT (requieren token válido)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->group(function () {

    // Personas (todas excepto store que fue pública)
    Route::apiResource('personas', PersonaController::class)->except(['store']);

    // CRUD de los demás modelos
    Route::apiResource('users', UserController::class);
    Route::prefix('actividades')->group(function () {
        Route::get('mis-cohortes', [ActividadController::class, 'misCohortes']);
        Route::get('mis-alumnos',  [ActividadController::class, 'misAlumnos']);
    });

    Route::apiResource('actividades', ActividadController::class); 
    Route::patch('actividades/{id}/estado', [ActividadController::class, 'patchEstado']);

    Route::apiResource('bitacoras', BitacoraController::class);
    Route::post('/bitacoras/remind-today', [BitacoraController::class, 'remindToday']);
    
    Route::apiResource('citas', CitaController::class);
    Route::patch('citas/{id}/estado', [CitaController::class, 'updateEstado']);

    Route::apiResource('encuestas', EncuestaController::class);
    Route::apiResource('recompensas', RecompensaController::class);
    Route::apiResource('tecnicas', TecnicaController::class);
    Route::apiResource('tests', TestController::class);

    Route::prefix('alumno')->group(function () {
        Route::get('/actividades', [ActividadesAsignadasController::class, 'index']);
    });

    // Rutas especiales (acciones de responder)
    Route::put('encuestas/{encuesta}/responder', [EncuestaController::class, 'responder']);
    Route::put('tests/{test}/responder',         [TestController::class, 'responder']);

    // Subidas
    Route::post('/uploads', [UploadController::class, 'store']);

    // Reportes (solo consulta). Opcional: agrega tu middleware de rol/policy si aplica.
    // ->middleware('can:reportes.consultar')  // ejemplo con Gate/Policy
    Route::prefix('reportes')->group(function () {
        Route::get('/top-tecnicas',            [ReporteController::class, 'topTecnicas']);
        Route::get('/actividades-por-alumno',  [ReporteController::class, 'actividadesPorAlumno']);
        Route::get('/citas-por-alumno',        [ReporteController::class, 'citasPorAlumno']);
        Route::get('/bitacoras-por-alumno',    [ReporteController::class, 'bitacorasPorAlumno']);
        Route::get('/encuestas-resultados',    [ReporteController::class, 'encuestasResultados']);
        Route::get('/recompensas-canjeadas',   [ReporteController::class, 'recompensasCanjeadas']);

        // Descargas
        Route::get('/export', [ReporteController::class, 'export']); 

        Route::get('/suggest-alumnos', [ReporteController::class, 'suggestAlumnos']);
    });

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

    /*
    |--------------------------------------------------------------------------
    | Dashboard - Admin (compatibilidad con lo que ya tenías)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/overview',         [DashboardController::class, 'overview']);         // Tarjetas KPI admin
        Route::get('/bitacoras-por-mes',[DashboardController::class, 'bitacorasPorMes']);  // Gráfica barras admin
    });


    /*
    |--------------------------------------------------------------------------
    | Dashboard - General + Profesor (nuevo, para el front actual)
    |--------------------------------------------------------------------------
    | /api/dashboard/overview                         -> Admin/general
    | /api/dashboard/profesor/overview                -> Hoy + cohortes + alumnosCargo
    | /api/dashboard/profesor/calendario              -> Citas del mes o fallback actividades
    | /api/dashboard/profesor/actividades-por-grupo   -> Conteo de actividades por cohorte
    */
    Route::prefix('dashboard')->group(function () {

        // General / Admin (mismo método que arriba, solo otra ruta de acceso)
        Route::get('/overview', [DashboardController::class, 'overview']);

        // Profesor
        Route::prefix('profesor')->group(function () {
            Route::get('/overview',               [DashboardController::class, 'profesorOverview']);
            Route::get('/calendario',             [DashboardController::class, 'profesorCalendario']);
            Route::get('/actividades-por-grupo',  [DashboardController::class, 'profesorActividadesPorGrupo']);

        });


        Route::prefix('alumno')->group(function () {
            Route::get('/overview',     [DashboardController::class, 'alumnoOverview']);
            Route::get('/bienestar',    [DashboardController::class, 'alumnoBienestarSemanal']);
            Route::get('/asignaciones', [DashboardController::class, 'alumnoAsignaciones']);
        });
        
    });
});
