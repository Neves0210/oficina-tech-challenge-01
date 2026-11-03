<?php

namespace App\Http\Controllers\Api;

use App\Application\Orders\OrderExecution;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutionController extends Controller
{
    public function __construct(private readonly OrderExecution $execution) {}

    public function start(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->execution->start($order, $request->user()->id)]);
    }

    public function finish(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->execution->finish($order, $request->user()->id)]);
    }

    public function deliver(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->execution->deliver($order, $request->user()->id)]);
    }
}
