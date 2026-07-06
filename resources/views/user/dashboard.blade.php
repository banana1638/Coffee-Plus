<x-app-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @auth
                <section class="overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm">
                    <div class="h-1 bg-[rgb(var(--cp-brand))]"></div>
                    <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[180px_1fr_auto] lg:items-center">
                        <div class="mx-auto w-40 lg:mx-0">
                            @include('components.tank-visualization')
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="border-l-2 border-emerald-600 bg-emerald-50/60 p-4">
                                <p class="text-xs font-semibold uppercase text-emerald-800">Tangki storage</p>
                                <p class="cp-tabular mt-2 text-3xl font-bold text-[rgb(var(--cp-brand-strong))]">
                                    {{ Auth::user()->tangki_oz }} <span class="text-sm text-slate-500">oz</span>
                                </p>
                            </div>
                            <div class="border-l-2 border-amber-500 bg-amber-50/60 p-4">
                                <p class="text-xs font-semibold uppercase text-amber-800">Cash balance</p>
                                <p class="cp-tabular mt-2 text-3xl font-bold text-[rgb(var(--cp-ink))]">
                                    <span class="text-sm text-slate-500">RM</span> {{ number_format(Auth::user()->tangki_balance, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-left lg:text-right">
                            <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Ready to order</p>
                            <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--cp-ink))]">Welcome, {{ Auth::user()->name }}</h1>
                            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Your balances are confirmed by Coffee Plus.</p>
                            <x-ui.button :href="route('tangki.index')" class="mt-4">
                                Manage Tangki
                            </x-ui.button>
                        </div>
                    </div>
                </section>
            @else
                <section class="rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Made to order</p>
                            <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--cp-ink))]">Coffee Plus Menu</h1>
                            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Sign in to save recipes, use Tangki, and track pickup.</p>
                        </div>
                        <div class="flex gap-2">
                            <x-ui.button type="button" variant="secondary" @click="authModal = 'login'">Login</x-ui.button>
                            <x-ui.button type="button" @click="authModal = 'register'">Register</x-ui.button>
                        </div>
                    </div>
                </section>
            @endauth

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Cafe menu</p>
                    <h2 class="mt-1 text-2xl font-bold text-[rgb(var(--cp-ink))]">Choose your drink</h2>
                    <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Select a drink, then configure size, temperature, and add-ons.</p>
                </div>

                <form action="{{ route('dashboard') }}" method="GET" class="relative w-full xl:w-96">
                    <label class="sr-only" for="search">Search coffee</label>
                    <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Search coffee..."
                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] py-3 pl-4 pr-11 text-sm font-medium text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <input type="hidden" name="category" value="{{ request('category', 'all') }}">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-emerald-50 hover:text-emerald-800" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2">
                @auth
                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'collections']) }}"
                    class="shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold transition {{ request('category') === 'collections' ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-[rgb(var(--cp-muted))] hover:border-emerald-300 hover:text-emerald-800' }}">
                        Collections
                    </a>
                @endauth

                <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'all']) }}"
                    class="shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold transition {{ request('category', 'all') === 'all' ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-[rgb(var(--cp-muted))] hover:border-emerald-300 hover:text-emerald-800' }}">
                    All Items
                </a>

                @foreach($allCategoryNames as $catName)
                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => $catName]) }}"
                        class="shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold transition {{ request('category') === $catName ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-[rgb(var(--cp-muted))] hover:border-emerald-300 hover:text-emerald-800' }}">
                        {{ $catName }}
                    </a>
                @endforeach
            </div>

            @if($category === 'collections')
                @include('user.favorites.list', ['favorites' => $favorites])
            @else
                @include('user.products.index', ['menus' => $menus])
            @endif
        </div>
    </div>
</x-app-layout>
