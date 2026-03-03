<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<x-app-layout>
    @if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
        class="fixed top-10 left-1/2 transform -translate-x-1/2 z-[100] w-full max-w-sm px-4">
        <div
            class="bg-gray-900/90 backdrop-blur-xl text-white px-6 py-4 rounded-[2rem] shadow-2xl flex items-center justify-between border border-white/10">
            <div class="flex items-center gap-3">
                <div class="bg-green-500 rounded-full p-1">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="font-bold text-sm">
                    {{ session('status') === 'profile-updated' ? 'Profile updated!' : 'Action successful!' }}
                </p>
            </div>
            <button @click="show = false" class="text-white/30 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path>
                </svg>
            </button>
        </div>
    </div>
    @endif

    <div class="py-12 bg-gray-50/50 min-h-screen scroll-smooth">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-10 flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Profile</h2>
                    <p class="text-gray-500 mt-1">Manage your coffee tank and security settings.</p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <div class="w-full lg:w-1/3 lg:sticky lg:top-8 space-y-6">

                    <div
                        class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 overflow-hidden relative">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-50 rounded-full opacity-50"></div>

                        <div class="relative">
                            <div
                                class="w-24 h-24 bg-gradient-to-tr from-blue-600 to-blue-400 rounded-[2rem] flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-100 text-white text-3xl font-black">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <h3 class="text-xl font-black text-gray-900 text-center">{{ Auth::user()->name }}</h3>
                            <p class="text-sm text-gray-400 mb-6 text-center">{{ Auth::user()->email }}</p>

                            <div class="grid grid-cols-2 gap-4 border-t border-gray-50 pt-6">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Balance</p>
                                    <p class="text-lg font-black text-gray-800">RM {{
                                        number_format(Auth::user()->tangki_balance, 2) }}</p>
                                </div>
                                <div class="text-center border-l border-gray-100">
                                    <p class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">Storage</p>
                                    <p class="text-lg font-black text-blue-600">{{ Auth::user()->tangki_oz }} <span
                                            class="text-xs">oz</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-3 shadow-sm border border-gray-100">
                        <nav class="space-y-1">
                            <a href="#profile-info"
                                class="group flex items-center gap-3 px-6 py-4 hover:bg-gray-50 text-gray-600 hover:text-blue-600 rounded-2xl font-bold transition-all">
                                <span class="text-xl group-hover:scale-110 transition">👤</span>
                                <span>Profile Information</span>
                            </a>
                            <a href="#password-info"
                                class="group flex items-center gap-3 px-6 py-4 hover:bg-gray-50 text-gray-600 hover:text-blue-600 rounded-2xl font-bold transition-all">
                                <span class="text-xl group-hover:scale-110 transition">🔒</span>
                                <span>Update Password</span>
                            </a>
                            <a href="#delete-account"
                                class="group flex items-center gap-3 px-6 py-4 hover:bg-red-50 text-gray-600 hover:text-red-500 rounded-2xl font-bold transition-all">
                                <span class="text-xl group-hover:scale-110 transition">⚠️</span>
                                <span>Delete Account</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="w-full lg:w-2/3 space-y-10">

                    <section id="profile-info" class="scroll-mt-8">
                        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-gray-100">
                            <div class="max-w-xl">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </section>

                    <section id="password-info" class="scroll-mt-8">
                        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-gray-100">
                            <div class="max-w-xl">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>
                    </section>

                    <section id="delete-account" class="scroll-mt-8">
                        <div
                            class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-gray-100 border-b-8 border-b-red-500">
                            <div class="max-w-xl">
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                setActive(this);
            });
        });

        function setActive(link) {
            document.querySelectorAll('nav a').forEach(a => {
                a.classList.remove('bg-blue-50', 'text-blue-600');
                a.classList.add('text-gray-600');
            });
            link.classList.add('bg-blue-50', 'text-blue-600');
            link.classList.remove('text-gray-600');
        }

        window.addEventListener('scroll', () => {
            let current = "";
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('nav a');

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                if (link.getAttribute('href').includes(current)) {
                    setActive(link);
                }
            });
        });
    </script>
</x-app-layout>