<?php

namespace App\Http\Controllers\Api;

use App\Models\Tecnica;
use Illuminate\Http\Request;
use App\Http\Requests\TecnicaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TecnicaResource;

class TecnicaController extends Controller
{
    /**
     * Mostrar un listado paginado de técnicas (6 por página),
     * ideal para presentar en formato de tarjetas (cards).
     */
    public function index(Request $request): JsonResponse
    {
        $tecnicas = Tecnica::paginate(6);

        return response()->json([
            'registros' => TecnicaResource::collection($tecnicas)->resolve(),
            'enlaces'   => [
                'primero'   => $tecnicas->url(1),
                'ultimo'    => $tecnicas->url($tecnicas->lastPage()),
                'anterior'  => $tecnicas->previousPageUrl(),
                'siguiente' => $tecnicas->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Almacenar una nueva técnica mindfulness en el sistema.
     */
    public function store(TecnicaRequest $request): JsonResponse
    {
        $tecnica = Tecnica::create($request->validated());

        return response()->json([
            'mensaje'  => 'Técnica creada correctamente.',
            'tecnica'  => new TecnicaResource($tecnica),
        ], 201);
    }

    /**
     * Mostrar la técnica especificada.
     */
    public function show(Tecnica $tecnica): JsonResponse
    {
        return response()->json([
            'tecnica' => new TecnicaResource($tecnica),
        ], 200);
    }

    /**
     * Actualizar la técnica especificada en el sistema.
     */
    public function update(TecnicaRequest $request, Tecnica $tecnica): JsonResponse
    {
        $tecnica->update($request->validated());

        return response()->json([
            'mensaje'  => 'Técnica actualizada correctamente.',
            'tecnica'  => new TecnicaResource($tecnica),
        ], 200);
    }

    /**
     * Eliminar la técnica especificada del sistema.
     */
    public function destroy(Tecnica $tecnica): JsonResponse
    {
        $tecnica->delete();

        return response()->json([
            'mensaje' => 'Técnica eliminada correctamente.',
        ], 200);
    }
}
