<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Konfirmasi Password')"
            :description="__('Ini adalah area aman aplikasi. Silakan konfirmasi password Anda untuk melanjutkan.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Konfirmasi dengan passkey')"
            :loading-label="__('Mengonfirmasi...')"
            :separator="__('Atau konfirmasi dengan password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-[#212121] dark:text-zinc-200 mb-2">{{ __('Password') }}</label>
                <input 
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Masukkan password Anda') }}"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg text-sm text-[#212121] dark:text-zinc-100 placeholder-[#828282] dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent transition-colors"
                />
                @error('password')
                    <p class="text-red-600 dark:text-rose-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <button 
                type="submit"
                class="w-full px-4 py-2.5 bg-[#1a73e8] hover:bg-[#1a5fc0] text-white rounded-lg font-medium text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2"
                data-test="confirm-password-button"
            >
                {{ __('Konfirmasi') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
