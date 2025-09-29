<?php

namespace App\Http\Controllers\Api;

use App\Models\Institucion;
use Illuminate\Http\Request;
use App\Http\Requests\InstitucionRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\InstitucionResource;

class InstitucionController extends Controller
{
    /**
     * Mostrar todas las instituciones disponibles (sin paginación).
     */
    public function index(Request $request): JsonResponse
    {
        $instituciones = Institucion::all();

        return response()->json([
            'registros' => InstitucionResource::collection($instituciones),
        ], 200);
    }

    /**
     * Almacenar una nueva institución en el sistema.
     */
    public function store(InstitucionRequest $request): JsonResponse
    {
        $institucion = Institucion::create($request->validated());

        return response()->json([
            'mensaje'     => 'Institución creada correctamente.',
            'institucion' => new InstitucionResource($institucion),
        ], 201);
    }

    /**
     * Mostrar la institución especificada.
     */
    public function show(Institucion $institucion): JsonResponse
    {
        return response()->json([
            'institucion' => new InstitucionResource($institucion),
        ], 200);
    }

    /**
     * Actualizar la institución especificada en el sistema.
     */
    public function update(InstitucionRequest $request, Institucion $institucion): JsonResponse
    {
        $institucion->update($request->validated());

        return response()->json([
            'mensaje'     => 'Institución actualizada correctamente.',
            'institucion' => new InstitucionResource($institucion),
        ], 200);
    }

    /**
     * Eliminar la institución especificada del sistema.
     */
    public function destroy(Institucion $institucion): JsonResponse
    {
        $institucion->delete();

        return response()->json([
            'mensaje' => 'Institución eliminada correctamente.',
        ], 200);
    }
}
