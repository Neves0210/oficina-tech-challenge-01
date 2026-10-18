<?php

namespace App\Http\Controllers\Api;

use App\Application\Registration\VehicleRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\VehicleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function __construct(private readonly VehicleRegistry $registry) {}

    public function index(PaginationRequest $request): JsonResponse
    {
        return response()->json($this->registry->paginate(
            $request->integer('per_page', 20), $request->integer('page', 1),
        ));
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated())], 201);
    }

    public function show(int $vehicle): JsonResponse
    {
        return response()->json(['data' => $this->registry->find($vehicle)]);
    }

    public function update(VehicleRequest $request, int $vehicle): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated(), $vehicle)]);
    }

    public function destroy(int $vehicle): Response
    {
        $this->registry->delete($vehicle);

        return response()->noContent();
    }
}
