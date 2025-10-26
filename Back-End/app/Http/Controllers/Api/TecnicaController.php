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

        // Crear campos base (sin arrays embebidos)
        $base = Arr::except($datos, ['calificaciones', 'recursos']);
        $tecnica = Tecnica::create($base);

        // Calificaciones (opcional)
        if (!empty($datos['calificaciones']) && is_array($datos['calificaciones'])) {
            $califs = array_map(function ($c) {
                $c['_id'] = $c['_id'] ?? (string) new ObjectId();
                return $c;
            }, $datos['calificaciones']);
            $tecnica->calificaciones = $califs;
        }

        // Recursos (opcional) - normalizar tipo y _id
        if (!empty($datos['recursos']) && is_array($datos['recursos'])) {
            $recursos = array_map(function ($r) {
                $r['_id'] = $r['_id'] ?? (string) new ObjectId();
                $r['tipo'] = $r['tipo'] ?? $this->guessTipoPorUrl($r['url'] ?? '');
                $r['fecha'] = $r['fecha'] ?? date('Y-m-d');
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

        // Campos base
        $base = Arr::except($datos, ['calificaciones', 'recursos']);
        $tecnica->fill($base);

        // Calificaciones (si viene la clave, se reemplaza)
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

        // Recursos (si viene la clave, se reemplaza)
        if (array_key_exists('recursos', $datos)) {
            $recursos = $datos['recursos'] ?? [];
            if (is_array($recursos)) {
                $recursos = array_map(function ($r) {
                    $r['_id'] = $r['_id'] ?? (string) new ObjectId();
                    $r['tipo'] = $r['tipo'] ?? $this->guessTipoPorUrl($r['url'] ?? '');
                    $r['fecha'] = $r['fecha'] ?? date('Y-m-d');
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

    /**
     * Inferir tipo (Imagen|Video|Audio|Documento) según URL o proveedor.
     */
    private function guessTipoPorUrl(string $url): string
    {
        $s = strtolower($url);

        // Proveedores embebibles
        if (preg_match('#(youtube\.com/watch\?v=|youtu\.be/)#', $s)) return 'Video';
        if (preg_match('#vimeo\.com/\d+#', $s)) return 'Video';
        if (preg_match('#soundcloud\.com/#', $s)) return 'Audio';
        if (preg_match('#open\.spotify\.com/#', $s)) return 'Audio';

        // Extensiones
        if (preg_match('#\.(png|jpe?g|gif|webp|avif|svg)(\?.*)?$#', $s)) return 'Imagen';
        if (preg_match('#\.(mp4|webm|ogg|mov|m4v)(\?.*)?$#', $s))     return 'Video';
        if (preg_match('#\.(mp3|wav|ogg|m4a)(\?.*)?$#', $s))           return 'Audio';

        return 'Documento';
    }
}
