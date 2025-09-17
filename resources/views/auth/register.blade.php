<x-layout>
    
    <section class="py-16 md:py-24 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold text-black dark:text-white">
                        {{ __('Create your account') }}
                    </h2>
                    @if (Route::has('login'))
                        <p class="mt-2 text-sm text-body-color">
                            {{ __('Already have an account?') }}
                            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('Sign in') }}
                            </a>
                        </p>
                    @endif
                </div>
                
                <x-validation-errors class="mb-4" />

                <form class="space-y-6" method="POST" action="{{ route('register') }}">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Name') }}
                            </label>
                            <x-form-input 
                                type="text"
                                name="name"
                                id="name"
                                placeholder="{{ __('Full name') }}"
                                autocomplete="name"
                                required
                                :error="$errors->has('name')"
                                value="{{ old('name') }}"
                            />
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
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
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Password') }}
                            </label>
                            <x-form-input 
                                type="password"
                                name="password"
                                id="password"
                                placeholder="{{ __('Password') }}"
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
                                placeholder="{{ __('Confirm password') }}"
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
                            {{ __('Create account') }}
                        </x-button>
                    </div>
                </form>

                {{-- Social Login --}}
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                                {{ __('Or continue with') }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3">
                        <a href="{{ route('auth.google') }}"
                           class="w-full inline-flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="ml-2">{{ __('Sign up with Google') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>