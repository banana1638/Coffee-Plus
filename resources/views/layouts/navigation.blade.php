<nav class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur" x-data="{ activeMenu: null, mobileOpen: false }"
    @keydown.escape.window="activeMenu = null; mobileOpen = false">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <x-application-logo class="block h-9 w-auto" />
                    <span class="hidden text-sm font-semibold text-slate-950 sm:block">Coffee-Plus</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('dashboard') }}"
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                        Menu
                    </a>

                    @auth
                        <a href="{{ route('tangki.index') }}"
                            class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('tangki.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                            My Tangki
                        </a>
                    @endauth
                </div>
            </div>

            <div class="hidden items-center gap-2 md:flex">
                @auth
                    <a href="{{ route('cart.index') }}"
                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-indigo-700"
                        aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        @if($cartCount > 0)
                            <span id="cart-badge" class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1 text-xs font-semibold text-white ring-2 ring-white">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    <div class="relative">
                        <button type="button" @click="activeMenu = activeMenu === 'notification' ? null : 'notification'"
                            @click.away="if(activeMenu === 'notification') activeMenu = null"
                            class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-indigo-700"
                            aria-label="Notifications">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($navbarUnreadCount > 0)
                                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                            @endif
                        </button>

                        <div x-show="activeMenu === 'notification'" x-cloak x-transition
                            class="absolute right-0 mt-3 w-96 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notifications</span>
                                @if($navbarUnreadCount > 0)
                                    <a href="{{ route('notifications.markAllAsRead') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                                        Mark all as read
                                    </a>
                                @endif
                            </div>
                            <div class="max-h-96 divide-y divide-slate-200 overflow-y-auto">
                                @forelse($navbarNotifications as $notification)
                                    <div class="{{ $notification->read_at ? '' : 'bg-indigo-50/50' }} group relative px-4 py-3 transition hover:bg-slate-50">
                                        <a href="{{ route('notifications.markAsRead', $notification->id) }}" class="block pr-8">
                                            <p class="text-sm {{ $notification->read_at ? 'text-slate-600' : 'font-semibold text-slate-950' }}">
                                                {{ $notification->data['message'] ?? 'New notification' }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                #{{ $notification->data['bill_id'] ?? 'N/A' }} · {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </a>
                                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST"
                                            onsubmit="return confirm('Delete this notification?')"
                                            class="absolute right-3 top-3 opacity-0 transition group-hover:opacity-100">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Delete notification">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="px-4 py-10 text-center">
                                        <p class="text-sm font-semibold text-slate-500">No notifications</p>
                                        <p class="mt-1 text-xs text-slate-400">Everything is up to date.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" @click="activeMenu = activeMenu === 'user' ? null : 'user'"
                            @click.away="if(activeMenu === 'user') activeMenu = null"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <span>{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 transition" :class="{ 'rotate-180': activeMenu === 'user' }" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </button>

                        <div x-show="activeMenu === 'user'" x-cloak x-transition
                            class="absolute right-0 mt-3 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-lg">
                            <x-dropdown-link :href="route('profile.edit')">Personal Center</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" class="text-rose-600"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Logout
                                </x-dropdown-link>
                            </form>
                        </div>
                    </div>
                @else
                    <button type="button" @click="$store.authModal.show('login')" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950">
                        Login
                    </button>
                    <x-ui.button type="button" @click="$store.authModal.show('register')" size="sm">
                        Register
                    </x-ui.button>
                @endauth
            </div>

            <button type="button" @click="mobileOpen = ! mobileOpen"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 md:hidden"
                aria-label="Toggle navigation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-cloak x-transition class="border-t border-slate-200 bg-white px-4 py-3 md:hidden">
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Menu</a>
            @auth
                <a href="{{ route('tangki.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">My Tangki</a>
                <a href="{{ route('cart.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cart ({{ $cartCount }})</a>
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Profile</a>
            @else
                <button type="button" @click="$store.authModal.show('login'); mobileOpen = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">Login</button>
                <button type="button" @click="$store.authModal.show('register'); mobileOpen = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Register</button>
            @endauth
        </div>
    </div>
</nav>
