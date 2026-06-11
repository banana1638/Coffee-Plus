<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Coupon Manager</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Promotion Control</p>
                </div>
                @adminCan('coupon.create')
                    <a href="{{ route('admin.coupons.create') }}"
                        class="px-8 py-4 bg-gray-900 text-white rounded-[1.5rem] font-black text-sm shadow-xl shadow-gray-200 hover:bg-blue-600 transition-all">
                        + ADD COUPON
                    </a>
                @endadminCan
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-500 text-white rounded-2xl font-bold shadow-lg shadow-green-100">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 border-b border-gray-50">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Code</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Discount</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Usage</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expiry</th>
                            @if(Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete'))
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($coupons as $coupon)
                            <tr class="hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-5">
                                    <p class="font-black text-gray-900 text-lg tracking-widest">{{ $coupon->code }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">{{ $coupon->isValid() ? 'Active' : 'Inactive' }}</p>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest">
                                        @if($coupon->type === 'percent')
                                            {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%
                                        @else
                                            RM {{ number_format($coupon->value, 2) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-600">
                                    {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? 'Unlimited' }}
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-600">
                                    {{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d H:i') : 'No expiry' }}
                                </td>
                                @if(Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete'))
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex justify-end gap-3">
                                            @adminCan('coupon.update')
                                                <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                                    class="px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-black hover:bg-blue-600 transition-all">
                                                    EDIT
                                                </a>
                                            @endadminCan
                                            @adminCan('coupon.delete')
                                                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Delete this coupon?')"
                                                        class="px-5 py-2.5 bg-red-50 text-red-600 rounded-xl text-xs font-black hover:bg-red-600 hover:text-white transition-all">
                                                        DELETE
                                                    </button>
                                                </form>
                                            @endadminCan
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete') ? 5 : 4 }}" class="px-8 py-12 text-center text-gray-400 font-bold uppercase tracking-widest">
                                    No coupons found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
