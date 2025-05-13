<?php

namespace App\Http\Controllers\Api;

use App\Models\Tecnica;
use Illuminate\Http\Request;
use App\Http\Requests\TecnicaRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TecnicaResource;

class TecnicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tecnicas = Tecnica::paginate();

        return TecnicaResource::collection($tecnicas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TecnicaRequest $request): JsonResponse
    {
        $tecnica = Tecnica::create($request->validated());

        return response()->json(new TecnicaResource($tecnica));
    }

    /**
     * Display the specified resource.
     */
    public function show(Tecnica $tecnica): JsonResponse
    {
        return response()->json(new TecnicaResource($tecnica));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TecnicaRequest $request, Tecnica $tecnica): JsonResponse
    {
        $tecnica->update($request->validated());

        return response()->json(new TecnicaResource($tecnica));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Tecnica $tecnica): Response
    {
        $tecnica->delete();

        return response()->noContent();
    }
}
