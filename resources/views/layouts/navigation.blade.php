<nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-40" x-data="{ activeMenu: null }"
    @keydown.escape.window="activeMenu = null">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">

            {{-- Left --}}
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-10 w-auto" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Home Page
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('tangki.index')" :active="request()->routeIs('tangki.*')">
                            My Tangki
                        </x-nav-link>
                    @endauth
                </div>
            </div>

            {{-- Right --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-4">

                @auth
                    {{-- Cart --}}
                    <a href="{{ route('cart.index') }}"
                        class="relative p-2.5 text-gray-400 hover:text-blue-600 transition-all hover:scale-110">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>

                        @if($cartCount > 0)
                            <span class="absolute top-1 right-1 inline-flex items-center justify-center
                                                                 w-5 h-5 text-[10px] font-black text-white bg-blue-600
                                                                 rounded-full border-2 border-white
                                                                 transform translate-x-1/4 -translate-y-1/4">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    {{-- Notification --}}
                    <div class="relative">
                        <button @click="activeMenu = activeMenu === 'notification' ? null : 'notification'"
                            @click.away="if(activeMenu === 'notification') activeMenu = null"
                            class="relative p-2.5 text-gray-400 hover:text-blue-600 transition-all hover:scale-110">

                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                                                     a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341
                                                     C7.67 6.165 6 8.388 6 11v3.159
                                                     c0 .538-.214 1.055-.595 1.436L4 17h5
                                                     m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>

                            @if($navbarUnreadCount > 0)
                                <span class="absolute top-2.5 right-2.5 h-2.5 w-2.5
                                                                     rounded-full bg-red-500 ring-2 ring-white"></span>
                            @endif
                        </button>

                        <div x-show="activeMenu === 'notification'" x-cloak x-transition class="absolute right-0 mt-4 w-80 bg-white
                                                rounded-[1.5rem] shadow-2xl border border-gray-100 z-50 overflow-hidden">
                            <!-- ... (notifications content) ... -->
                            <div class="px-5 py-4 border-b bg-gray-50 flex justify-between items-center">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Notifications
                                </span>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @forelse($navbarNotifications as $notification)
                                    <div
                                        class="px-5 py-4 hover:bg-blue-50/50 border-b border-gray-50 last:border-0 transition-colors">
                                        <p class="text-xs text-gray-800 font-bold">
                                            {{ $notification->data['message'] ?? 'New notification' }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 mt-1 uppercase font-black tracking-tighter">
                                            Order #{{ $notification->data['bill_id'] ?? 'N/A' }}
                                            • {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                @empty
                                    <div class="px-5 py-8 text-center text-gray-400">
                                        <p class="text-xs font-bold uppercase tracking-widest">Empty tank ☕</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- User Dropdown --}}
                    <div class="relative">
                        <button @click="activeMenu = activeMenu === 'user' ? null : 'user'"
                            @click.away="if(activeMenu === 'user') activeMenu = null" class="inline-flex items-center px-4 py-2 bg-gray-50 rounded-xl
                                               text-sm font-black text-gray-900 border border-transparent
                                               hover:bg-gray-100 transition-all active:scale-95">
                            <span>{{ auth()->user()->name }}</span>
                            <svg class="ms-2 h-4 w-4 fill-current transition-transform duration-200"
                                :class="{ 'rotate-180': activeMenu === 'user' }" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586
                                                     l3.293-3.293a1 1 0 111.414 1.414
                                                     l-4 4a1 1 0 01-1.414 0l-4-4
                                                     a1 1 0 010-1.414z" />
                            </svg>
                        </button>

                        <div x-show="activeMenu === 'user'" x-cloak x-transition class="absolute right-0 mt-4 w-48 bg-white
                                                border border-gray-100 rounded-2xl shadow-2xl py-2 z-50 overflow-hidden">
                            <x-dropdown-link :href="route('profile.edit')" class="font-bold">
                                Personal Center
                            </x-dropdown-link>
                            <hr class="border-gray-50 my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" class="text-red-600 font-bold"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Logout
                                </x-dropdown-link>
                            </form>
                        </div>
                    </div>

                @else

                    <div class="flex items-center space-x-4">
                        <button @click="authModal = 'login'"
                            class="text-sm font-black text-gray-500 hover:text-gray-900 uppercase tracking-widest transition-colors">
                            Login
                        </button>
                        <button @click="authModal = 'register'"
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:shadow-blue-200 transition-all hover:-translate-y-0.5 active:translate-y-0">
                            Register
                        </button>
                    </div>
                @endauth

            </div>
        </div>
    </div>
</nav>