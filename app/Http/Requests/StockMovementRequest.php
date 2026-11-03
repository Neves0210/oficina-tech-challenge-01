<?php

namespace App\Http\Requests;

use App\Domain\Materials\MaterialType;
use App\Domain\Materials\MovementDirection;
use App\Domain\Materials\Quantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_int($this->input('quantity'))) {
            $this->merge(['quantity' => (string) $this->input('quantity')]);
        }
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(MovementDirection::class)],
            'quantity' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                try {
                    ($this->routeIs('parts.*') ? MaterialType::PART : MaterialType::SUPPLY)->validateQuantity(new Quantity($value));
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
