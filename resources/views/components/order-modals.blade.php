<div id="confirmModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 mb-2">Confirm Order?</h3>
            <p class="text-gray-500 mb-8">Proceed with payment using your Tangki Balance?</p>
            <div class="flex flex-col gap-3">
                <button type="button" onclick="executeSubmit()" id="btnConfirm"
                    class="w-full bg-gray-900 text-white py-4 rounded-2xl font-bold hover:bg-blue-600 transition">
                    Confirm Add to Cart
                </button>
                <button type="button" onclick="toggleModal('confirmModal', false)"
                    class="w-full bg-gray-100 text-gray-500 py-4 rounded-2xl font-bold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<div id="successModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl">
        <div class="text-center">
            <div
                class="w-20 h-20 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 mb-2">Success!</h3>
            <p class="text-gray-500 mb-8">Your drink is being prepared. Enjoy!</p>
            <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                class="w-full bg-green-600 text-white py-4 rounded-2xl font-bold hover:bg-green-700 transition">
                Back to Menu
            </button>
        </div>
    </div>
</div>