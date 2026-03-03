<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4">
            
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-xs uppercase tracking-widest mb-8 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Back to Inventory
            </a>

            <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Add New Coffee</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Create a new menu item</p>
                </div>

                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Product Photography</label>
                        <div class="relative group">
                            <input type="file" name="image" id="image-upload" class="hidden" accept="image/*" onchange="previewImage(event)">
                            <label for="image-upload" class="flex flex-col items-center justify-center w-full h-48 bg-gray-50 rounded-[2rem] border-2 border-dashed border-gray-200 cursor-pointer hover:bg-gray-100 hover:border-blue-300 transition-all overflow-hidden">
                                <div id="preview-placeholder" class="text-center">
                                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mx-auto mb-3">
                                        <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tap to select image</p>
                                </div>
                                <img id="image-preview" class="hidden w-full h-full object-cover">
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Coffee Name</label>
                            <input type="text" name="name" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300" placeholder="e.g. Caramel Macchiato">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Price (RM)</label>
                            <input type="number" step="0.01" name="price" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300" placeholder="12.00">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Redeem Value (OZ)</label>
                            <input type="number" name="oz_redeem_value" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300" placeholder="150">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Menu Category</label>
                            <select name="menu_id" required class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 appearance-none">
                                <option value="" disabled selected>Select a category</option>
                                @foreach($menus as $menu)
                                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-6 bg-gray-900 text-white rounded-[2rem] font-black text-lg shadow-2xl shadow-gray-200 hover:bg-blue-600 transition-all hover:-translate-y-1 active:scale-[0.98]">
                        CREATE PRODUCT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('image-preview');
                const placeholder = document.getElementById('preview-placeholder');
                output.src = reader.result;
                output.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</x-admin-layout>