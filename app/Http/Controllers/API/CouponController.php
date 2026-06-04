<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $subtotal = (float) $validated['subtotal'];
        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (!$coupon || !$coupon->isValid()) {
            return $this->success([
                'valid' => false,
                'discount' => 0,
                'final_amount' => $subtotal,
                'message' => 'Coupon is invalid or expired.',
            ]);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return $this->success([
            'valid' => true,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'discount' => $discount,
            'final_amount' => max(0, $subtotal - $discount),
            'message' => 'Coupon applied.',
        ]);
    }
}
