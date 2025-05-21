<?php

namespace App\Http\Controllers\Api;

use App\Models\Recompensa;
use Illuminate\Http\Request;
use App\Http\Requests\RecompensaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecompensaResource;

class RecompensaController extends Controller
{
    /**
     * Mostrar todas las recompensas disponibles (sin paginación).
     */
    public function index(Request $request): JsonResponse
    {
        // Obtenemos todas las recompensas porque su número será reducido
        $recompensas = Recompensa::all();

        return response()->json([
            'registros' => RecompensaResource::collection($recompensas),
        ], 200);
    }

    /**
     * Almacenar una nueva recompensa en el sistema.
     */
    public function store(RecompensaRequest $request): JsonResponse
    {
        $recompensa = Recompensa::create($request->validated());

        return response()->json([
            'mensaje'    => 'Recompensa creada correctamente.',
            'recompensa' => new RecompensaResource($recompensa),
        ], 201);
    }

    /**
     * Mostrar la recompensa especificada.
     */
    public function show(Recompensa $recompensa): JsonResponse
    {
        return response()->json([
            'recompensa' => new RecompensaResource($recompensa),
        ], 200);
    }

    /**
     * Actualizar la recompensa especificada en el sistema.
     */
    public function update(RecompensaRequest $request, Recompensa $recompensa): JsonResponse
    {
        $recompensa->update($request->validated());

        return response()->json([
            'mensaje'    => 'Recompensa actualizada correctamente.',
            'recompensa' => new RecompensaResource($recompensa),
        ], 200);
    }

    /**
     * Eliminar la recompensa especificada del sistema.
     */
    public function destroy(Recompensa $recompensa): JsonResponse
    {
        $recompensa->delete();

        return response()->json([
            'mensaje' => 'Recompensa eliminada correctamente.',
        ], 200);
    }
}
