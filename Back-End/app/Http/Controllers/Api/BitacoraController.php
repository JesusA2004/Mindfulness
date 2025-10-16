<?php

namespace App\Http\Controllers\Api;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\BitacoraRequest;
use App\Http\Resources\BitacoraResource;
use Carbon\Carbon;

class BitacoraController extends Controller
{
    /**
     * Devuelve todas las bitácoras del alumno autenticado.
     * Se puede filtrar opcionalmente por mes y año (?mes=10&anio=2025)
     * y la respuesta incluye los valores de hoy, mes y año actuales.
     */
    public function index(Request $request): JsonResponse
    {
        $tz   = config('app.timezone', 'America/Mexico_City');
        $now  = \Carbon\Carbon::now($tz);

        // Si vienen filtros, se usan; si no, devolvemos TODO el historial
        $mes  = $request->query('mes');   // opcional
        $anio = $request->query('anio');  // opcional

        $alumnoId = auth()->id();

        $query = Bitacora::where('alumno_id', $alumnoId)
            ->orderBy('fecha', 'desc')
            ->orderBy('_id', 'desc');

        // ⚠️ Importante: como 'fecha' es STRING, filtramos por RANGO de strings "YYYY-MM-DD"
        if ($anio && $mes) {
            $inicio = \Carbon\Carbon::createFromDate((int)$anio, (int)$mes, 1, $tz)->toDateString();     // e.g. 2025-10-01
            $fin    = \Carbon\Carbon::createFromDate((int)$anio, (int)$mes, 1, $tz)->endOfMonth()->toDateString(); // e.g. 2025-10-31
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif ($anio && !$mes) {
            // Rango del año completo
            $inicio = \Carbon\Carbon::createFromDate((int)$anio, 1, 1, $tz)->toDateString();             // e.g. 2025-01-01
            $fin    = \Carbon\Carbon::createFromDate((int)$anio, 12, 31, $tz)->toDateString();           // e.g. 2025-12-31
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif (!$anio && $mes) {
            // Si llega solo mes, usamos el año actual del TZ
            $inicio = \Carbon\Carbon::createFromDate($now->year, (int)$mes, 1, $tz)->toDateString();
            $fin    = \Carbon\Carbon::createFromDate($now->year, (int)$mes, 1, $tz)->endOfMonth()->toDateString();
            $query->whereBetween('fecha', [$inicio, $fin]);
        }

        $bitacoras = $query->get();

        // Formato de salida con fecha desglosada
        $bitacorasFormateadas = $bitacoras->map(function ($b) {
            $fecha = \Carbon\Carbon::parse($b->fecha);
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
            'hoy'       => (int) $now->day,      // solo el día
            'mes'       => (int) ($mes ?? $now->month),
            'anio'      => (int) ($anio ?? $now->year),
            'bitacoras' => $bitacorasFormateadas,
        ], 200);
    }

    /**
     * Guarda o actualiza una bitácora del alumno autenticado.
     * Si ya existe una bitácora para la fecha, la actualiza.
     */
    public function store(BitacoraRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['alumno_id'] = auth()->id();

        // Buscar si ya existe una bitácora para esa fecha y alumno
        $bitacora = Bitacora::firstOrNew([
            'alumno_id' => $data['alumno_id'],
            'fecha'     => $data['fecha'],
        ]);

        $bitacora->fill($data)->save();

        return response()->json([
            'mensaje'  => 'Bitácora emocional guardada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
        ], 201);
    }

    /**
     * Muestra una bitácora específica por su ID.
     */
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

    /**
     * Actualiza una bitácora existente.
     */
    public function update(BitacoraRequest $request, Bitacora $bitacora): JsonResponse
    {
        $bitacora->update($request->validated());

        return response()->json([
            'mensaje'  => 'Bitácora emocional actualizada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
        ], 200);
    }

    /**
     * Elimina una bitácora específica.
     */
    public function destroy(Bitacora $bitacora): JsonResponse
    {
        $bitacora->delete();

        return response()->json([
            'mensaje' => 'Bitácora emocional eliminada correctamente.',
        ], 200);
    }
}
