<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecompensaRequest;
use App\Http\Resources\RecompensaResource;
use App\Models\Recompensa;
use Illuminate\Http\JsonResponse;
use MongoDB\BSON\ObjectId;
use Throwable;

class RecompensaController extends Controller
{
    /**
     * Listar todas las recompensas (sin paginación).
     */
    public function index(): JsonResponse
    {
        $recompensas = Recompensa::all();

        return response()->json([
            'registros' => RecompensaResource::collection($recompensas),
        ], 200);
    }

    /**
     * Crear una recompensa.
     */
    public function store(RecompensaRequest $request): JsonResponse
    {
        // NOTA: asegúrate de que el modelo Recompensa tenga 'canjeo' en $fillable y 'array' en $casts
        $recompensa = Recompensa::create($request->validated());

        return response()->json([
            'mensaje'    => 'Recompensa creada correctamente.',
            'recompensa' => new RecompensaResource($recompensa),
        ], 201);
    }

    /**
     * Mostrar una recompensa por id (ObjectId de Mongo).
     */
    public function show(string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        $recompensa = Recompensa::where('_id', $oid)->first();
        if (!$recompensa) {
            return response()->json(['mensaje' => 'Recompensa no encontrada.'], 404);
        }

        return response()->json([
            'recompensa' => new RecompensaResource($recompensa),
        ], 200);
    }

    /**
     * Actualizar una recompensa por id.
     * PUT: envía todos los obligatorios. (Tu RecompensaRequest ya lo exige)
     */
    public function update(RecompensaRequest $request, string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        $recompensa = Recompensa::where('_id', $oid)->first();
        if (!$recompensa) {
            return response()->json(['mensaje' => 'Recompensa no encontrada.'], 404);
        }

        $recompensa->update($request->validated());

        return response()->json([
            'mensaje'    => 'Recompensa actualizada correctamente.',
            'recompensa' => new RecompensaResource($recompensa),
        ], 200);
    }

    /**
     * Eliminar una recompensa por id.
     */
    public function destroy(string $id): JsonResponse
    {
        $oid = $this->toObjectId($id);
        if (!$oid) {
            return response()->json(['mensaje' => 'ID inválido. Debe ser un ObjectId de 24 caracteres.'], 400);
        }

        try {
            $deleted = Recompensa::where('_id', $oid)->delete(); // retorna # de docs borrados
            if ($deleted === 0) {
                return response()->json(['mensaje' => 'Recompensa no encontrada.'], 404);
            }

            return response()->json(['mensaje' => 'Recompensa eliminada correctamente.'], 200);
        } catch (Throwable $e) {
            return response()->json([
                'mensaje' => 'No se pudo eliminar la recompensa.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convierte string de 24 hex a ObjectId.
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
