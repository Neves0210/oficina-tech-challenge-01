<?php

namespace App\Http\Requests;

use App\Domain\Customers\DocumentNumber;
use App\Domain\Vehicles\Plate;
use InvalidArgumentException;

class OrderRequest extends OrderItemsRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['items'][0] = 'sometimes';

        return [...$rules,
            'customer_document' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                try {
                    new DocumentNumber($value);
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'vehicle_plate' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                try {
                    new Plate($value);
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'complaint' => ['required', 'string', 'max:10000'],
            'status' => ['prohibited'],
        ];
    }
}
