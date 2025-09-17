{{-- Update Profile Information --}}
<div class="dark:bg-gray-800 rounded-lg shadow-one p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-dark dark:text-white">{{ __('profile.profile_information') }}</h3>
        <p class="mt-1 text-sm text-body-color">
            {{ __('profile.profile_information_desc') }}
        </p>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('profile.full_name') }}
            </label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   value="{{ old('name', $user->name) }}"
                   required
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
            @error('name')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('profile.email_address') }}
            </label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="{{ old('email', $user->email) }}"
                   required
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
            @error('email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('profile.email_unverified') }}
                        <button form="send-verification" type="submit" class="underline text-yellow-600 dark:text-yellow-400 hover:text-yellow-500">
                            {{ __('profile.resend_verification') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                            {{ __('profile.verification_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end">
            <x-button type="submit" variant="primary" size="md">
                {{ __('profile.save_changes') }}
            </x-button>
        </div>
    </form>

    {{-- Send Email Verification Form --}}
    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
            @csrf
        </form>
    @endif
</div>