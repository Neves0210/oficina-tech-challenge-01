<?php

namespace App\Http\Requests;

use App\Domain\Vehicles\Plate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('plate'))) {
            $this->merge(['plate' => strtoupper(str_replace(['-', ' '], '', $this->input('plate')))]);
        }
    }

    public function rules(): array
    {
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'customer_id' => [$required, 'required', 'integer', 'exists:customers,id'],
            'plate' => ['bail', $required, 'required', 'string', 'max:8',
                function ($attribute, $value, $fail) {
                    try {
                        new Plate($value);
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
                Rule::unique('vehicles', 'plate')->ignore((int) $this->route('vehicle')),
            ],
            'brand' => [$required, 'required', 'string', 'max:100'],
            'model' => [$required, 'required', 'string', 'max:100'],
            'year' => [$required, 'required', 'integer', 'between:1886,'.(date('Y') + 1)],
        ];
    }
}
