<?php

namespace App\Http\Controllers\Api;

use App\Models\Actividad;
use Illuminate\Http\Request;
use App\Http\Requests\ActividadRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActividadResource;

class ActividadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $actividads = Actividad::paginate();

        return ActividadResource::collection($actividads);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ActividadRequest $request): JsonResponse
    {
        $actividad = Actividad::create($request->validated());

        return response()->json(new ActividadResource($actividad));
    }

    /**
     * Display the specified resource.
     */
    public function show(Actividad $actividad): JsonResponse
    {
        return response()->json(new ActividadResource($actividad));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActividadRequest $request, Actividad $actividad): JsonResponse
    {
        $actividad->update($request->validated());

        return response()->json(new ActividadResource($actividad));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Actividad $actividad): Response
    {
        $actividad->delete();

        return response()->noContent();
    }
}
