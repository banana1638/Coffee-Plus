<section class="space-y-6">
    <header>
        <h2 class="text-base font-semibold text-rose-700">{{ __('Delete Account') }}</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
        </p>
    </header>

    <x-ui.button type="button" variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete Account') }}
    </x-ui.button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-base font-semibold text-slate-950">{{ __('Are you sure you want to delete your account?') }}</h2>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6 space-y-2">
                <label for="password" class="sr-only">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" placeholder="{{ __('Password') }}"
                    class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-rose-500 focus:ring-rose-500">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit" variant="danger">
                    {{ __('Delete Account') }}
                </x-ui.button>
            </div>
        </form>
    </x-modal>
</section>
