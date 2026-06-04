<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'use_oz' => 'nullable|array',
            'use_oz.*' => 'exists:cart_items,id',
            'coupon_code' => 'nullable|string',
            'pickup_time' => 'nullable|date|after:now',
        ];
    }
}
