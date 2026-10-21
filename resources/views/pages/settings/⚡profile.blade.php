<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
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

        Flux::toast(variant: 'success', text: __('Profile updated.'));
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
    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="space-y-5">
            <!-- Name Input -->
            <div>
                <label for="name" class="block text-sm font-medium text-[#212121] mb-2">{{ __('Name') }}</label>
                <input 
                    id="name"
                    wire:model="name" 
                    type="text"
                    required 
                    autofocus 
                    autocomplete="name"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm text-[#212121] placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent transition-colors"
                >
                @error('name')
                    <p class="text-red-600 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-sm font-medium text-[#212121] mb-2">{{ __('Email') }}</label>
                <input 
                    id="email"
                    wire:model="email" 
                    type="email"
                    required 
                    autocomplete="email"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm text-[#212121] placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent transition-colors"
                >
                @error('email')
                    <p class="text-red-600 text-xs mt-1.5">{{ $message }}</p>
                @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                        <p class="text-sm text-[#212121]">
                            {{ __('Your email address is unverified.') }}
                            <button type="button" wire:click.prevent="resendVerificationNotification" class="text-[#1a73e8] font-medium hover:underline">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="text-sm text-green-600 font-medium mt-2">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex pt-4">
                <button 
                    type="submit"
                    class="px-6 py-2.5 bg-[#1a73e8] text-white rounded-lg font-medium text-sm transition-all hover:bg-[#1a5fc0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2"
                    data-test="update-profile-button"
                >
                    {{ __('Save') }}
                </button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
