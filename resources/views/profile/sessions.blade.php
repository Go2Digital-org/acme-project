<x-layout title="{{ __('Active Sessions') }}">
    <section class="py-12">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                {{-- Header --}}
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Active Sessions</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Manage your active sessions and account security.
                    </p>
                    
                    {{-- Breadcrumbs --}}
                    <nav class="flex mt-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('profile.show') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary dark:text-gray-400 dark:hover:text-white">
                                    <i class="fas fa-user mr-2"></i>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">Sessions</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                {{-- Sessions Management --}}
                <div class="space-y-8">
                    {{-- Browser Sessions --}}
                    @include('profile.partials.logout-other-browser-sessions')
                    
                    {{-- Account Deletion --}}
                    @include('profile.partials.delete-user')
                </div>
            </div>
        </div>
    </section>
</x-layout>