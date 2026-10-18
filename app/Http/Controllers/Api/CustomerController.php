<?php

namespace App\Http\Controllers\Api;

use App\Application\Registration\CustomerRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\PaginationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerRegistry $registry) {}

    public function index(PaginationRequest $request): JsonResponse
    {
        return response()->json($this->registry->paginate(
            $request->integer('per_page', 20), $request->integer('page', 1),
        ));
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated())], 201);
    }

    public function show(int $customer): JsonResponse
    {
        return response()->json(['data' => $this->registry->find($customer)]);
    }

    public function update(CustomerRequest $request, int $customer): JsonResponse
    {
        return response()->json(['data' => $this->registry->save($request->validated(), $customer)]);
    }

    public function destroy(int $customer): Response
    {
        $this->registry->delete($customer);

        return response()->noContent();
    }
}
