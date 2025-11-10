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
use App\Http\Controllers\Api\UserPointsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DashboardAlumnoController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Api\ActividadesAsignadasController;
use App\Http\Controllers\Api\BaseReportController;
use App\Http\Controllers\Api\ContactoController;
use App\Http\Controllers\Api\TestRespuestaController;
use App\Http\Controllers\Api\CitaAlumnoController;
use App\Http\Controllers\Api\Reportes\TopTecnicasController;
use App\Http\Controllers\Api\Reportes\ActividadesAlumnoController;
use App\Http\Controllers\Api\Reportes\CitasAlumnoController;
use App\Http\Controllers\Api\Reportes\BitacorasAlumnoController;
use App\Http\Controllers\Api\Reportes\EncuestasResultadosController;
use App\Http\Controllers\Api\Reportes\RecompensasCanjeadasController;
use App\Http\Controllers\Api\Reportes\ExportController;
use App\Http\Controllers\Api\RestoreController;

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

Route::post('/contacto', [ContactoController::class, 'store']);

// Filtros de reportes
Route::get('reportes/opciones/cohortes', [TopTecnicasController::class, 'cohortes']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas por JWT (requieren token válido)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->group(function () {

    // Citas para alumno (solo sus propias citas)
    Route::prefix('alumno')->group(function () {
        Route::get('citas',  [CitaAlumnoController::class, 'index']);
        Route::post('citas', [CitaAlumnoController::class, 'store']);
    });

    // Personas (todas excepto store que fue pública)
    Route::apiResource('personas', PersonaController::class)->except(['store']);

    // Respuestas de tests
    Route::get('/tests/{id}/respuestas', [TestRespuestaController::class, 'index']);

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

    // Reportes (solo consulta).
    Route::prefix('reportes')->group(function () {

        // 1) Top técnicas
        Route::get('/top-tecnicas', [TopTecnicasController::class, 'index']);

        // 2) Actividades por alumno
        Route::get('/actividades-por-alumno', [ActividadesAlumnoController::class, 'index']);

        // 3) Citas por alumno
        Route::get('/citas-por-alumno', [CitasAlumnoController::class, 'index']);

        // 4) Bitácoras por alumno
        Route::get('/bitacoras-por-alumno', [BitacorasAlumnoController::class, 'index']);

        // 5) Resultados de encuestas
        Route::get('/encuestas-resultados', [EncuestasResultadosController::class, 'index']);

        // 6) Recompensas canjeadas
        Route::get('/recompensas-canjeadas', [RecompensasCanjeadasController::class, 'index']);

        // Exportador universal (pdf|excel)
        // Ej: /api/reportes/export?reporte=top-tecnicas&tipo=pdf
        Route::get('/export', [ExportController::class, 'export']);
    });

    // Backups
    Route::get('backups/export', [BackupController::class, 'export']);

    //Resturacion de base de datos
    Route::post('/restore', 
    [RestoreController::class, 'import']
    );

    // Puntos de usuario
    Route::post('/users/{id}/points/earn',   [UserPointsController::class, 'earn']);
    Route::post('/users/{id}/points/redeem', [UserPointsController::class, 'redeem']);
    Route::get('/users/{id}/points',         [UserPointsController::class, 'getPoints']);

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

        // ===== Alumno con controlador dedicado =====
        Route::prefix('alumno')->group(function () {
            Route::get('/overview',     [DashboardAlumnoController::class, 'overview']);
            Route::get('/bienestar',    [DashboardAlumnoController::class, 'bienestarSemanal']);
            Route::get('/asignaciones', [DashboardAlumnoController::class, 'asignaciones']);
        });
        
    });
});
