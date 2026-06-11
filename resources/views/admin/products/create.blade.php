<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-layout.page-header title="Add New Coffee" description="Create a menu item with pricing, stock tracking, and optional add-ons.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.products.index')" variant="secondary">
                        Back to Inventory
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <x-ui.card>
                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf

                    <div class="grid gap-8 xl:grid-cols-[320px_1fr]">
                        <div class="space-y-2">
                            <label for="image-upload" class="text-sm font-medium text-slate-700">Product Photography</label>
                            <input type="file" name="image" id="image-upload" class="hidden" accept="image/*" onchange="previewImage(event)">
                            <label for="image-upload" class="group flex h-64 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                                <div id="preview-placeholder" class="text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm transition group-hover:text-indigo-600">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500">Select image</p>
                                </div>
                                <img id="image-preview" class="hidden h-full w-full object-cover" alt="Product preview">
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <label for="name" class="text-sm font-medium text-slate-700">Coffee Name</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                    class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Caramel Macchiato">
                            </div>

                            <div class="space-y-2">
                                <label for="price" class="text-sm font-medium text-slate-700">Price (RM)</label>
                                <input id="price" type="number" step="0.01" name="price" value="{{ old('price') }}" required
                                    class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="12.00">
                            </div>

                            <div class="space-y-2">
                                <label for="oz_redeem_value" class="text-sm font-medium text-slate-700">Redeem Value (OZ)</label>
                                <input id="oz_redeem_value" type="number" name="oz_redeem_value" value="{{ old('oz_redeem_value') }}" required
                                    class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="150">
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label for="menu_id" class="text-sm font-medium text-slate-700">Menu Category</label>
                                <select id="menu_id" name="menu_id" required
                                    class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled selected>Select a category</option>
                                    @foreach($menus as $menu)
                                        <option value="{{ $menu->id }}" @selected(old('menu_id') == $menu->id)>{{ $menu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label for="track-stock" class="text-sm font-semibold text-slate-950">Stock Tracking</label>
                                <p class="mt-1 text-xs text-slate-500">Enable this when the product has limited inventory.</p>
                            </div>
                            <label class="inline-flex items-center gap-3">
                                <input id="track-stock" type="checkbox" name="track_stock" value="1" @checked(old('track_stock'))
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-semibold text-slate-700">Track Stock</span>
                            </label>
                        </div>

                        <div class="mt-4 space-y-2">
                            <label for="stock" class="text-sm font-medium text-slate-700">Current Stock</label>
                            <input id="stock" type="number" min="0" name="stock" value="{{ old('stock') }}"
                                class="w-full rounded-lg border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. 25">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Add-ons</h2>
                                <p class="text-sm text-slate-600">Optional paid additions such as extra shot or syrup.</p>
                            </div>
                            <x-ui.button type="button" variant="secondary" size="sm" onclick="addAddonRow()">
                                Add Option
                            </x-ui.button>
                        </div>

                        <div id="addons-container" class="space-y-3">
                            <div class="grid gap-3 addon-row md:grid-cols-[1fr_160px_44px]">
                                <input type="text" name="addons[0][name]" placeholder="Name (e.g. Extra Shot)"
                                    class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="number" step="0.01" name="addons[0][price]" placeholder="Price (RM)"
                                    class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="button" onclick="removeAddonRow(this)" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" aria-label="Remove add-on">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" size="lg">
                            Create Product
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('image-preview').src = reader.result;
                document.getElementById('image-preview').classList.remove('hidden');
                document.getElementById('preview-placeholder').classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        let addonCount = 1;
        function addAddonRow() {
            const container = document.getElementById('addons-container');
            const newRow = document.createElement('div');
            newRow.className = 'grid gap-3 addon-row md:grid-cols-[1fr_160px_44px]';
            newRow.innerHTML = `
                <input type="text" name="addons[${addonCount}][name]" placeholder="Name (e.g. Extra Shot)" class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <input type="number" step="0.01" name="addons[${addonCount}][price]" placeholder="Price (RM)" class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="button" onclick="removeAddonRow(this)" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" aria-label="Remove add-on">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            container.appendChild(newRow);
            addonCount++;
        }

        function removeAddonRow(button) {
            button.closest('.addon-row').remove();
        }
    </script>
</x-admin-layout>
