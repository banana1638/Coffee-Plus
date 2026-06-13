<?php

namespace App\Http\Requests\API;

use App\Models\ProductAddon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sizes = collect(config('coffee.options.sizes', []))->pluck('name')->all();
        $temps = config('coffee.options.temps', []);

        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:20',
            'size' => ['required', 'string', Rule::in($sizes)],
            'temp' => ['required', 'string', Rule::in($temps)],
            'addons' => 'nullable|array|max:20',
            'addons.*' => 'string|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $productId = (int) $this->input('product_id');
            $addons = array_values(array_unique($this->input('addons', []) ?? []));

            if (!$productId || $addons === []) {
                return;
            }

            $validAddons = ProductAddon::where('product_id', $productId)
                ->whereIn('name', $addons)
                ->count();

            if ($validAddons !== count($addons)) {
                $validator->errors()->add('addons', 'Selected add-ons are invalid for this product.');
            }
        });
    }
}
