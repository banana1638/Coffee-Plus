<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('admin.dashboard') }}">
                        <x-application-logo class="block h-9 w-auto" />
                    </a>
                    <span
                        class="ml-3 text-xs font-black text-gray-400 uppercase tracking-widest border-l pl-3 border-gray-200">Admin</span>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if(Auth::guard('admin')->user()->isStaff())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            Dashboard
                        </x-nav-link>
                    @endif

                    @if(Auth::guard('admin')->user()->isOwner())
                        <x-nav-link :href="route('admin.owner.dashboard')"
                            :active="request()->routeIs('admin.owner.dashboard')">
                            Analytics
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        Products
                    </x-nav-link>
                    <x-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                        Orders
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="relative">
                    <button onclick="toggleAdminDropdown(event)"
                        class="inline-flex items-center px-4 py-2 border border-blue-50 text-xs font-black rounded-xl text-gray-900 bg-white hover:bg-gray-50 focus:outline-none transition-all uppercase tracking-tighter">
                        <div>{{ Auth::guard('admin')->user()->name }}</div>
                        <div
                            class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-600 rounded text-[8px] uppercase tracking-widest">
                            {{ Auth::guard('admin')->user()->role }}
                        </div>
                        <div class="ms-1">
                            <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                <path
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </button>

                    <div id="adminDropdownMenu"
                        class="hidden absolute right-0 z-50 mt-2 w-48 rounded-2xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 py-2 transition-all duration-200 opacity-0 scale-95 origin-top-right border border-gray-50">

                        <div
                            class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 mb-1">
                            Control Panel
                        </div>

                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-3 text-sm text-red-600 font-black italic hover:bg-red-50 transition-colors uppercase tracking-widest">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleAdminDropdown(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById('adminDropdownMenu');
        if (!menu) return;

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            setTimeout(() => {
                menu.classList.remove('opacity-0', 'scale-95');
                menu.classList.add('opacity-100', 'scale-100');
            }, 10);
        } else {
            closeAdminDropdown();
        }
    }

    function closeAdminDropdown() {
        const menu = document.getElementById('adminDropdownMenu');
        if (!menu) return;
        menu.classList.remove('opacity-100', 'scale-100');
        menu.classList.add('opacity-0', 'scale-95');
        setTimeout(() => menu.classList.add('hidden'), 200);
    }

    window.addEventListener('click', closeAdminDropdown);
</script>