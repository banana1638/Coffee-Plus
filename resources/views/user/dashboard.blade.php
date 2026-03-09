<x-app-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-10">
                @auth
                    <div class="bg-white rounded-[2.5rem] p-6 md:p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row items-center gap-8">
                        <div class="w-32 md:w-40 shrink-0">
                            @include('components.tank-visualization')
                        </div>

                        <div class="flex-1 grid grid-cols-2 gap-4 w-full md:border-l md:pl-8 border-gray-100">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Current Storage</p>
                                <p class="text-2xl md:text-3xl font-black text-blue-600">
                                    {{ Auth::user()->tangki_oz }} <span class="text-xs font-normal text-gray-400">oz</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Account Balance</p>
                                <p class="text-2xl md:text-3xl font-black text-gray-800">
                                    RM {{ number_format(Auth::user()->tangki_balance, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-center md:text-right shrink-0">
                            <h2 class="text-xl font-black text-gray-900 leading-tight">
                                Welcome, <span class="text-blue-600">{{ Auth::user()->name }}</span>!
                            </h2>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Coffee Member</p>

                            <a href="{{ route('tangki.index') }}" class="inline-block mt-4 px-6 py-2 bg-blue-50 text-blue-600 rounded-xl text-xs font-black hover:bg-blue-600 hover:text-white transition-all">
                                MANAGE TANK
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mb-8">
                        <h2 class="text-3xl font-black text-gray-900">Coffee Plus+ ☕</h2>
                        <p class="text-gray-500">Log in to manage your digital tank and earn rewards.</p>
                    </div>
                @endauth
            </div>

            <div class="mb-8 flex justify-end">
                <form action="{{ route('dashboard') }}" method="GET" class="relative w-full md:w-96">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search coffee..." 
                        class="w-full pl-6 pr-12 py-4 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500/20 transition-all font-medium text-gray-700"
                    >
                    <input type="hidden" name="category" value="{{ request('category', 'all') }}">
                    <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-3 overflow-x-auto pb-4 no-scrollbar">
                <a href="{{ route('dashboard', ['search' => request('search'), 'category' => 'all']) }}" 
                   class="px-6 py-3 rounded-xl font-bold text-sm transition-all whitespace-nowrap {{ request('category', 'all') === 'all' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white text-gray-500 hover:bg-gray-100' }}">
                    All Items
                </a>
                @foreach($allCategoryNames as $catName)
                    <a href="{{ route('dashboard', ['search' => request('search'), 'category' => $catName]) }}" 
                       class="px-6 py-3 rounded-xl font-bold text-sm transition-all whitespace-nowrap {{ request('category') === $catName ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white text-gray-500 hover:bg-gray-100' }}">
                        {{ $catName }}
                    </a>
                @endforeach
            </div>

            @include('user.products.index', ['menus' => $menus])

        </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-app-layout>