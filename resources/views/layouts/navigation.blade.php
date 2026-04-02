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
                                                rounded-[2rem] shadow-2xl border border-gray-100 z-50 overflow-hidden ring-1 ring-black/5">
                            <div class="px-6 py-4 border-b bg-gray-50/50 flex justify-between items-center">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Notifications
                                </span>
                                @if($navbarUnreadCount > 0)
                                    <a href="{{ route('notifications.markAllAsRead') }}"
                                        class="text-[9px] font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 transition-colors">
                                        Mark all as read
                                    </a>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                                @forelse($navbarNotifications as $notification)
                                    <div
                                        class="group relative flex items-start px-6 py-4 transition-all duration-200 {{ $notification->read_at ? 'opacity-75' : 'bg-blue-50/30' }} hover:bg-gray-50">

                                        {{-- Unread Dot --}}
                                        @if(!$notification->read_at)
                                            <div
                                                class="absolute left-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-blue-600 rounded-full shadow-[0_0_8px_rgba(37,99,235,0.5)]">
                                            </div>
                                        @endif

                                        <div class="flex-1 min-w-0 pr-8">
                                            <a href="{{ route('notifications.markAsRead', $notification->id) }}"
                                                class="block">
                                                <p
                                                    class="text-[13px] leading-snug {{ $notification->read_at ? 'text-gray-600 font-medium' : 'text-gray-900 font-bold' }}">
                                                    {{ $notification->data['message'] ?? 'New notification' }}
                                                </p>
                                                <div class="flex items-center mt-1.5 space-x-2">
                                                    <span
                                                        class="text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                                        #{{ $notification->data['bill_id'] ?? 'N/A' }}
                                                    </span>
                                                    <span class="text-[9px] font-black text-gray-300">•</span>
                                                    <span
                                                        class="text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </a>
                                        </div>

                                        {{-- Delete Button --}}
                                        <div
                                            class="absolute right-4 top-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <form action="{{ route('notifications.destroy', $notification->id) }}"
                                                method="POST" onsubmit="return confirm('Delete this notification?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-6 py-12 text-center">
                                        <div
                                            class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">No
                                            Notifications</p>
                                        <p class="text-[9px] text-gray-300 mt-1 uppercase font-black">Everything is up
                                            to date ☕</p>
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