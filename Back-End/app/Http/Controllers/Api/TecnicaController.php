<?php

namespace App\Http\Controllers\Api;

use App\Models\Tecnica;
use Illuminate\Http\Request;
use App\Http\Requests\TecnicaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TecnicaResource;
use Illuminate\Support\Arr;
use MongoDB\BSON\ObjectId;

class TecnicaController extends Controller
{
    /**
     * Listado paginado (6 por página).
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
     * Crear técnica con subdocumentos embebidos.
     */
    public function store(TecnicaRequest $request): JsonResponse
    {
        $datos = $request->validated();

        // Crear solo campos base (sin arrays embebidos)
        $base = Arr::except($datos, ['calificaciones', 'recursos']);
        $tecnica = Tecnica::create($base);

        // Si vienen calificaciones, las colocamos como array embebido
        if (!empty($datos['calificaciones']) && is_array($datos['calificaciones'])) {
            $califs = array_map(function ($c) {
                // Útil para futuras ediciones/borrados por _id interno del subdoc
                if (!isset($c['_id'])) {
                    $c['_id'] = (string) new ObjectId();
                }
                return $c;
            }, $datos['calificaciones']);

            $tecnica->calificaciones = $califs;
        }

        // Si vienen recursos, los colocamos como array embebido
        if (!empty($datos['recursos']) && is_array($datos['recursos'])) {
            $recursos = array_map(function ($r) {
                if (!isset($r['_id'])) {
                    $r['_id'] = (string) new ObjectId();
                }
                return $r;
            }, $datos['recursos']);

            $tecnica->recursos = $recursos;
        }

        $tecnica->save();

        return response()->json([
            'mensaje' => 'Técnica creada correctamente.',
            'tecnica' => new TecnicaResource($tecnica->fresh()),
        ], 201);
    }

    /**
     * Mostrar una técnica.
     */
    public function show(Tecnica $tecnica): JsonResponse
    {
        return response()->json([
            'tecnica' => new TecnicaResource($tecnica),
        ], 200);
    }

    /**
     * Actualizar técnica (reemplaza arrays embebidos si se envían).
     */
    public function update(TecnicaRequest $request, Tecnica $tecnica): JsonResponse
    {
        $datos = $request->validated();

        // Actualiza campos base
        $base = Arr::except($datos, ['calificaciones', 'recursos']);
        $tecnica->fill($base);

        // Si el payload incluye 'calificaciones', se reemplaza el array completo
        if (array_key_exists('calificaciones', $datos)) {
            $califs = $datos['calificaciones'] ?? [];
            if (is_array($califs)) {
                $califs = array_map(function ($c) {
                    $c['_id'] = $c['_id'] ?? (string) new ObjectId();
                    return $c;
                }, $califs);
            }
            $tecnica->calificaciones = $califs;
        }

        // Si el payload incluye 'recursos', se reemplaza el array completo
        if (array_key_exists('recursos', $datos)) {
            $recursos = $datos['recursos'] ?? [];
            if (is_array($recursos)) {
                $recursos = array_map(function ($r) {
                    $r['_id'] = $r['_id'] ?? (string) new ObjectId();
                    return $r;
                }, $recursos);
            }
            $tecnica->recursos = $recursos;
        }

        $tecnica->save();

        return response()->json([
            'mensaje' => 'Técnica actualizada correctamente.',
            'tecnica' => new TecnicaResource($tecnica->fresh()),
        ], 200);
    }

    /**
     * Eliminar técnica.
     */
    public function destroy(Tecnica $tecnica): JsonResponse
    {
        $tecnica->delete();

        return response()->json([
            'mensaje' => 'Técnica eliminada correctamente.',
        ], 200);
    }
}
