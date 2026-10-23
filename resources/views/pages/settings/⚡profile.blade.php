<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Pengaturan Profil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profil berhasil diperbarui.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading', [
        'pageIcon' => 'fa-user',
        'pageTitle' => __('Profil'),
        'pageDescription' => __('Perbarui nama dan alamat email Anda')
    ])

    <!-- Settings Navigation Tabs -->
    <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-zinc-800">
        <a href="{{ route('profile.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('profile.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Profil') }}
        </a>
        <a href="{{ route('security.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('security.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Keamanan') }}
        </a>
        <a href="{{ route('appearance.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('appearance.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Tampilan') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-slate-200/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="px-6 py-6">
        <form wire:submit="updateProfileInformation" class="space-y-5">
            <!-- Name Input -->
            <div>
                <label for="name" class="block text-sm font-medium text-[#212121] dark:text-zinc-200 mb-2">{{ __('Nama') }}</label>
                <input 
                    id="name"
                    wire:model="name" 
                    type="text"
                    required 
                    autofocus 
                    autocomplete="name"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-100 placeholder-[#828282] dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent transition-colors"
                >
                @error('name')
                    <p class="text-red-600 dark:text-rose-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-sm font-medium text-[#212121] dark:text-zinc-200 mb-2">{{ __('Email') }}</label>
                <input 
                    id="email"
                    wire:model="email" 
                    type="email"
                    required 
                    autocomplete="email"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-100 placeholder-[#828282] dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent transition-colors"
                >
                @error('email')
                    <p class="text-red-600 dark:text-rose-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-3 p-3 bg-yellow-50 dark:bg-amber-950/40 rounded-lg border border-yellow-100 dark:border-amber-900/60">
                        <p class="text-sm text-[#212121] dark:text-amber-200">
                            {{ __('Alamat email Anda belum diverifikasi.') }}
                            <button type="button" wire:click.prevent="resendVerificationNotification" class="text-[#1a73e8] dark:text-blue-400 font-medium hover:underline">
                                {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="text-sm text-green-600 dark:text-emerald-400 font-medium mt-2">
                                {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex pt-4">
                <button 
                    type="submit"
                    class="px-6 py-2.5 bg-[#1a73e8] hover:bg-[#1a5fc0] text-white rounded-lg font-medium text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2"
                    data-test="update-profile-button"
                >
                    {{ __('Simpan') }}
                </button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
        </div>
    </div>
</section>
