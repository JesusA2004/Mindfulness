<?php

namespace App\Http\Controllers\Api;

use App\Models\Persona;
use Illuminate\Http\Request;
use App\Http\Requests\PersonaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PersonaResource;

class PersonaController extends Controller
{
    /**
     * Mostrar un listado paginado de personas (6 por página).
     */
    public function index(Request $request): JsonResponse
    {
        $personas = Persona::all();

        return response()->json([
            'registros' => PersonaResource::collection($personas),
        ], 200);
    }

    /**
     * Almacenar una nueva persona en el sistema.
     */
    public function store(PersonaRequest $request): JsonResponse
    {
        $persona = Persona::create($request->validated());

        return response()->json([
            'mensaje' => 'Persona creada correctamente.',
            'persona' => new PersonaResource($persona),
        ], 201);
    }

    /**
     * Mostrar la persona especificada.
     */
    public function show(Persona $persona): JsonResponse
    {
        return response()->json([
            'persona' => new PersonaResource($persona),
        ], 200);
    }

    /**
     * Actualizar la persona especificada en el sistema.
     */
    public function update(PersonaRequest $request, Persona $persona): JsonResponse
    {
        $persona->update($request->validated());

        return response()->json([
            'mensaje' => 'Persona actualizada correctamente.',
            'persona' => new PersonaResource($persona),
        ], 200);
    }

    /**
     * Eliminar la persona especificada del sistema.
     */
    public function destroy(Persona $persona): JsonResponse
    {
        $persona->delete();

        return response()->json([
            'mensaje' => 'Persona eliminada correctamente.',
        ], 200);
    }
}
