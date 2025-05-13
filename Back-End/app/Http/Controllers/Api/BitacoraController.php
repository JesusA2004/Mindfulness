<?php

namespace App\Http\Controllers\Api;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use App\Http\Requests\BitacoraRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BitacoraResource;

class BitacoraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $bitacoras = Bitacora::paginate();

        return BitacoraResource::collection($bitacoras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BitacoraRequest $request): JsonResponse
    {
        $bitacora = Bitacora::create($request->validated());

        return response()->json(new BitacoraResource($bitacora));
    }

    /**
     * Display the specified resource.
     */
    public function show(Bitacora $bitacora): JsonResponse
    {
        return response()->json(new BitacoraResource($bitacora));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BitacoraRequest $request, Bitacora $bitacora): JsonResponse
    {
        $bitacora->update($request->validated());

        return response()->json(new BitacoraResource($bitacora));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Bitacora $bitacora): Response
    {
        $bitacora->delete();

        return response()->noContent();
    }
}
