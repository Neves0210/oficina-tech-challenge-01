<?php

namespace App\Http\Controllers\Api;

use App\Application\Registration\ServiceRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceRegistry $registry) {}

    public function index(PaginationRequest $request): JsonResponse
    {
        return response()->json($this->registry->paginate(
            $request->integer('per_page', 20), $request->integer('page', 1),
        ));
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated())], 201);
    }

    public function show(int $service): JsonResponse
    {
        return response()->json(['data' => $this->registry->find($service)]);
    }

    public function update(ServiceRequest $request, int $service): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated(), $service)]);
    }

    public function destroy(int $service): Response
    {
        $this->registry->delete($service);

        return response()->noContent();
    }
}
