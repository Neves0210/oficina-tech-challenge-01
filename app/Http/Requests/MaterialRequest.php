<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price_cents' => [$required, 'required', 'integer', 'between:0,2147483647'],
            'unit' => $this->routeIs('parts.*')
                ? ['sometimes', 'required', Rule::in(['unit'])]
                : [$required, 'required', 'string', 'max:20'],
            'active' => ['sometimes', 'required', 'boolean'],
            'stock_quantity' => ['prohibited'],
            'type' => ['prohibited'],
        ];
    }
}
