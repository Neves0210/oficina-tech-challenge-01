<?php

namespace App\Http\Controllers\Api;

use App\Application\Orders\CustomerPortal;
use App\Domain\Orders\BudgetDecision;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CustomerPortalController extends Controller
{
    public function __construct(private readonly CustomerPortal $portal) {}

    public function issue(Request $request, int $order): JsonResponse
    {
        return response()->json(['data' => $this->portal->issue($order, $request->user()->id)], 201)
            ->header('Cache-Control', 'no-store');
    }

    public function revoke(int $order): Response
    {
        $this->portal->revoke($order);

        return response()->noContent();
    }

    public function show(int $order): JsonResponse
    {
        return response()->json(['data' => $this->portal->show($order)])->header('Cache-Control', 'no-store');
    }

    public function budget(int $order, int $budget): JsonResponse
    {
        return response()->json(['data' => $this->portal->budget($order, $budget)])->header('Cache-Control', 'no-store');
    }

    public function decide(Request $request, int $order, int $budget): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in([BudgetDecision::APPROVED->value, BudgetDecision::REJECTED->value])]]);

        return response()->json(['data' => $this->portal->decide($order, $budget, BudgetDecision::from($data['decision']), $request->attributes->get('customer_access_hash'))])
            ->header('Cache-Control', 'no-store');
    }
}
