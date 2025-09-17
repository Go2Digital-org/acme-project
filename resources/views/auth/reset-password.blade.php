<x-layout>
    
    <section class="py-16 md:py-24 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold text-black dark:text-white">
                        {{ __('Reset Password') }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Please enter your new password below.') }}
                    </p>
                </div>
                
                <x-validation-errors class="mb-4" />

                <form class="space-y-6" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    
                    <div class="space-y-4">
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
                                value="{{ old('email', $request->email) }}"
                            />
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Password') }}
                            </label>
                            <x-form-input 
                                type="password"
                                name="password"
                                id="password"
                                placeholder="{{ __('New password') }}"
                                autocomplete="new-password"
                                required
                                :error="$errors->has('password')"
                            />
                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Confirm Password') }}
                            </label>
                            <x-form-input 
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                placeholder="{{ __('Confirm new password') }}"
                                autocomplete="new-password"
                                required
                                :error="$errors->has('password_confirmation')"
                            />
                            @error('password_confirmation')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-button type="submit" class="w-full justify-center">
                            {{ __('Reset Password') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layout>