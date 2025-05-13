<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use Illuminate\Http\Request;
use App\Http\Requests\TestRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TestResource;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tests = Test::paginate();

        return TestResource::collection($tests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TestRequest $request): JsonResponse
    {
        $test = Test::create($request->validated());

        return response()->json(new TestResource($test));
    }

    /**
     * Display the specified resource.
     */
    public function show(Test $test): JsonResponse
    {
        return response()->json(new TestResource($test));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TestRequest $request, Test $test): JsonResponse
    {
        $test->update($request->validated());

        return response()->json(new TestResource($test));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Test $test): Response
    {
        $test->delete();

        return response()->noContent();
    }
}
