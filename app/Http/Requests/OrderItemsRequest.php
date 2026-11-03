<?php

namespace App\Http\Requests;

use App\Domain\Materials\MaterialType;
use App\Domain\Materials\Quantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class OrderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as &$item) {
                if (is_array($item) && is_int($item['quantity'] ?? null)) {
                    $item['quantity'] = (string) $item['quantity'];
                }
            }
            unset($item);
            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'list', 'max:100'],
            'items.*' => ['required', 'array:kind,catalog_id,quantity'],
            'items.*.kind' => ['required', Rule::in(['SERVICE', 'PART', 'SUPPLY'])],
            'items.*.catalog_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                try {
                    $index = explode('.', $attribute)[1];
                    ($this->input("items.{$index}.kind") === 'SUPPLY' ? MaterialType::SUPPLY : MaterialType::PART)->validateQuantity(new Quantity($value));
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
        ];
    }
}
