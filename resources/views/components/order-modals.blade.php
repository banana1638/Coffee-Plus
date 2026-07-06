<div id="confirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-lg">
        <div class="text-center">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-950">Confirm Order?</h3>
            <p class="mt-2 text-sm text-slate-600">Proceed with adding this drink to your cart?</p>
            <div class="mt-6 flex flex-col gap-3">
                <x-ui.button type="button" onclick="executeSubmit()" id="btnConfirm" class="w-full">
                    Confirm Add to Cart
                </x-ui.button>
                <x-ui.button type="button" onclick="toggleModal('confirmModal', false)" variant="secondary" class="w-full">
                    Cancel
                </x-ui.button>
            </div>
        </div>
    </div>
</div>

<div id="successModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-lg">
        <div class="text-center">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-950">Success</h3>
            <p class="mt-2 text-sm text-slate-600">Your drink was added to the cart.</p>
            <x-ui.button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="mt-6 w-full">
                Back to Menu
            </x-ui.button>
        </div>
    </div>
</div>
