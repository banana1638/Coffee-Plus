<?php

namespace App\Http\Requests;

class AddCartItemRequest extends ProductOptionsRequest
{
    public function rules(): array
    {
        return array_merge($this->productOptionRules(), [
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }
}
