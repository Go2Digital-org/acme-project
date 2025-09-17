<x-layout>
    
    <section class="py-16 md:py-24 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold text-black dark:text-white">
                        {{ __('Confirm Password') }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </p>
                </div>
                
                <x-validation-errors class="mb-4" />

                <form class="space-y-6" method="POST" action="{{ route('password.confirm') }}">
                    @csrf
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                            {{ __('Password') }}
                        </label>
                        <x-form-input 
                            type="password"
                            name="password"
                            id="password"
                            placeholder="{{ __('Password') }}"
                            autocomplete="current-password"
                            required
                            :error="$errors->has('password')"
                        />
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-button type="submit" class="w-full justify-center">
                            {{ __('Confirm') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layout>