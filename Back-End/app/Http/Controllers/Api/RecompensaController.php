<?php

namespace App\Http\Controllers\Api;

use App\Models\Recompensa;
use Illuminate\Http\Request;
use App\Http\Requests\RecompensaRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecompensaResource;

class RecompensaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $recompensas = Recompensa::paginate();

        return RecompensaResource::collection($recompensas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RecompensaRequest $request): JsonResponse
    {
        $recompensa = Recompensa::create($request->validated());

        return response()->json(new RecompensaResource($recompensa));
    }

    /**
     * Display the specified resource.
     */
    public function show(Recompensa $recompensa): JsonResponse
    {
        return response()->json(new RecompensaResource($recompensa));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RecompensaRequest $request, Recompensa $recompensa): JsonResponse
    {
        $recompensa->update($request->validated());

        return response()->json(new RecompensaResource($recompensa));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Recompensa $recompensa): Response
    {
        $recompensa->delete();

        return response()->noContent();
    }
}
