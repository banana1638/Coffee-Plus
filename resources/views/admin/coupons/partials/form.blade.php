@if($errors->any())
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert">
        {{ $errors->first() }}
    </div>
@endif

<div class="space-y-2">
    <label for="code" class="text-sm font-medium text-slate-700">Code</label>
    <input id="code" type="text" name="code" value="{{ old('code', $coupon?->code) }}" required
        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold uppercase tracking-wide text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
</div>

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div class="space-y-2">
        <label for="type" class="text-sm font-medium text-slate-700">Type</label>
        <select id="type" name="type" required
            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
            <option value="fixed" @selected(old('type', $coupon?->type) === 'fixed')>Fixed RM</option>
            <option value="percent" @selected(old('type', $coupon?->type) === 'percent')>Percent</option>
        </select>
    </div>

    <div class="space-y-2">
        <label for="value" class="text-sm font-medium text-slate-700">Value</label>
        <input id="value" type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon?->value) }}" required
            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
</div>

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div class="space-y-2">
        <label for="expires_at" class="text-sm font-medium text-slate-700">Expires At</label>
        <input id="expires_at" type="datetime-local" name="expires_at"
            value="{{ old('expires_at', $coupon?->expires_at?->format('Y-m-d\TH:i')) }}"
            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>

    <div class="space-y-2">
        <label for="usage_limit" class="text-sm font-medium text-slate-700">Usage Limit</label>
        <input id="usage_limit" type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon?->usage_limit) }}"
            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
</div>
