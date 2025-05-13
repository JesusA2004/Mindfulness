<?php

namespace App\Http\Controllers\Api;

use App\Models\Encuesta;
use Illuminate\Http\Request;
use App\Http\Requests\EncuestaRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EncuestaResource;

class EncuestaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $encuestas = Encuesta::paginate();

        return EncuestaResource::collection($encuestas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EncuestaRequest $request): JsonResponse
    {
        $encuesta = Encuesta::create($request->validated());

        return response()->json(new EncuestaResource($encuesta));
    }

    /**
     * Display the specified resource.
     */
    public function show(Encuesta $encuesta): JsonResponse
    {
        return response()->json(new EncuestaResource($encuesta));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EncuestaRequest $request, Encuesta $encuesta): JsonResponse
    {
        $encuesta->update($request->validated());

        return response()->json(new EncuestaResource($encuesta));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Encuesta $encuesta): Response
    {
        $encuesta->delete();

        return response()->noContent();
    }
}
