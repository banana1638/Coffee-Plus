<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl space-y-6">
            <x-layout.page-header title="Edit Coupon" description="{{ $coupon->code }}">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.coupons.index')" variant="secondary">
                        Back to Coupons
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <x-ui.card>
                <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('admin.coupons.partials.form', ['coupon' => $coupon])
                    <div class="flex justify-end">
                        <x-ui.button type="submit" size="lg">
                            Update Coupon
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
