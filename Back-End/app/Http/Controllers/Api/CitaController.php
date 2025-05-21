<?php

namespace App\Http\Controllers\Api;

use App\Models\Cita;
use Illuminate\Http\Request;
use App\Http\Requests\CitaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitaResource;

class CitaController extends Controller
{
    /**
     * Mostrar un listado paginado de citas (6 por página).
     */
    public function index(Request $request): JsonResponse
    {
        $citas = Cita::paginate(6);

        return response()->json([
            'registros' => CitaResource::collection($citas)->resolve(),
            'enlaces'   => [
                'primero'   => $citas->url(1),
                'ultimo'    => $citas->url($citas->lastPage()),
                'anterior'  => $citas->previousPageUrl(),
                'siguiente' => $citas->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Almacenar una nueva cita en el sistema.
     */
    public function store(CitaRequest $request): JsonResponse
    {
        $cita = Cita::create($request->validated());

        return response()->json([
            'mensaje' => 'Cita creada correctamente.',
            'cita'    => new CitaResource($cita),
        ], 201);
    }

    /**
     * Mostrar la cita especificada.
     */
    public function show(Cita $cita): JsonResponse
    {
        return response()->json([
            'cita' => new CitaResource($cita),
        ], 200);
    }

    /**
     * Actualizar la cita especificada en el sistema.
     */
    public function update(CitaRequest $request, Cita $cita): JsonResponse
    {
        $cita->update($request->validated());

        return response()->json([
            'mensaje' => 'Cita actualizada correctamente.',
            'cita'    => new CitaResource($cita),
        ], 200);
    }

    /**
     * Eliminar la cita especificada del sistema.
     */
    public function destroy(Cita $cita): JsonResponse
    {
        $cita->delete();

        return response()->json([
            'mensaje' => 'Cita eliminada correctamente.',
        ], 200);
    }
}
