<x-layout>
    <section class="flex-1 flex flex-col items-center justify-center text-center px-6 min-h-screen">
        <h1 class="text-6xl sm:text-7xl md:text-8xl font-extrabold text-gray-900 dark:text-white tracking-tight">
            @yield('code')
        </h1>
        <p class="mt-4 text-lg sm:text-xl md:text-2xl text-gray-600 dark:text-gray-400 uppercase tracking-widest">
            @yield('message')
        </p>
        <a href="{{ url('/') }}"
           class="mt-8 inline-block rounded-lg bg-primary px-6 py-3 text-white font-semibold transition hover:bg-primary-dark">
            {{ __('errors.go_back_home') }}
        </a>
    </section>
</x-layout>