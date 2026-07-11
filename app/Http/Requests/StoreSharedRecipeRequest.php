<?php

namespace App\Http\Requests;

class StoreSharedRecipeRequest extends ProductOptionsRequest
{
    public function rules(): array
    {
        return array_merge($this->productOptionRules(), [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
