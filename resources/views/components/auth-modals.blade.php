@php
    $initialAuthTab = in_array(request('auth'), ['login', 'register'], true) ? request('auth') : null;
@endphp

<div x-data="{
    tab: @js($initialAuthTab ?? 'login'),
    email: '',
    password: '',
    name: '',
    phone: '',
    address: '',
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
                    password_confirmation: this.password_confirmation,
                    phone: this.phone || null,
                    address: this.address || null
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
}" x-init="$watch('authModal', value => { if (value) tab = value })" x-show="authModal" @if(! $initialAuthTab) x-cloak @endif
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
    x-transition.opacity @click.self="$dispatch('close-auth-modal')" @keydown.escape.window="$dispatch('close-auth-modal')"
    @open-auth-modal.window="tab = $event.detail.tab">

    <div class="relative max-h-[calc(100vh-2rem)] w-full max-w-[480px] overflow-y-auto rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-lg"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">
        <div class="flex justify-end px-4 pt-4">
            <button type="button" @click="$dispatch('close-auth-modal')"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[rgb(var(--cp-line))] bg-white text-slate-500 shadow-sm transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                aria-label="Close authentication modal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 pb-6 pt-2 sm:px-8 sm:pb-8">
            <div class="relative mb-8 grid grid-cols-2 rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 p-1">
                <div class="absolute inset-y-1 left-1 w-[calc(50%-0.25rem)] rounded-lg bg-emerald-800 shadow-sm transition-transform duration-300 ease-out"
                    :class="tab === 'register' ? 'translate-x-full' : 'translate-x-0'"></div>
                <button type="button" @click="tab = 'login'"
                    :class="tab === 'login' ? 'text-white' : 'text-slate-600 hover:text-slate-950'"
                    class="relative z-10 rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200">
                    Login
                </button>
                <button type="button" @click="tab = 'register'"
                    :class="tab === 'register' ? 'text-white' : 'text-slate-600 hover:text-slate-950'"
                    class="relative z-10 rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200">
                    Register
                </button>
            </div>

            <div class="overflow-x-hidden">
                <div class="flex transition-transform duration-300 ease-out"
                    style="{{ $initialAuthTab === 'register' ? 'transform: translateX(-100%);' : 'transform: translateX(0%);' }}"
                    :style="tab === 'register' ? 'transform: translateX(-100%)' : 'transform: translateX(0%)'">
                    <div class="w-full shrink-0">
                        <div class="mb-6">
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Welcome Back</h2>
                            <p class="mt-1 text-sm text-slate-600">Login to your Coffee-Plus account.</p>
                        </div>

                        <form @submit.prevent="submitLogin" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">Email Address</label>
                                <input type="email" x-model="email" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <template x-if="errors.email">
                                    <p class="text-sm font-medium text-rose-600" x-text="errors.email[0]"></p>
                                </template>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">Password</label>
                                <input type="password" x-model="password" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <template x-if="errors.password">
                                    <p class="text-sm font-medium text-rose-600" x-text="errors.password[0]"></p>
                                </template>
                            </div>
                            <x-ui.button type="submit" x-bind:disabled="loading" class="w-full">
                                <span x-show="!loading">Log In</span>
                                <span x-show="loading" x-cloak>Logging in...</span>
                            </x-ui.button>
                        </form>
                    </div>

                    <div class="w-full shrink-0 pl-1">
                        <div class="mb-6">
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Create Account</h2>
                            <p class="mt-1 text-sm text-slate-600">Join Coffee-Plus and start collecting rewards.</p>
                        </div>

                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">Full Name</label>
                                <input type="text" x-model="name" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <template x-if="errors.name">
                                    <p class="text-sm font-medium text-rose-600" x-text="errors.name[0]"></p>
                                </template>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">Email Address</label>
                                <input type="email" x-model="email" required
                                    class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <template x-if="errors.email">
                                    <p class="text-sm font-medium text-rose-600" x-text="errors.email[0]"></p>
                                </template>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-700">Password</label>
                                    <input type="password" x-model="password" required
                                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-700">Confirm</label>
                                    <input type="password" x-model="password_confirmation" required
                                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                </div>
                            </div>
                            <template x-if="errors.password">
                                <p class="text-sm font-medium text-rose-600" x-text="errors.password[0]"></p>
                            </template>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-700">Phone</label>
                                    <input type="tel" x-model="phone"
                                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-700">Address</label>
                                    <input type="text" x-model="address"
                                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                </div>
                            </div>
                            <x-ui.button type="submit" x-bind:disabled="loading" class="w-full">
                                <span x-show="!loading">Register Now</span>
                                <span x-show="loading" x-cloak>Creating...</span>
                            </x-ui.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
