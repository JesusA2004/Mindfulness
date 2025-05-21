<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use Illuminate\Http\Request;
use App\Http\Requests\TestRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TestResource;

class TestController extends Controller
{
    /**
     * Mostrar un listado paginado de tests emocionales (6 por página).
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
     * Almacenar un nuevo test emocional en el sistema.
     */
    public function store(TestRequest $request): JsonResponse
    {
        $test = Test::create($request->validated());

        return response()->json([
            'mensaje' => 'Test emocional creado correctamente.',
            'test'    => new TestResource($test),
        ], 201);
    }

    /**
     * Mostrar el test emocional especificado.
     */
    public function show(Test $test): JsonResponse
    {
        return response()->json([
            'test' => new TestResource($test),
        ], 200);
    }

    /**
     * Actualizar el test emocional especificado en el sistema.
     */
    public function update(TestRequest $request, Test $test): JsonResponse
    {
        $test->update($request->validated());

        return response()->json([
            'mensaje' => 'Test emocional actualizado correctamente.',
            'test'    => new TestResource($test),
        ], 200);
    }

    /**
     * Eliminar el test emocional especificado del sistema.
     */
    public function destroy(Test $test): JsonResponse
    {
        $test->delete();

        return response()->json([
            'mensaje' => 'Test emocional eliminado correctamente.',
        ], 200);
    }
}
