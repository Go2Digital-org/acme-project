<x-layout>
    
    <section class="py-16 md:py-24 bg-gray-50 dark:bg-[#050714]">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold text-black dark:text-white">
                        {{ __('Two Factor Authentication') }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-show="! recovery">
                        {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-show="recovery" style="display: none;">
                        {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
                    </p>
                </div>
                
                <x-validation-errors class="mb-4" />

                <div x-data="{ recovery: false }">
                    <form class="space-y-6" method="POST" action="{{ route('two-factor.login') }}">
                        @csrf
                        
                        <div x-show="! recovery">
                            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Code') }}
                            </label>
                            <x-form-input 
                                type="text"
                                name="code"
                                id="code"
                                placeholder="{{ __('Authentication code') }}"
                                autocomplete="one-time-code"
                                inputmode="numeric"
                                :error="$errors->has('code')"
                            />
                            @error('code')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="recovery" style="display: none;">
                            <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                {{ __('Recovery Code') }}
                            </label>
                            <x-form-input 
                                type="text"
                                name="recovery_code"
                                id="recovery_code"
                                placeholder="{{ __('Recovery code') }}"
                                autocomplete="one-time-code"
                                :error="$errors->has('recovery_code')"
                            />
                            @error('recovery_code')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="button" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 underline cursor-pointer"
                                    x-show="! recovery"
                                    x-on:click="recovery = true; $refs.recovery_code?.focus()">
                                {{ __('Use a recovery code') }}
                            </button>

                            <button type="button" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 underline cursor-pointer"
                                    x-show="recovery"
                                    x-on:click="recovery = false; $refs.code?.focus()"
                                    style="display: none;">
                                {{ __('Use an authentication code') }}
                            </button>
                        </div>

                        <div>
                            <x-button type="submit" class="w-full justify-center">
                                {{ __('Log in') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout>