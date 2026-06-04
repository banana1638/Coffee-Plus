<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_item_id' => 'nullable|exists:cart_items,id',
            'product_id' => 'nullable|required_without:cart_item_id|exists:products,id',
        ];
    }
}
