<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class InitiateRefillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:5', 'max:500', 'decimal:0,2'],
        ];
    }

    public function amountCents(): int
    {
        return Money::toCents($this->validated('amount'));
    }
}
