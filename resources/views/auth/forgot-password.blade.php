<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50/50 py-12 px-6">
        
        <div class="w-full sm:max-w-[480px] bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-10 sm:p-12 transition-all">
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[1.5rem] mb-6 shadow-xl shadow-blue-100">
                    <span class="text-4xl text-white">🔑</span>
                </div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Reset Password</h2>
                <p class="text-gray-500 mt-2 font-medium leading-relaxed">
                    {{ __('Forgot your password? No problem. Just let us know your email address.') }}
                </p>
            </div>

            <x-auth-session-status class="mb-6" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                    <input id="email" type="email" name="email" :value="old('email')" required autofocus
                        class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm placeholder-gray-300" 
                        placeholder="name@example.com">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="pt-2 space-y-4">
                    <button type="submit" class="w-full py-4 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl text-lg font-black shadow-lg shadow-blue-100 hover:shadow-xl transition transform active:scale-[0.98]">
                        {{ __('Email Reset Link') }}
                    </button>
                    
                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                        <div class="relative flex justify-center text-xs uppercase"><span class="bg-white px-4 text-gray-400 font-bold tracking-[0.2em]">or</span></div>
                    </div>

                    <a href="{{ route('login') }}" class="flex justify-center w-full py-4 bg-gray-900 text-white rounded-2xl text-lg font-black hover:bg-black transition transform active:scale-[0.98] shadow-lg shadow-gray-100">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>

        <p class="mt-10 text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em]">
            © {{ date('Y') }} Coffee Plus+ System
        </p>
    </div>
</x-guest-layout>