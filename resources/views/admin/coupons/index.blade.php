<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1300px] space-y-6">
            <x-layout.page-header title="Coupon Manager" description="Promotion control, usage limits, and expiry monitoring.">
                <x-slot:actions>
                    @adminCan('coupon.create')
                        <x-ui.button :href="route('admin.coupons.create')" size="lg">
                            Add Coupon
                        </x-ui.button>
                    @endadminCan
                </x-slot:actions>
            </x-layout.page-header>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <x-ui.table-shell>
                <table class="w-full min-w-[860px] text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Usage</th>
                            <th class="px-4 py-3">Expiry</th>
                            @if(Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete'))
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @forelse($coupons as $coupon)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold tracking-wide text-slate-950">{{ $coupon->code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $coupon->isValid() ? 'Active' : 'Inactive' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge variant="info">
                                        @if($coupon->type === 'percent')
                                            {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%
                                        @else
                                            RM {{ number_format($coupon->value, 2) }}
                                        @endif
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">
                                    {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? 'Unlimited' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d H:i') : 'No expiry' }}
                                </td>
                                @if(Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete'))
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            @adminCan('coupon.update')
                                                <x-ui.button :href="route('admin.coupons.edit', $coupon)" variant="secondary" size="sm">
                                                    Edit
                                                </x-ui.button>
                                            @endadminCan
                                            @adminCan('coupon.delete')
                                                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <x-ui.button type="submit" variant="danger" size="sm" onclick="return confirm('Delete this coupon?')">
                                                        Delete
                                                    </x-ui.button>
                                                </form>
                                            @endadminCan
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::guard('admin')->user()->canPerform('coupon.update') || Auth::guard('admin')->user()->canPerform('coupon.delete') ? 5 : 4 }}" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                    No coupons found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div>
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
