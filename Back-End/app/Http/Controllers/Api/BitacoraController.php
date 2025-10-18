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
        $data = $request->validated();
        $data['alumno_id'] = auth()->id();

        // Buscar si existe registro para (alumno, fecha)
        $bitacora = Bitacora::firstOrNew([
            'alumno_id' => $data['alumno_id'],
            'fecha'     => $data['fecha'],
        ]);

        // Será "nuevo" si aún no existía en BD
        $wasNew = !$bitacora->exists;

        $bitacora->fill($data)->save();

        return response()->json([
            'mensaje'  => 'Bitácora emocional guardada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
            'awarded'  => $wasNew, // ← bandera para abonar puntos solo si es la primera del día
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
        // Asegurar que la bitácora sea del usuario autenticado (opcional, recomendado)
        if ($bitacora->alumno_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $bitacora->update($request->validated());

        return response()->json([
            'mensaje'  => 'Bitácora emocional actualizada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
        ], 200);
    }

    public function destroy(Bitacora $bitacora): JsonResponse
    {
        // Asegurar que la bitácora sea del usuario autenticado (opcional, recomendado)
        if ($bitacora->alumno_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $bitacora->delete();

        return response()->json([
            'mensaje' => 'Bitácora emocional eliminada correctamente.',
        ], 200);
    }
}
