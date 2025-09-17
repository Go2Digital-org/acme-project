import preset from './vendor/filament/support/tailwind.config.preset'

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    darkMode: 'class',
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        'bg-green-400',
        'bg-green-500',
        'bg-green-600',
        'bg-yellow-500',
        'bg-orange-500',
        'bg-red-500',
        'hover:bg-green-500',
        'dark:bg-green-600',
        'animate-pulse',
        'bg-primary',
        'bg-primary-dark',
        'hover:bg-primary',
        'hover:bg-primary-dark',
        'text-primary',
        'text-primary-dark',
        'border-primary',
        'border-primary-dark'
    ],
    theme: {
        extend: {
            colors: {
                'primary': 'var(--color-primary)',
                'primary-dark': 'var(--color-primary-dark)',
                'mirage': 'var(--color-mirage)',
                'haiti': 'var(--color-haiti)',
                'cloud': 'var(--color-cloud)',
                'dark': '#1d2144',
                'body-color': '#959cb1',
                'black': '#090e34',
            },
            boxShadow: {
                'one': '0px 2px 3px rgba(7, 7, 77, 0.05)',
                'signUp': '0px 5px 10px rgba(4, 10, 34, 0.2)',
            },
        },
    },
    plugins: [],
}