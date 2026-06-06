<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponAdminController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function index()
    {
        $coupons = Coupon::latest()->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $coupon = new Coupon();
        $this->fillCoupon($coupon, $validated);
        $coupon->used_count = 0;
        $coupon->save();
        $this->auditLogService->record('coupon.create', $coupon, null, $coupon->only([
            'code',
            'type',
            'value',
            'expires_at',
            'usage_limit',
        ]));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate($this->rules($coupon));

        $oldValues = $coupon->only(['code', 'type', 'value', 'expires_at', 'usage_limit']);
        $this->fillCoupon($coupon, $validated);
        $coupon->save();
        $this->auditLogService->record('coupon.update', $coupon, $oldValues, $coupon->only([
            'code',
            'type',
            'value',
            'expires_at',
            'usage_limit',
        ]));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $oldValues = $coupon->only(['code', 'type', 'value', 'expires_at', 'usage_limit']);
        $coupon->delete();
        $this->auditLogService->record('coupon.delete', $coupon, $oldValues, null);

        return back()->with('success', 'Coupon deleted.');
    }

    private function rules(?Coupon $coupon = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function fillCoupon(Coupon $coupon, array $data): void
    {
        $coupon->code = strtoupper($data['code']);
        $coupon->type = $data['type'];
        $coupon->value = $data['value'];
        $coupon->expires_at = $data['expires_at'] ?? null;
        $coupon->usage_limit = $data['usage_limit'] ?? null;
    }
}
