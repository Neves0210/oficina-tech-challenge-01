<?php

namespace App\Http\Controllers\Api;

use App\Application\Materials\MaterialRegistry;
use App\Domain\Materials\MaterialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\StockMovementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

abstract class MaterialController extends Controller
{
    protected const TYPE = MaterialType::PART;

    public function __construct(private readonly MaterialRegistry $registry) {}

    public function index(PaginationRequest $request): JsonResponse
    {
        return response()->json($this->registry->paginate(static::TYPE, $request->integer('per_page', 20), $request->integer('page', 1)));
    }

    public function store(MaterialRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->registry->save(static::TYPE, $request->validated())], 201);
    }

    public function show(int $material): JsonResponse
    {
        return response()->json(['data' => $this->registry->find(static::TYPE, $material)]);
    }

    public function update(MaterialRequest $request, int $material): JsonResponse
    {
        return response()->json(['data' => $this->registry->save(static::TYPE, $request->validated(), $material)]);
    }

    public function destroy(int $material): Response
    {
        $this->registry->delete(static::TYPE, $material);

        return response()->noContent();
    }

    public function move(StockMovementRequest $request, int $material): JsonResponse
    {
        return response()->json(['data' => $this->registry->move(static::TYPE, $material, $request->validated(), $request->user()->id)], 201);
    }

    public function movements(PaginationRequest $request, int $material): JsonResponse
    {
        return response()->json($this->registry->movements(static::TYPE, $material, $request->integer('per_page', 20), $request->integer('page', 1)));
    }
}
