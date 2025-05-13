<?php

namespace App\Http\Controllers\Api;

use App\Models\Persona;
use Illuminate\Http\Request;
use App\Http\Requests\PersonaRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PersonaResource;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $personas = Persona::paginate();

        return PersonaResource::collection($personas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PersonaRequest $request): JsonResponse
    {
        $persona = Persona::create($request->validated());

        return response()->json(new PersonaResource($persona));
    }

    /**
     * Display the specified resource.
     */
    public function show(Persona $persona): JsonResponse
    {
        return response()->json(new PersonaResource($persona));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PersonaRequest $request, Persona $persona): JsonResponse
    {
        $persona->update($request->validated());

        return response()->json(new PersonaResource($persona));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Persona $persona): Response
    {
        $persona->delete();

        return response()->noContent();
    }
}
