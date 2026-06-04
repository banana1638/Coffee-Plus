<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4">
            <a href="{{ route('admin.coupons.index') }}"
                class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-xs uppercase tracking-widest mb-8">
                Back to coupons
            </a>

            <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Create Coupon</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Add a promotion code</p>
                </div>

                <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @include('admin.coupons.partials.form', ['coupon' => null])
                    <button type="submit" class="w-full py-6 bg-gray-900 text-white rounded-[2rem] font-black text-lg hover:bg-blue-600 transition-all">
                        CREATE COUPON
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
