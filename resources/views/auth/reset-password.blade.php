<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50/50 py-12 px-6">
        
        <div class="w-full sm:max-w-[480px] bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-10 sm:p-12 transition-all">
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[1.5rem] mb-6 shadow-xl shadow-blue-100">
                    <span class="text-4xl text-white">🔄</span>
                </div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">New Password</h2>
                <p class="text-gray-500 mt-2 font-medium">Create a strong password for your account</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus readonly
                        class="w-full px-6 py-4 bg-gray-100 border-2 border-transparent rounded-2xl text-gray-500 font-bold shadow-sm cursor-not-allowed">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">New Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm"
                        placeholder="••••••••">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-0 transition duration-200 text-gray-900 font-bold shadow-sm"
                        placeholder="••••••••">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl text-lg font-black shadow-lg shadow-blue-100 hover:shadow-xl transition transform active:scale-[0.98]">
                        {{ __('Reset Password') }}
                    </button>
                    
                    <div class="mt-6 text-center">
                        <a href="{{ route('login') }}" class="text-xs font-black text-gray-400 uppercase hover:text-blue-600 transition tracking-widest">
                            Return to login
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <p class="mt-10 text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em]">
            © {{ date('Y') }} Coffee Plus+ System
        </p>
    </div>
</x-guest-layout>