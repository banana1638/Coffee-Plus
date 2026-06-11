<x-app-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @auth
                <x-ui.card>
                    <div class="grid gap-6 lg:grid-cols-[220px_1fr_240px] lg:items-center">
                        <div class="mx-auto w-40 lg:mx-0">
                            @include('components.tank-visualization')
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">Current Storage</p>
                                <p class="mt-2 text-3xl font-semibold text-indigo-700">
                                    {{ Auth::user()->tangki_oz }} <span class="text-sm text-slate-500">oz</span>
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">Account Balance</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-950">
                                    <span class="text-sm text-slate-500">RM</span> {{ number_format(Auth::user()->tangki_balance, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-left lg:text-right">
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Welcome, {{ Auth::user()->name }}</h1>
                            <p class="mt-1 text-sm text-slate-600">Coffee member dashboard</p>
                            <x-ui.button :href="route('tangki.index')" class="mt-4">
                                Manage Tangki
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Coffee-Plus Menu</h1>
                            <p class="mt-1 text-sm text-slate-600">Log in to manage your Tangki, rewards, and saved combinations.</p>
                        </div>
                        <div class="flex gap-2">
                            <x-ui.button type="button" variant="secondary" @click="$store.authModal.show('login')">Login</x-ui.button>
                            <x-ui.button type="button" @click="$store.authModal.show('register')">Register</x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            @endauth

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Browse Coffee</h2>
                    <p class="mt-1 text-sm text-slate-600">Search, filter, and add your next order.</p>
                </div>

                <form action="{{ route('dashboard') }}" method="GET" class="relative w-full xl:w-96">
                    <label class="sr-only" for="search">Search coffee</label>
                    <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Search coffee..."
                        class="w-full rounded-xl border-slate-300 bg-white py-3 pl-4 pr-11 text-sm font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="hidden" name="category" value="{{ request('category', 'all') }}">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-indigo-700" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2">
                @auth
                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'collections']) }}"
                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition {{ request('category') === 'collections' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
                        Collections
                    </a>
                @endauth

                <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'all']) }}"
                    class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition {{ request('category', 'all') === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
                    All Items
                </a>

                @foreach($allCategoryNames as $catName)
                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => $catName]) }}"
                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition {{ request('category') === $catName ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
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
