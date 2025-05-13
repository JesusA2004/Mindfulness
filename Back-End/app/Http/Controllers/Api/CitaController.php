<?php

namespace App\Http\Controllers\Api;

use App\Models\Cita;
use Illuminate\Http\Request;
use App\Http\Requests\CitaRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitaResource;

class CitaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $citas = Cita::paginate();

        return CitaResource::collection($citas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CitaRequest $request): JsonResponse
    {
        $cita = Cita::create($request->validated());

        return response()->json(new CitaResource($cita));
    }

    /**
     * Display the specified resource.
     */
    public function show(Cita $cita): JsonResponse
    {
        return response()->json(new CitaResource($cita));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CitaRequest $request, Cita $cita): JsonResponse
    {
        $cita->update($request->validated());

        return response()->json(new CitaResource($cita));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Cita $cita): Response
    {
        $cita->delete();

        return response()->noContent();
    }
}
