<?php

namespace App\Http\Controllers\Api;

use App\Models\Encuesta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\EncuestaRequest;
use App\Http\Requests\ResponderEncuestaRequest;
use App\Http\Resources\EncuestaResource;

class EncuestaController extends Controller
{
    /**
     * Listado paginado de encuestas (6 por página).
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
     * Crear una nueva encuesta (respuestas NO son obligatorias al crear).
     * Si no viene 'fechaAsignacion', se asigna automáticamente hoy (Y-m-d).
     * Se normaliza 'cuestionario[].respuestas_por_usuario' como arreglo.
     */
    public function store(EncuestaRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Asignación automática de fechaAsignacion si no viene
        $data['fechaAsignacion'] = $data['fechaAsignacion'] ?? now()->format('Y-m-d');

        // Normalizar respuestas_por_usuario
        if (!empty($data['cuestionario']) && is_array($data['cuestionario'])) {
            foreach ($data['cuestionario'] as &$q) {
                if (!isset($q['respuestas_por_usuario']) || !is_array($q['respuestas_por_usuario'])) {
                    $q['respuestas_por_usuario'] = [];
                }
            }
            unset($q);
        }

        $encuesta = Encuesta::create($data);

        return response()->json([
            'mensaje'  => 'Encuesta creada correctamente.',
            'encuesta' => new EncuestaResource($encuesta->fresh()),
        ], 201);
    }

    /**
     * Mostrar una encuesta.
     */
    public function show(Encuesta $encuesta): JsonResponse
    {
        return response()->json([
            'encuesta' => new EncuestaResource($encuesta),
        ], 200);
    }

    /**
     * Actualizar una encuesta.
     * Si se envía 'cuestionario', se normaliza 'respuestas_por_usuario' a arreglo.
     */
    public function update(EncuestaRequest $request, Encuesta $encuesta): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('cuestionario', $data) && is_array($data['cuestionario'])) {
            foreach ($data['cuestionario'] as &$q) {
                if (!isset($q['respuestas_por_usuario']) || !is_array($q['respuestas_por_usuario'])) {
                    $q['respuestas_por_usuario'] = [];
                }
            }
            unset($q);
        }

        $encuesta->fill($data)->save();

        return response()->json([
            'mensaje'  => 'Encuesta actualizada correctamente.',
            'encuesta' => new EncuestaResource($encuesta->fresh()),
        ], 200);
    }

    /**
     * Registrar/actualizar respuestas de un alumno (upsert por usuario y pregunta).
     * Ruta sugerida: PUT /api/encuestas/{encuesta}/responder
     *
     * Body:
     * {
     *   "usuario_id": "68dafc4ffe40ef27600dba32",
     *   "respuestas": [
     *     { "pregunta_id": "q1", "respuesta": "Diario" },
     *     { "pregunta_id": "q2", "respuesta": ["Interés","Tranquilidad"] },
     *     { "pregunta_id": "q3", "respuesta": "Respiro profundo..." }
     *   ]
     * }
     */
    public function responder(ResponderEncuestaRequest $request, Encuesta $encuesta): JsonResponse
    {
        $usuarioId = $request->input('usuario_id');
        $respuestas = $request->input('respuestas', []);

        $cuestionario = $encuesta->cuestionario ?? [];
        // Mapa pregunta_id => índice para ubicar rápido cada pregunta
        $mapa = [];
        foreach ($cuestionario as $idx => $q) {
            if (isset($q['_id'])) {
                $mapa[$q['_id']] = $idx;
            }
        }

        // Validación contextual por tipo de pregunta
        foreach ($respuestas as $r) {
            $pregId    = $r['pregunta_id'];
            $respuesta = $r['respuesta'];

            if (!array_key_exists($pregId, $mapa)) {
                return response()->json([
                    'message' => "La pregunta '{$pregId}' no pertenece a esta encuesta."
                ], 422);
            }

            $qIdx = $mapa[$pregId];
            $preg = $cuestionario[$qIdx];
            $tipo = $preg['tipo'] ?? null;

            if ($tipo === 'opcion_multiple') {
                if (!is_string($respuesta)) {
                    return response()->json(['message' => "La respuesta de '{$pregId}' debe ser un string."], 422);
                }
                $opc = $preg['opciones'] ?? [];
                if (!in_array($respuesta, $opc, true)) {
                    return response()->json(['message' => "La respuesta de '{$pregId}' debe estar dentro de las opciones permitidas."], 422);
                }
            } elseif ($tipo === 'seleccion_multiple') {
                if (!is_array($respuesta) || empty($respuesta)) {
                    return response()->json(['message' => "La respuesta de '{$pregId}' debe ser un arreglo no vacío."], 422);
                }
                $opc = $preg['opciones'] ?? [];
                foreach ($respuesta as $val) {
                    if (!in_array($val, $opc, true)) {
                        return response()->json(['message' => "La opción '{$val}' no es válida para '{$pregId}'."], 422);
                    }
                }
            } elseif ($tipo === 'respuesta_abierta') {
                if (!is_string($respuesta) || trim($respuesta) === '') {
                    return response()->json(['message' => "La respuesta de '{$pregId}' debe ser texto."], 422);
                }
            } else {
                return response()->json(['message' => "Tipo de pregunta inválido o no definido en '{$pregId}'."], 422);
            }
        }

        // Upsert de respuesta por usuario/pregunta
        foreach ($respuestas as $r) {
            $pregId    = $r['pregunta_id'];
            $qIdx      = $mapa[$pregId];

            if (!isset($cuestionario[$qIdx]['respuestas_por_usuario']) || !is_array($cuestionario[$qIdx]['respuestas_por_usuario'])) {
                $cuestionario[$qIdx]['respuestas_por_usuario'] = [];
            }

            $lista = $cuestionario[$qIdx]['respuestas_por_usuario'];
            $yaExiste = false;

            foreach ($lista as &$ru) {
                if (($ru['usuario_id'] ?? null) === $usuarioId) {
                    $ru['respuesta'] = $r['respuesta'];
                    $ru['fecha']     = now()->format('Y-m-d');
                    $yaExiste = true;
                    break;
                }
            }
            unset($ru);

            if (!$yaExiste) {
                $lista[] = [
                    'usuario_id' => $usuarioId,
                    'respuesta'  => $r['respuesta'],
                    'fecha'      => now()->format('Y-m-d'),
                ];
            }

            $cuestionario[$qIdx]['respuestas_por_usuario'] = $lista;
        }

        $encuesta->cuestionario = $cuestionario;
        $encuesta->save();

        return response()->json([
            'mensaje'  => 'Respuestas registradas correctamente.',
            'encuesta' => new EncuestaResource($encuesta->fresh()),
        ], 200);
    }

    /**
     * Eliminar una encuesta.
     */
    public function destroy(Encuesta $encuesta): JsonResponse
    {
        $encuesta->delete();

        return response()->json([
            'mensaje' => 'Encuesta eliminada correctamente.',
        ], 200);
    }
}
