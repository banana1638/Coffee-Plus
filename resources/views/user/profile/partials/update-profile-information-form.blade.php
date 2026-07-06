<section>
    <header>
        <h2 class="text-base font-bold text-[rgb(var(--cp-ink))]">{{ __('Profile Information') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __("Update your account's profile information and email address.") }}</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div class="space-y-2">
            <label for="name" class="text-sm font-medium text-slate-700">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="font-semibold text-emerald-700 hover:text-emerald-900">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-700">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
