<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-layout.page-header title="Edit product" description="Modify backend-owned pricing, stock controls, imagery, category, and add-ons for #{{ $product->id }}.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.products.index')" variant="secondary">
                        Discard Changes
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <x-ui.card>
                <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-8 xl:grid-cols-[320px_1fr]">
                        <div class="space-y-2">
                            <label for="image-upload-edit" class="text-sm font-medium text-slate-700">Product Photography</label>
                            <input type="file" name="image" id="image-upload-edit" class="hidden" accept="image/*" onchange="previewImageEdit(event)">
                            <label for="image-upload-edit" class="group relative flex h-64 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-[rgb(var(--cp-line))] bg-slate-50 transition hover:border-emerald-400 hover:bg-emerald-50/40">
                                <img id="image-preview-edit"
                                    src="{{ $product->image ? $product->detail_image_url : '#' }}"
                                    class="{{ $product->image ? '' : 'hidden' }} h-full w-full object-cover" alt="{{ $product->name }} preview">
                                <div id="preview-placeholder-edit" class="{{ $product->image ? 'hidden' : '' }} text-center">
                                    <p class="text-sm font-semibold text-slate-500">Select image</p>
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center bg-slate-950/50 opacity-0 transition group-hover:opacity-100">
                                    <span class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-950">Change Photo</span>
                                </div>
                            </label>
                            @error('image')
                                <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <label for="name" class="text-sm font-medium text-slate-700">Coffee Name</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            </div>

                            <div class="space-y-2">
                                <label for="price" class="text-sm font-medium text-slate-700">Price (RM)</label>
                                <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            </div>

                            <div class="space-y-2">
                                <label for="oz_redeem_value" class="text-sm font-medium text-slate-700">Redeem Value (OZ)</label>
                                <input id="oz_redeem_value" type="number" name="oz_redeem_value" value="{{ old('oz_redeem_value', $product->oz_redeem_value) }}" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label for="menu_id" class="text-sm font-medium text-slate-700">Menu Category</label>
                                <select id="menu_id" name="menu_id" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    @foreach($menus as $menu)
                                        <option value="{{ $menu->id }}" @selected(old('menu_id', $product->menu_id) == $menu->id)>{{ $menu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[rgb(var(--cp-line))] bg-stone-100 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label for="track-stock-edit" class="text-sm font-semibold text-slate-950">Stock Tracking</label>
                                <p class="mt-1 text-xs text-slate-500">Enable this when the product has limited inventory.</p>
                            </div>
                            <label class="inline-flex items-center gap-3">
                                <input id="track-stock-edit" type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock))
                                    class="rounded border-[rgb(var(--cp-line))] text-emerald-700 focus:ring-emerald-600">
                                <span class="text-sm font-semibold text-slate-700">Track Stock</span>
                            </label>
                        </div>

                        <div class="mt-4 space-y-2">
                            <label for="stock" class="text-sm font-medium text-slate-700">Current Stock</label>
                            <input id="stock" type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}"
                                class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="e.g. 25">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Add-ons</h2>
                                <p class="text-sm text-slate-600">Optional paid additions shown during checkout.</p>
                            </div>
                            <x-ui.button type="button" variant="secondary" size="sm" onclick="addAddonRow()">
                                Add Option
                            </x-ui.button>
                        </div>

                        <div id="addons-container" class="space-y-3">
                            @forelse($product->addons as $index => $addon)
                                <div class="grid gap-3 addon-row md:grid-cols-[1fr_160px_44px]">
                                    <input type="text" name="addons[{{ $index }}][name]" value="{{ $addon->name }}" placeholder="Name"
                                        class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <input type="number" step="0.01" name="addons[{{ $index }}][price]" value="{{ $addon->price }}" placeholder="Price"
                                        class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <button type="button" onclick="removeAddonRow(this)" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" aria-label="Remove add-on">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @empty
                                <div class="grid gap-3 addon-row md:grid-cols-[1fr_160px_44px]">
                                    <input type="text" name="addons[0][name]" placeholder="Name (e.g. Extra Shot)"
                                        class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <input type="number" step="0.01" name="addons[0][price]" placeholder="Price (RM)"
                                        class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <button type="button" onclick="removeAddonRow(this)" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" aria-label="Remove add-on">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" size="lg">
                            Update Product
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        function previewImageEdit(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('image-preview-edit').src = reader.result;
                document.getElementById('image-preview-edit').classList.remove('hidden');
                document.getElementById('preview-placeholder-edit').classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        let addonCount = {{ max(1, $product->addons ? $product->addons->count() : 1) }};
        function addAddonRow() {
            const container = document.getElementById('addons-container');
            const newRow = document.createElement('div');
            newRow.className = 'grid gap-3 addon-row md:grid-cols-[1fr_160px_44px]';
            newRow.innerHTML = `
                <input type="text" name="addons[${addonCount}][name]" placeholder="Name (e.g. Extra Shot)" class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                <input type="number" step="0.01" name="addons[${addonCount}][price]" placeholder="Price (RM)" class="rounded-lg border-[rgb(var(--cp-line))] text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
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
