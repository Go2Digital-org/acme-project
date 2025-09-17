{{-- Update Password --}}
<div class="dark:bg-gray-800 rounded-lg shadow-one p-6 mb-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-dark dark:text-white">{{ __('profile.update_password') }}</h3>
        <p class="mt-1 text-sm text-body-color">
            {{ __('profile.password_security_desc') }}
        </p>
    </div>

    <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Current Password --}}
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('profile.current_password') }}
            </label>
            <input type="password" 
                   id="current_password" 
                   name="current_password" 
                   required
                   autocomplete="current-password"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
            @error('current_password')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- New Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('profile.new_password') }}
            </label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   required
                   autocomplete="new-password"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
            @error('password')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('profile.confirm_password') }}
            </label>
            <input type="password" 
                   id="password_confirmation" 
                   name="password_confirmation" 
                   required
                   autocomplete="new-password"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end">
            <x-button type="submit" variant="primary" size="md">
                {{ __('profile.update_password') }}
            </x-button>
        </div>
    </form>
</div>