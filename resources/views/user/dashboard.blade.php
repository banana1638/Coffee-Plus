<x-app-layout>
    @php
        $featuredProduct = $category === 'collections'
            ? optional($favorites->first())->product
            : $menus->flatMap(fn ($menu) => $menu->products)->first();
        $visibleProductCount = $category === 'collections'
            ? $favorites->count()
            : $menus->sum(fn ($menu) => $menu->products->count());
        $activeCategoryLabel = match ($category) {
            'collections' => 'Saved recipes',
            'all' => 'Full menu',
            default => $category,
        };
    @endphp

    <div class="overflow-hidden">
        <section class="relative border-b border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))]">
            <div class="cp-menu-paper absolute inset-0 opacity-45" aria-hidden="true"></div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[0.82fr_1.18fr] lg:items-center lg:gap-14 lg:px-8 lg:py-20">
                <div class="cp-menu-reveal">
                    <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--cp-brand))]">
                        <span>Coffee Plus</span>
                        <span class="h-px w-10 bg-[rgb(var(--cp-caramel))]" aria-hidden="true"></span>
                        <span class="text-[rgb(var(--cp-muted))]">Made to order</span>
                    </div>

                    <h1 class="mt-6 max-w-2xl font-display text-5xl font-medium leading-[0.98] tracking-[-0.045em] text-[rgb(var(--cp-ink))] sm:text-6xl lg:text-7xl">
                        Choose the cup.<br>
                        <span class="text-[rgb(var(--cp-brand))]">Make it yours.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-7 text-[rgb(var(--cp-muted))] sm:text-lg">
                        Browse the menu, then set the size, temperature, and add-ons before your drink reaches the cart.
                    </p>

                    @auth
                        <dl class="mt-8 grid max-w-xl grid-cols-2 divide-x divide-[rgb(var(--cp-line))] border-y border-[rgb(var(--cp-line))] py-4">
                            <div class="pr-5">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[rgb(var(--cp-muted))]">Tangki OZ</dt>
                                <dd class="cp-tabular mt-1 text-2xl font-bold text-[rgb(var(--cp-brand-strong))]">
                                    {{ number_format(Auth::user()->tangki_oz) }} <span class="text-xs font-semibold text-[rgb(var(--cp-muted))]">OZ</span>
                                </dd>
                            </div>
                            <div class="pl-5">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[rgb(var(--cp-muted))]">Cash balance</dt>
                                <dd class="mt-1"><x-ui.price :amount="Auth::user()->tangki_balance" size="lg" accent /></dd>
                            </div>
                        </dl>
                    @endauth

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <x-ui.button href="#menu" size="lg">
                            Browse the menu
                            <span aria-hidden="true">&#8595;</span>
                        </x-ui.button>

                        @auth
                            <x-ui.button :href="route('tangki.index')" variant="secondary" size="lg">Manage Tangki</x-ui.button>
                        @else
                            <x-ui.button type="button" variant="secondary" size="lg" @click="authModal = 'login'">Sign in</x-ui.button>
                        @endauth
                    </div>

                    <ol class="mt-9 flex flex-wrap gap-x-7 gap-y-3 text-xs font-semibold uppercase tracking-[0.12em] text-[rgb(var(--cp-muted))]" aria-label="Ordering steps">
                        <li><span class="mr-2 text-[rgb(var(--cp-caramel))]">01</span>Choose</li>
                        <li><span class="mr-2 text-[rgb(var(--cp-caramel))]">02</span>Customise</li>
                        <li><span class="mr-2 text-[rgb(var(--cp-caramel))]">03</span>Collect</li>
                    </ol>
                </div>

                <div class="cp-menu-reveal relative lg:pl-6" style="animation-delay: 90ms">
                    @if($featuredProduct)
                        <a href="{{ route('product.detail', $featuredProduct->id) }}" @guest @click.prevent="authModal = 'login'" @endguest
                            class="group relative block overflow-hidden rounded-lg bg-stone-200 shadow-[0_24px_70px_-35px_rgba(24,32,29,0.55)] focus-visible:outline-none">
                            <img src="{{ $featuredProduct->detail_image_url }}" alt="{{ $featuredProduct->name }}"
                                class="aspect-[4/3] h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.025]"
                                onerror="this.src='https://placehold.co/960x720?text=Image+Missing'">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent px-5 pb-5 pt-24 text-white sm:px-7 sm:pb-7">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/70">Featured from the menu</p>
                                <div class="mt-2 flex items-end justify-between gap-4">
                                    <h2 class="font-display text-3xl font-medium leading-none sm:text-4xl">{{ $featuredProduct->name }}</h2>
                                    <x-ui.price :amount="$featuredProduct->price" size="lg" class="shrink-0 !text-white [&>span:first-child]:!text-white/65" />
                                </div>
                            </div>
                        </a>
                    @else
                        <div class="flex aspect-[4/3] items-center justify-center rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-canvas))] p-10">
                            <div class="max-w-xs text-center">
                                <x-application-logo class="mx-auto h-16 w-auto opacity-80" />
                                <p class="mt-5 font-display text-3xl text-[rgb(var(--cp-ink))]">Your menu is ready when you are.</p>
                            </div>
                        </div>
                    @endif

                    <div class="absolute -bottom-4 -left-2 hidden border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] px-4 py-3 shadow-sm sm:block lg:-left-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[rgb(var(--cp-muted))]">Currently viewing</p>
                        <p class="mt-1 text-sm font-semibold text-[rgb(var(--cp-ink))]">{{ $activeCategoryLabel }} &middot; {{ $visibleProductCount }} items</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="menu" class="scroll-mt-24 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-7 border-b border-[rgb(var(--cp-line))] pb-8 lg:grid-cols-[1fr_420px] lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--cp-brand))]">Cafe menu</p>
                        <h2 class="mt-2 font-display text-4xl font-medium tracking-[-0.035em] text-[rgb(var(--cp-ink))] sm:text-5xl">Find your next order</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-[rgb(var(--cp-muted))] sm:text-base">
                            Search by name or move through the menu categories. Every price shown here comes from the current catalog.
                        </p>
                    </div>

                    <form action="{{ route('dashboard') }}" method="GET" class="relative">
                        <label class="sr-only" for="search">Search the menu</label>
                        <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search drinks and food"
                            class="min-h-12 w-full rounded-lg border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] py-3 pl-4 pr-12 text-sm font-medium text-[rgb(var(--cp-ink))] shadow-sm placeholder:text-slate-400 focus:border-[rgb(var(--cp-brand))] focus:ring-[rgb(var(--cp-brand))]">
                        <input type="hidden" name="category" value="{{ request('category', 'all') }}">
                        <button type="submit" class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-[rgb(var(--cp-muted))] transition hover:bg-emerald-50 hover:text-[rgb(var(--cp-brand-strong))]" aria-label="Search the menu">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>

                <nav class="-mx-4 flex gap-7 overflow-x-auto border-b border-[rgb(var(--cp-line))] px-4 sm:mx-0 sm:px-0" aria-label="Menu categories">
                    @auth
                        <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'collections']) }}#menu"
                            class="shrink-0 border-b-2 px-1 py-4 text-sm font-semibold transition {{ request('category') === 'collections' ? 'border-[rgb(var(--cp-brand))] text-[rgb(var(--cp-brand-strong))]' : 'border-transparent text-[rgb(var(--cp-muted))] hover:border-emerald-200 hover:text-[rgb(var(--cp-ink))]' }}">
                            Saved recipes
                        </a>
                    @endauth

                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'all']) }}#menu"
                        class="shrink-0 border-b-2 px-1 py-4 text-sm font-semibold transition {{ request('category', 'all') === 'all' ? 'border-[rgb(var(--cp-brand))] text-[rgb(var(--cp-brand-strong))]' : 'border-transparent text-[rgb(var(--cp-muted))] hover:border-emerald-200 hover:text-[rgb(var(--cp-ink))]' }}">
                        Full menu
                    </a>

                    @foreach($allCategoryNames as $catName)
                        <a href="{{ route('dashboard', ['search' => request('search'), 'category' => $catName]) }}#menu"
                            class="shrink-0 border-b-2 px-1 py-4 text-sm font-semibold transition {{ request('category') === $catName ? 'border-[rgb(var(--cp-brand))] text-[rgb(var(--cp-brand-strong))]' : 'border-transparent text-[rgb(var(--cp-muted))] hover:border-emerald-200 hover:text-[rgb(var(--cp-ink))]' }}">
                            {{ $catName }}
                        </a>
                    @endforeach
                </nav>

                <div class="mt-10">
                    @if($category === 'collections')
                        @include('user.favorites.list', ['favorites' => $favorites])
                    @else
                        @include('user.products.index', ['menus' => $menus])
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
