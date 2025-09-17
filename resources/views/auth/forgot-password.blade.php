<x-layout>
    
    <section class="py-16 md:py-24 bg-gray-50 dark:bg-[#050714]">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold text-black dark:text-white">
                        {{ __('Forgot your password?') }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </p>
                </div>
                
                <x-validation-errors class="mb-4" />

                @session('status')
                    <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        {{ $value }}
                    </div>
                @endsession

                <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                            {{ __('Email') }}
                        </label>
                        <x-form-input 
                            type="email"
                            name="email"
                            id="email"
                            placeholder="{{ __('Email address') }}"
                            autocomplete="email"
                            required
                            :error="$errors->has('email')"
                            value="{{ old('email') }}"
                        />
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-button type="submit" class="w-full justify-center">
                            {{ __('Email Password Reset Link') }}
                        </x-button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                        {{ __('Back to sign in') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>