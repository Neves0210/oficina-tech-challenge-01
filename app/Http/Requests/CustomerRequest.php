<?php

namespace App\Http\Requests;

use App\Domain\Customers\DocumentNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('document'))) {
            $this->merge(['document' => strtoupper(str_replace(['.', '/', '-', ' '], '', $this->input('document')))]);
        }
    }

    public function rules(): array
    {
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'required', 'string', 'max:255'],
            'document' => ['bail', $required, 'required', 'string', 'max:18',
                function ($attribute, $value, $fail) {
                    try {
                        new DocumentNumber($value);
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
                Rule::unique('customers', 'document')->ignore((int) $this->route('customer')),
            ],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
