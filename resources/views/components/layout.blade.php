<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-pt-24"
    x-data="{
        darkMode: localStorage.getItem('darkMode') !== 'false',
        init() {
            // Watch for changes and save to localStorage
            this.$watch('darkMode', value => {
                localStorage.setItem('darkMode', value ? 'true' : 'false');
            });
        }
    }"
    x-bind:class="{ 'dark': darkMode === true }"
>
    <head>
        {{-- Apply theme before anything renders to prevent flash --}}
        <script>
            // Check localStorage and apply theme immediately
            if (localStorage.getItem('darkMode') !== 'false') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        
        <meta charset="utf-8" />
        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        />
        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        />
        <meta
            name="locale"
            content="{{ app()->getLocale() }}"
        />
        @auth
        <meta
            name="user-authenticated"
            content="true"
        />
        @endauth

        <title>{{ __('layout.site_title') }}{{ isset($title) ? ' | ' . $title : '' }}</title>
        
        {{-- Meta tags for corporate branding --}}
        <meta name="description" content="{{ __('layout.meta_description') }}">
        <meta name="keywords" content="{{ __('layout.meta_keywords') }}">
        <meta name="author" content="{{ __('layout.meta_author') }}">
        
        {{-- CSRF and Auth Meta Tags --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <meta name="user-authenticated" content="true">
        @endauth

        {{-- Favicon --}}
        <link
            rel="icon"
            href="{{ asset('favicon.ico') }}"
            type="image/x-icon"
        />
        <link
            rel="icon"
            href="{{ asset('favicon.svg') }}"
            type="image/svg+xml"
        />
        <link
            rel="icon"
            href="{{ asset('favicon.png') }}"
            type="image/png"
        />
        
        {{-- Preload important fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        {{-- Font Awesome --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        {{-- Styles --}}
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        @vite('resources/css/app.css')
    </head>
    <body
        x-cloak
        x-data="{
            showMobileMenu: false,
            showSearchModal: false,
            showNotifications: false,
            scrolled: window.scrollY > 50,
            width: window.innerWidth,
            get showPlatformSwitcherHeader() {
                return !this.scrolled && this.width >= 1024
            },
        }"
        x-resize="
            width = $width
            if (width >= 1024) {
                showMobileMenu = false
                showNotifications = false
            }
        "
        x-init="
            window.addEventListener('scroll', () => {
                scrolled = window.scrollY > 50
            })
        "
        class="flex flex-col min-h-screen font-sans overflow-x-clip antialiased selection:bg-primary selection:text-white bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100"
    >
    {{-- Skip link for accessibility --}}
    <a 
        href="#main-content" 
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 bg-primary text-white px-4 py-2 rounded-md transition-all"
    >
        {{ __('layout.skip_to_content') }}
    </a>
    
    <x-navigation />
    
    {{-- Flash Messages / Toast Notifications --}}
    @if(session('success') || session('error') || session('warning') || session('info'))
        <div class="fixed top-20 right-4 z-50 max-w-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            @if(session('success'))
                <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-3"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="ml-4 text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mr-3"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="ml-4 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
            
            @if(session('warning'))
                <div class="bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 dark:text-yellow-400 mr-3"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                    <button @click="show = false" class="ml-4 text-yellow-500 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
            
            @if(session('info'))
                <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 mr-3"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                    <button @click="show = false" class="ml-4 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Breadcrumbs --}}
    <x-breadcrumbs />
    
    {{-- Main content area --}}
    <main id="main-content" class="flex flex-col">
        {{ $slot }}
    </main>

    <x-footer />

        @vite('resources/js/app.js')
        
        {{-- Page-specific scripts --}}
        @stack('scripts')
        
        {{-- Corporate analytics and tracking scripts would go here --}}
        {{-- Google Analytics, employee engagement tracking, etc. --}}
    <x-impersonate::banner/>
    </body>
</html>