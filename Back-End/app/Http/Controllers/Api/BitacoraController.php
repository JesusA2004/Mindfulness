<?php

namespace App\Http\Controllers\Api;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use App\Http\Requests\BitacoraRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BitacoraResource;

class BitacoraController extends Controller
{
    /**
     * Devuelve datos para el calendario emocional:
     * - 'hoy': fecha actual para resaltar.
     * - 'bitacoras': todas las bitácoras del mes agrupadas por día.
     */
    public function index(Request $request): JsonResponse
    {
        // Determinar mes y año a mostrar (query params o hoy)
        $mes  = $request->query('mes', Carbon::now()->month);
        $anio = $request->query('anio', Carbon::now()->year);

        // ID del alumno autenticado
        $alumnoId = auth()->id();

        // Traer todas las bitácoras de ese mes
        $bitacoras = Bitacora::where('alumno_id', $alumnoId)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->get();

        // Agrupar por día (YYYY-MM-DD)
        $mapaPorDia = $bitacoras
            ->mapToGroups(fn($b) => [ $b->fecha->toDateString() => new BitacoraResource($b) ])
            ->toArray();

        return response()->json([
            'hoy'        => Carbon::today()->toDateString(),
            'mes'        => $mes,
            'anio'       => $anio,
            'bitacoras'  => $mapaPorDia,
        ], 200);
    }

    /**
     * Almacenar o actualizar la bitácora emocional de un día.
     * Si ya existe una bitácora para esa fecha y alumno, la actualiza; si no, la crea.
     */
    public function store(BitacoraRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['alumno_id'] = auth()->id();

        // Buscar si ya hay bitácora para ese alumno y fecha
        $bitacora = Bitacora::firstOrNew([
            'alumno_id' => $data['alumno_id'],
            'fecha'     => $data['fecha'],
        ]);
        $bitacora->fill($data);
        $bitacora->save();

        return response()->json([
            'mensaje'  => 'Bitácora emocional guardada correctamente.',
            'bitacora' => new BitacoraResource($bitacora),
        ], 201);
    }

    /**
     * Mostrar la bitácora especificada.
     */
    public function show(Bitacora $bitacora): JsonResponse
    {
        return response()->json([
            'bitacora' => new BitacoraResource($bitacora),
        ], 200);
    }

    /**
     * Actualizar la bitácora especificada en el sistema.
     */
    public function update(BitacoraRequest $request, Bitacora $bitacora): JsonResponse
    {
        $bitacora->update($request->validated());

        return response()->json([
            'mensaje'   => 'Bitácora emocional actualizada correctamente.',
            'bitacora'  => new BitacoraResource($bitacora),
        ], 200);
    }

    /**
     * Eliminar la bitácora especificada del sistema.
     */
    public function destroy(Bitacora $bitacora): JsonResponse
    {
        $bitacora->delete();

        return response()->json([
            'mensaje' => 'Bitácora emocional eliminada correctamente.',
        ], 200);
    }
}
