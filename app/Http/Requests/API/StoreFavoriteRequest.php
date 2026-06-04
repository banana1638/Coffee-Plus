<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
            'temp' => 'required|string',
            'addons' => 'array',
            'remark' => 'nullable|string',
        ];
    }
}
