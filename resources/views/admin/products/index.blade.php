<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-6">
            <x-layout.page-header title="Product Manager" description="Desktop inventory control for menu items, pricing, ratings, and stock.">
                <x-slot:actions>
                    @adminCan('product.create')
                        <x-ui.button :href="route('admin.products.create')" size="lg">
                            Add New Coffee
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
                <table class="w-full min-w-[980px] text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Pricing</th>
                            <th class="px-4 py-3">Stock</th>
                            @if(Auth::guard('admin')->user()->canPerform('product.update') || Auth::guard('admin')->user()->canPerform('product.delete'))
                                <th class="px-4 py-3 text-right">Management</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @foreach($products as $product)
                            <tr class="group transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-4">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                            @if($product->image)
                                                <img src="{{ asset('images/products/'.$product->image) }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-slate-400">No Img</div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $product->name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">ID #{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge>{{ $product->menu->name ?? 'Uncategorized' }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-950">RM {{ number_format($product->price, 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $product->oz_redeem_value }} oz redeem</p>
                                    <p class="mt-1 text-xs font-semibold text-amber-700">{{ number_format($product->average_rating, 1) }}/5 reviews</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if($product->track_stock)
                                        <x-ui.badge :variant="(int) $product->stock <= 5 ? 'danger' : 'success'">
                                            {{ (int) $product->stock }} in stock
                                        </x-ui.badge>
                                    @else
                                        <x-ui.badge>Not tracked</x-ui.badge>
                                    @endif
                                </td>
                                @if(Auth::guard('admin')->user()->canPerform('product.update') || Auth::guard('admin')->user()->canPerform('product.delete'))
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2 opacity-100 transition md:opacity-0 md:group-hover:opacity-100">
                                            @adminCan('product.update')
                                                <x-ui.button :href="route('admin.products.edit', $product->id)" variant="secondary" size="sm">
                                                    Edit
                                                </x-ui.button>
                                            @endadminCan
                                            @adminCan('product.delete')
                                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                    @csrf @method('DELETE')
                                                    <x-ui.button type="submit" variant="danger" size="sm">
                                                        Delete
                                                    </x-ui.button>
                                                </form>
                                            @endadminCan
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
