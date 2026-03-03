<x-guest-layout>
    <div class="min-h-screen bg-gray-50/50 flex flex-col justify-center items-center px-4">

        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <div
                    class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-[2rem] shadow-2xl shadow-gray-200 mb-6 p-4">
                    <x-application-logo class="w-full h-full" />
                </div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Admin Portal</h2>
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-2">Coffee Plus+ Management
                </p>
            </div>

            <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label
                            class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Admin
                            Email</label>
                        <input type="email" name="email" required autofocus
                            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                        @error('email') <p class="text-red-500 text-[10px] font-bold mt-2 ml-4">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label
                            class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4 mb-2 block">Security
                            Token</label>
                        <input type="password" name="password" required
                            class="w-full px-8 py-5 bg-gray-50 border-none rounded-[1.5rem] focus:ring-4 focus:ring-blue-500/5 transition-all font-bold text-gray-800 placeholder:text-gray-300">
                    </div>

                    <div class="flex items-center ml-4">
                        <input type="checkbox" name="remember" id="remember"
                            class="rounded-md border-gray-200 text-blue-600 focus:ring-blue-500">
                        <label for="remember"
                            class="ml-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest cursor-pointer">Stay
                            Authenticated</label>
                    </div>

                    <button type="submit"
                        class="w-full py-6 bg-gray-900 text-white rounded-[2rem] font-black text-lg shadow-2xl shadow-gray-200 hover:bg-blue-600 transition-all hover:-translate-y-1 active:scale-[0.98]">
                        ACCESS CONSOLE
                    </button>
                </form>
            </div>

            <p class="text-center mt-8 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                Protected Environment &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
</x-guest-layout>