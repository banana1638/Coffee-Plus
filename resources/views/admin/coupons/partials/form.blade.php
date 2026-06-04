@if($errors->any())
    <div class="p-4 bg-red-50 text-red-600 rounded-2xl text-sm font-bold">
        {{ $errors->first() }}
    </div>
@endif

<div>
    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Code</label>
    <input type="text" name="code" value="{{ old('code', $coupon?->code) }}" required
        class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 font-bold text-gray-800 uppercase tracking-widest">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Type</label>
        <select name="type" required
            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 font-bold text-gray-800">
            <option value="fixed" @selected(old('type', $coupon?->type) === 'fixed')>Fixed RM</option>
            <option value="percent" @selected(old('type', $coupon?->type) === 'percent')>Percent</option>
        </select>
    </div>

    <div>
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Value</label>
        <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon?->value) }}" required
            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 font-bold text-gray-800">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Expires At</label>
        <input type="datetime-local" name="expires_at"
            value="{{ old('expires_at', $coupon?->expires_at?->format('Y-m-d\TH:i')) }}"
            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 font-bold text-gray-800">
    </div>

    <div>
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Usage Limit</label>
        <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon?->usage_limit) }}"
            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 font-bold text-gray-800">
    </div>
</div>
