<?php

namespace App\Http\Controllers\Api;

use App\Application\Orders\OrderRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PaginationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRegistry $orders) {}

    public function index(PaginationRequest $request): JsonResponse
    {
        return response()->json($this->orders->paginate($request->integer('per_page', 20), $request->integer('page', 1)));
    }

    public function store(OrderRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->orders->open($request->validated(), $request->user()->id)], 201);
    }

    public function show(int $order): JsonResponse
    {
        return response()->json(['data' => $this->orders->find($order)]);
    }

    public function diagnose(Request $request, int $order): JsonResponse
    {
        $data = $request->validate(['diagnosis' => ['required', 'string', 'max:10000']]);

        return response()->json(['data' => $this->orders->diagnose($order, $data['diagnosis'], $request->user()->id)]);
    }

    public function replaceItems(OrderItemsRequest $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->orders->replaceItems($order, $request->validated('items'))]);
    }
}
