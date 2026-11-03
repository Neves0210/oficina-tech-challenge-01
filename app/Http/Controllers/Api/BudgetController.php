<?php

namespace App\Http\Controllers\Api;

use App\Application\Orders\BudgetRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetRegistry $budgets) {}

    public function store(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->budgets->present($order, $request->user()->id)], 201);
    }

    public function index(PaginationRequest $request, int $order): JsonResponse
    {
        return response()->json($this->budgets->paginate($order, $request->integer('per_page', 20), $request->integer('page', 1)));
    }

    public function show(int $order, int $budget): JsonResponse
    {
        return response()->json(['data' => $this->budgets->find($order, $budget)]);
    }

    public function revise(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->budgets->revise($order, $request->user()->id)]);
    }
}
