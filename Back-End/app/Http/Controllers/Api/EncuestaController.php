<?php

namespace App\Http\Controllers\Api;

use App\Models\Encuesta;
use Illuminate\Http\Request;
use App\Http\Requests\EncuestaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EncuestaResource;

class EncuestaController extends Controller
{
    /**
     * Mostrar un listado paginado de encuestas (6 por página).
     */
    public function index(Request $request): JsonResponse
    {
        $encuestas = Encuesta::paginate(6);

        return response()->json([
            'registros' => EncuestaResource::collection($encuestas)->resolve(),
            'enlaces'   => [
                'primero'   => $encuestas->url(1),
                'ultimo'    => $encuestas->url($encuestas->lastPage()),
                'anterior'  => $encuestas->previousPageUrl(),
                'siguiente' => $encuestas->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Almacenar una nueva encuesta en el sistema.
     */
    public function store(EncuestaRequest $request): JsonResponse
    {
        $encuesta = Encuesta::create($request->validated());

        return response()->json([
            'mensaje'  => 'Encuesta creada correctamente.',
            'encuesta' => new EncuestaResource($encuesta),
        ], 201);
    }

    /**
     * Mostrar la encuesta especificada.
     */
    public function show(Encuesta $encuesta): JsonResponse
    {
        return response()->json([
            'encuesta' => new EncuestaResource($encuesta),
        ], 200);
    }

    /**
     * Actualizar la encuesta especificada en el sistema.
     */
    public function update(EncuestaRequest $request, Encuesta $encuesta): JsonResponse
    {
        $encuesta->update($request->validated());

        return response()->json([
            'mensaje'  => 'Encuesta actualizada correctamente.',
            'encuesta' => new EncuestaResource($encuesta),
        ], 200);
    }

    /**
     * Eliminar la encuesta especificada del sistema.
     */
    public function destroy(Encuesta $encuesta): JsonResponse
    {
        $encuesta->delete();

        return response()->json([
            'mensaje' => 'Encuesta eliminada correctamente.',
        ], 200);
    }
}
