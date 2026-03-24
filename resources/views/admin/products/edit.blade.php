<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4">
            
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-xs uppercase tracking-widest mb-8 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Discard Changes
            </a>

            <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Edit Coffee</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Modify #{{ $product->id }} details</p>
                </div>

                <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Update Photography</label>
                        <div class="relative group">
                            <input type="file" name="image" id="image-upload-edit" class="hidden" accept="image/*" onchange="previewImageEdit(event)">
                            <label for="image-upload-edit" class="flex flex-col items-center justify-center w-full h-48 bg-gray-50 rounded-[2rem] border-2 border-dashed border-gray-200 cursor-pointer hover:bg-gray-100 hover:border-blue-300 transition-all overflow-hidden relative">
                                <img id="image-preview-edit" 
                                     src="{{ $product->image ? asset('images/products/'.$product->image) : '#' }}" 
                                     class="{{ $product->image ? '' : 'hidden' }} w-full h-full object-cover">
                                
                                <div id="preview-placeholder-edit" class="{{ $product->image ? 'hidden' : '' }} text-center">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Click to change</p>
                                </div>
                                
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-white text-[10px] font-black uppercase tracking-widest">Change Photo</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Coffee Name</label>
                            <input type="text" name="name" value="{{ $product->name }}" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Price (RM)</label>
                            <input type="number" step="0.01" name="price" value="{{ $product->price }}" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Redeem Value (OZ)</label>
                            <input type="number" name="oz_redeem_value" value="{{ $product->oz_redeem_value }}" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Menu Category</label>
                            <select name="menu_id" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 appearance-none">
                                @foreach($menus as $menu)
                                    <option value="{{ $menu->id }}" {{ $product->menu_id == $menu->id ? 'selected' : '' }}>{{ $menu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                        <div class="md:col-span-2 space-y-4">
                            <div class="flex items-center justify-between ml-4">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Add-ons</label>
                                <button type="button" onclick="addAddonRow()" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors uppercase tracking-wider flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Option
                                </button>
                            </div>
                            
                            <div id="addons-container" class="space-y-3">
                                @forelse($product->addons as $index => $addon)
                                <div class="flex items-center gap-3 addon-row">
                                    <input type="text" name="addons[{{ $index }}][name]" value="{{ $addon->name }}" placeholder="Name" class="flex-1 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                                    <input type="number" step="0.01" name="addons[{{ $index }}][price]" value="{{ $addon->price }}" placeholder="Price" class="w-32 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                                    <button type="button" onclick="removeAddonRow(this)" class="p-4 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-2xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                @empty
                                <div class="flex items-center gap-3 addon-row">
                                    <input type="text" name="addons[0][name]" placeholder="Name (e.g. Extra Shot)" class="flex-1 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                                    <input type="number" step="0.01" name="addons[0][price]" placeholder="Price (RM)" class="w-32 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                                    <button type="button" onclick="removeAddonRow(this)" class="p-4 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-2xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-6 bg-blue-600 text-white rounded-[2rem] font-black text-lg shadow-2xl shadow-blue-200 hover:bg-gray-900 transition-all hover:-translate-y-1 active:scale-[0.98]">
                        UPDATE PRODUCT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImageEdit(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('image-preview-edit');
                const placeholder = document.getElementById('preview-placeholder-edit');
                output.src = reader.result;
                output.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        let addonCount = {{ max(1, $product->addons ? $product->addons->count() : 1) }};
        function addAddonRow() {
            const container = document.getElementById('addons-container');
            const newRow = document.createElement('div');
            newRow.className = 'flex items-center gap-3 addon-row';
            newRow.innerHTML = `
                <input type="text" name="addons[${addonCount}][name]" placeholder="Name (e.g. Extra Shot)" class="flex-1 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                <input type="number" step="0.01" name="addons[${addonCount}][price]" placeholder="Price (RM)" class="w-32 px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                <button type="button" onclick="removeAddonRow(this)" class="p-4 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-2xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
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