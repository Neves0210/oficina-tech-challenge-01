<?php

namespace App\Http\Controllers\Api;

use App\Application\Orders\ExecutionTimeReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExecutionTimeController extends Controller
{
    public function __invoke(ExecutionTimeReport $report): JsonResponse
    {
        return response()->json(['data' => $report->summary()]);
    }
}
