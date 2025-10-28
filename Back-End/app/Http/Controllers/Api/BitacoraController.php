<?php

namespace App\Http\Controllers\Api;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\BitacoraRequest;
use App\Http\Resources\BitacoraResource;
use Carbon\Carbon;
use App\Events\BitacoraRecordatorio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BitacoraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tz   = config('app.timezone', 'America/Mexico_City');
        $now  = Carbon::now($tz);

        $mes  = $request->query('mes');
        $anio = $request->query('anio');

        $alumnoId = auth()->id(); // ← ID desde la sesión/JWT

        $query = Bitacora::where('alumno_id', $alumnoId)
            ->orderBy('fecha', 'desc')
            ->orderBy('_id', 'desc');

        if ($anio && $mes) {
            $inicio = Carbon::createFromDate((int)$anio, (int)$mes, 1, $tz)->toDateString();
            $fin    = Carbon::createFromDate((int)$anio, (int)$mes, 1, $tz)->endOfMonth()->toDateString();
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif ($anio && !$mes) {
            $inicio = Carbon::createFromDate((int)$anio, 1, 1, $tz)->toDateString();
            $fin    = Carbon::createFromDate((int)$anio, 12, 31, $tz)->toDateString();
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif (!$anio && $mes) {
            $inicio = Carbon::createFromDate($now->year, (int)$mes, 1, $tz)->toDateString();
            $fin    = Carbon::createFromDate($now->year, (int)$mes, 1, $tz)->endOfMonth()->toDateString();
            $query->whereBetween('fecha', [$inicio, $fin]);
        }

        $bitacoras = $query->get();

        $bitacorasFormateadas = $bitacoras->map(function ($b) {
            $fecha = Carbon::parse($b->fecha);
            return [
                'id'          => $b->_id ?? $b->id,
                'titulo'      => $b->titulo,
                'descripcion' => $b->descripcion,
                'fecha'       => $fecha->toDateString(),
                'dia'         => (int) $fecha->day,
                'mes'         => (int) $fecha->month,
                'anio'        => (int) $fecha->year,
                'alumno_id'   => $b->alumno_id,
                'created_at'  => $b->created_at,
                'updated_at'  => $b->updated_at,
            ];
        });

        return response()->json([
            'hoy'       => (int) $now->day,
            'mes'       => (int) ($mes ?? $now->month),
            'anio'      => (int) ($anio ?? $now->year),
            'bitacoras' => $bitacorasFormateadas,
        ], 200);
    }

    public function store(BitacoraRequest $request): JsonResponse
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $data = $request->validated();
        $data['alumno_id'] = auth()->id();

        // ⚙️ Normalizar la fecha entrante a la zona de la app y a Y-m-d (por si viene con hora o TZ)
        if (!empty($data['fecha'])) {
            try {
                $data['fecha'] = Carbon::parse($data['fecha'], $tz)->toDateString();
            } catch (\Throwable $e) {
                // Si no se puede parsear, forzamos a hoy (mejor opción: validar en el FormRequest)
                $data['fecha'] = Carbon::now($tz)->toDateString();
            }
        } else {
            $data['fecha'] = Carbon::now($tz)->toDateString();
        }

        // Buscar si existe registro para (alumno, fecha)
        $bitacora = Bitacora::firstOrNew([
            'alumno_id' => $data['alumno_id'],
            'fecha'     => $data['fecha'],
        ]);

        $wasNew = !$bitacora->exists;

        $bitacora->fill($data)->save();

        return response()->json([
            'mensaje'  => 'Bitácora emocional guardada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
            'awarded'  => $wasNew, // abonar puntos solo si es la primera del día
        ], 201);
    }

    public function show(Bitacora $bitacora): JsonResponse
    {
        $fecha = Carbon::parse($bitacora->fecha);

        return response()->json([
            'bitacora' => [
                'id'          => $bitacora->_id ?? $bitacora->id,
                'titulo'      => $bitacora->titulo,
                'descripcion' => $bitacora->descripcion,
                'fecha'       => $fecha->toDateString(),
                'dia'         => (int) $fecha->day,
                'mes'         => (int) $fecha->month,
                'anio'        => (int) $fecha->year,
                'alumno_id'   => $bitacora->alumno_id,
                'created_at'  => $bitacora->created_at,
                'updated_at'  => $bitacora->updated_at,
            ],
        ], 200);
    }

    public function update(BitacoraRequest $request, Bitacora $bitacora): JsonResponse
    {
        if ($bitacora->alumno_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $tz = config('app.timezone', 'America/Mexico_City');
        $data = $request->validated();

        // ⚙️ Si actualizan la fecha, también normalizamos
        if (!empty($data['fecha'])) {
            try {
                $data['fecha'] = Carbon::parse($data['fecha'], $tz)->toDateString();
            } catch (\Throwable $e) {
                $data['fecha'] = Carbon::now($tz)->toDateString();
            }
        }

        $bitacora->update($data);

        return response()->json([
            'mensaje'  => 'Bitácora emocional actualizada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
        ], 200);
    }

    public function destroy(Bitacora $bitacora): JsonResponse
    {
        if ($bitacora->alumno_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $bitacora->delete();

        return response()->json([
            'mensaje' => 'Bitácora emocional eliminada correctamente.',
        ], 200);
    }

    /**
     * POST /bitacoras/remind-today
     * Emite recordatorio SOLO si no hay bitácora con fecha == hoy (timezone app).
     */
    public function remindToday(Request $request): JsonResponse
    {
        $tz   = config('app.timezone', 'America/Mexico_City');
        $hoy  = Carbon::now($tz)->toDateString();

        $alumnoId = (string) Auth::id();
        if (!$alumnoId) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // ✅ Igualdad exacta por fecha Y-m-d (evita problemas de hour/TZ)
        $existe = Bitacora::where('alumno_id', $alumnoId)
            ->where('fecha', $hoy)
            ->exists();

        if ($existe) {
            return response()->json(null, 204); // ya la tiene: no recordar
        }

        $payload = [
            'fecha'   => $hoy,
            'mensaje' => 'Aún no registras tu bitácora emocional de hoy. Tómate 3 minutos para completarla.',
            'tipo'    => 'recordatorio_bitacora',
        ];

        try {
            event(new BitacoraRecordatorio($alumnoId, $payload));
        } catch (\Throwable $e) {
            Log::error('Broadcast BitacoraRecordatorio falló', [
                'error' => $e->getMessage(),
            ]);
            // puedes devolver 500 si lo prefieres; 200 evita romper flujo en front
        }

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Recordatorio enviado.',
        ], 200);
    }
}
