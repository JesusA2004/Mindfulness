<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\TestRequest;
use App\Http\Requests\ResponderTestRequest;
use App\Http\Resources\TestResource;

class TestController extends Controller
{
    /**
     * Listado paginado (6 por página).
     */
    public function index(Request $request): JsonResponse
    {
        $tests = Test::paginate(6);

        return response()->json([
            'registros' => TestResource::collection($tests)->resolve(),
            'enlaces'   => [
                'primero'   => $tests->url(1),
                'ultimo'    => $tests->url($tests->lastPage()),
                'anterior'  => $tests->previousPageUrl(),
                'siguiente' => $tests->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Crear test (respuestas NO obligatorias). Si no viene fechaAplicacion, se asigna hoy (Y-m-d).
     */
    public function store(TestRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['fechaAplicacion'] = $data['fechaAplicacion'] ?? now()->format('Y-m-d');

        if (!empty($data['cuestionario']) && is_array($data['cuestionario'])) {
            foreach ($data['cuestionario'] as &$q) {
                if (!isset($q['respuestas_por_usuario']) || !is_array($q['respuestas_por_usuario'])) {
                    $q['respuestas_por_usuario'] = [];
                }
            }
            unset($q);
        }

        $test = Test::create($data);

        return response()->json([
            'mensaje' => 'Test emocional creado correctamente.',
            'test'    => new TestResource($test->fresh()),
        ], 201);
    }

    /**
     * Mostrar test.
     */
    public function show(Test $test): JsonResponse
    {
        return response()->json([
            'test' => new TestResource($test),
        ], 200);
    }

    /**
     * Actualizar test (si se envía cuestionario, normaliza respuestas_por_usuario).
     */
    public function update(TestRequest $request, Test $test): JsonResponse
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

        $test->fill($data)->save();

        return response()->json([
            'mensaje' => 'Test emocional actualizado correctamente.',
            'test'    => new TestResource($test->fresh()),
        ], 200);
    }

    /**
     * Registrar/actualizar respuestas de un alumno para este test (upsert por usuario y pregunta).
     * Ruta: PUT /api/tests/{test}/responder
     *
     * Body:
     * {
     *   "usuario_id": "68dafc4ffe40ef27600dba32",
     *   "respuestas": [
     *     { "pregunta_id": "t1", "respuesta": "Siempre" },
     *     { "pregunta_id": "t2", "respuesta": ["Ansiedad","Estrés"] },
     *     { "pregunta_id": "t3", "respuesta": "Me ayuda respirar profundo" }
     *   ]
     * }
     */
    public function responder(ResponderTestRequest $request, Test $test): JsonResponse
    {
        $usuarioId = $request->input('usuario_id');
        $respuestas = $request->input('respuestas', []);

        $cuestionario = $test->cuestionario ?? [];
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
                    'message' => "La pregunta '{$pregId}' no pertenece a este test."
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

        // Upsert de respuestas por usuario/pregunta
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

        $test->cuestionario = $cuestionario;
        $test->save();

        return response()->json([
            'mensaje' => 'Respuestas registradas correctamente.',
            'test'    => new TestResource($test->fresh()),
        ], 200);
    }

    /**
     * Eliminar test.
     */
    public function destroy(Test $test): JsonResponse
    {
        $test->delete();

        return response()->json([
            'mensaje' => 'Test emocional eliminado correctamente.',
        ], 200);
    }
}
