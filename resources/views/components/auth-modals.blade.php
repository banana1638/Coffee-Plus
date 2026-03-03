<div x-data="{ 
    tab: 'login',
    email: '',
    password: '',
    name: '',
    password_confirmation: '',
    errors: {},
    loading: false,

    async submitLogin() {
        this.loading = true;
        this.errors = {};
        try {
            const response = await fetch('{{ route('login') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: this.email, password: this.password })
            });
            const data = await response.json();
            if (response.ok) {
                window.location.reload();
            } else {
                this.errors = data.errors || { email: [data.message] };
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },

    async submitRegister() {
        this.loading = true;
        this.errors = {};
        try {
            const response = await fetch('{{ route('register') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    name: this.name, 
                    email: this.email, 
                    password: this.password, 
                    password_confirmation: this.password_confirmation 
                })
            });
            const data = await response.json();
            if (response.ok) {
                window.location.reload();
            } else {
                this.errors = data.errors || { email: [data.message] };
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    }
}" x-init="$watch('authModal', value => { if (value) tab = value })" x-show="authModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click.self="authModal = null"
    @keydown.escape.window="authModal = null"
    @open-auth-modal.window="authModal = $event.detail.tab; tab = $event.detail.tab">

    <div class="bg-white w-full max-w-[480px] rounded-[2.5rem] shadow-2xl overflow-hidden relative"
        x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">

        <!-- Close Button -->
        <button @click="authModal = null"
            class="absolute top-6 right-6 text-gray-400 hover:text-gray-900 transition translate-x-2 -translate-y-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="p-8 sm:p-12">
            <!-- Tabs with Sliding Indicator -->
            <div class="relative flex gap-8 mb-12 bg-gray-50 p-2 rounded-2xl">
                <div class="absolute inset-y-2 transition-all duration-300 ease-out bg-white shadow-sm rounded-xl"
                    :style="tab === 'login' ? 'left: 8px; width: calc(50% - 12px)' : 'left: calc(50% + 4px); width: calc(50% - 12px)'">
                </div>
                <button @click="tab = 'login'" :class="tab === 'login' ? 'text-gray-900' : 'text-gray-400'"
                    class="relative z-10 flex-1 py-3 text-xs font-black uppercase tracking-widest transition-colors">
                    Login
                </button>
                <button @click="tab = 'register'" :class="tab === 'register' ? 'text-gray-900' : 'text-gray-400'"
                    class="relative z-10 flex-1 py-3 text-xs font-black uppercase tracking-widest transition-colors">
                    Register
                </button>
            </div>

            <!-- Forms Container with Sliding Animation -->
            <div class="relative overflow-hidden">
                <div class="flex transition-transform duration-500 ease-out"
                    :style="tab === 'login' ? 'transform: translateX(0%)' : 'transform: translateX(-100%)'">

                    <!-- Login Form -->
                    <div class="w-full shrink-0 pr-4">
                        <div class="mb-8">
                            <h2 class="text-3xl font-black text-gray-900 tracking-tight">Welcome Back</h2>
                            <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-widest">Login to your
                                account</p>
                        </div>

                        <form @submit.prevent="submitLogin" class="space-y-5">
                            <div>
                                <input type="email" x-model="email" placeholder="Email Address" required
                                    class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                <template x-if="errors.email">
                                    <p class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1"
                                        x-text="errors.email[0]"></p>
                                </template>
                            </div>
                            <div>
                                <input type="password" x-model="password" placeholder="Password" required
                                    class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                <template x-if="errors.password">
                                    <p class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1"
                                        x-text="errors.password[0]"></p>
                                </template>
                            </div>
                            <button type="submit" :disabled="loading"
                                class="w-full py-4 bg-blue-600 text-white rounded-2xl text-sm font-black uppercase tracking-[0.2em] shadow-lg shadow-blue-100 hover:shadow-xl transition transform active:scale-[0.98] disabled:opacity-50 overflow-hidden relative group">
                                <span class="relative z-10" x-show="!loading">Log In</span>
                                <span class="relative z-10" x-show="loading">Logging in...</span>
                                <div
                                    class="absolute inset-0 bg-blue-700 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                </div>
                            </button>
                        </form>
                    </div>

                    <!-- Register Form -->
                    <div class="w-full shrink-0 pl-4">
                        <div class="mb-8">
                            <h2 class="text-3xl font-black text-gray-900 tracking-tight">Create Account</h2>
                            <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-widest">Join the coffee
                                club</p>
                        </div>

                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <div>
                                <input type="text" x-model="name" placeholder="Full Name" required
                                    class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                <template x-if="errors.name">
                                    <p class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1"
                                        x-text="errors.name[0]"></p>
                                </template>
                            </div>
                            <div>
                                <input type="email" x-model="email" placeholder="Email Address" required
                                    class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                <template x-if="errors.email">
                                    <p class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1"
                                        x-text="errors.email[0]"></p>
                                </template>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <input type="password" x-model="password" placeholder="Password" required
                                        class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                </div>
                                <div>
                                    <input type="password" x-model="password_confirmation" placeholder="Confirm"
                                        required
                                        class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm">
                                </div>
                            </div>
                            <template x-if="errors.password">
                                <p class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1"
                                    x-text="errors.password[0]"></p>
                            </template>
                            <button type="submit" :disabled="loading"
                                class="w-full py-4 bg-gray-900 text-white rounded-2xl text-sm font-black uppercase tracking-[0.2em] shadow-lg shadow-gray-100 hover:bg-black transition transform active:scale-[0.98] disabled:opacity-50 overflow-hidden relative group">
                                <span class="relative z-10" x-show="!loading">Register Now</span>
                                <span class="relative z-10" x-show="loading">Creating...</span>
                                <div
                                    class="absolute inset-0 bg-black translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                </div>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>