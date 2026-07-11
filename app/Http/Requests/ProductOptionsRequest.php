<?php

namespace App\Http\Requests;

use App\Support\ProductAddonSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class ProductOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function productOptionRules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'size' => ['required', 'string', Rule::in(collect(config('coffee.options.sizes', []))->pluck('name')->all())],
            'temp' => ['required', 'string', Rule::in(config('coffee.options.temps', []))],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*' => ['string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('product_id')) {
                return;
            }

            $productId = (int) $this->input('product_id');
            $addons = ProductAddonSelection::normalize($this->input('addons', []));

            if (! ProductAddonSelection::belongsToProduct($productId, $addons)) {
                $validator->errors()->add('addons', 'Selected add-ons are invalid for this product.');
            }
        });
    }
}
