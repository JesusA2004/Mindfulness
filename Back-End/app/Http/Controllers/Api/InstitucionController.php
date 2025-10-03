<?php

namespace App\Http\Controllers\Api;

use App\Models\Institucion;
use Illuminate\Http\Request;
use App\Http\Requests\InstitucionRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\InstitucionResource;
use MongoDB\BSON\ObjectId;
use Throwable;

class InstitucionController extends Controller
{
    /**
     * Listar todas las instituciones (sin paginación).
     */
    public function index(Request $request): JsonResponse
    {
        $instituciones = Institucion::all();

        return response()->json([
            'registros' => InstitucionResource::collection($instituciones),
        ], 200);
    }

    /**
     * Crear una institución.
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
     * Mostrar una institución por id.
     */
    public function show(string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        $institucion = Institucion::where('_id', $oid)->first();
        if (!$institucion) {
            return response()->json(['mensaje' => 'Institución no encontrada.'], 404);
        }

        return response()->json([
            'institucion' => new InstitucionResource($institucion),
        ], 200);
    }

    /**
     * Actualizar una institución por id.
     */
    public function update(InstitucionRequest $request, string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        $institucion = Institucion::where('_id', $oid)->first();
        if (!$institucion) {
            return response()->json(['mensaje' => 'Institución no encontrada.'], 404);
        }

        $institucion->update($request->validated());

        return response()->json([
            'mensaje'     => 'Institución actualizada correctamente.',
            'institucion' => new InstitucionResource($institucion),
        ], 200);
    }

    /**
     * Eliminar una institución por id (hard delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        try {
            $deleted = Institucion::where('_id', $oid)->delete(); // devuelve número de doc eliminados

            if ($deleted === 0) {
                return response()->json(['mensaje' => 'Institución no encontrada.'], 404);
            }

            return response()->json(['mensaje' => 'Institución eliminada correctamente.'], 200);
        } catch (Throwable $e) {
            return response()->json([
                'mensaje' => 'No se pudo eliminar la institución.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convierte un string hex de 24 caracteres a ObjectId. Devuelve null si es inválido.
     */
    private function toObjectId(?string $id): ?ObjectId
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id)) {
            return null;
        }

        try {
            return new ObjectId($id);
        } catch (Throwable $e) {
            return null;
        }
    }
}
