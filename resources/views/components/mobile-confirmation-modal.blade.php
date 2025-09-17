{{-- Mobile-Optimized Confirmation Modal Component --}}
@props([
    'id' => 'confirmation-modal',
    'title' => 'Confirm Action',
    'danger' => false,
    'size' => 'default' // sm, default, lg
])

@php
    $sizeClasses = match($size) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-lg',
        default => 'max-w-md'
    };
    
    $headerClasses = $danger 
        ? 'text-red-600 dark:text-red-400' 
        : 'text-gray-900 dark:text-white';
@endphp

<div
    x-data="{ 
        open: false,
        modalData: null
    }"
    x-show="open"
    x-cloak
    @modal-open.window="if ($event.detail.id === '{{ $id }}') { 
        open = true; 
        modalData = $event.detail.data || null;
        document.body.style.overflow = 'hidden';
    }"
    @modal-close.window="if ($event.detail.id === '{{ $id }}') { 
        open = false; 
        modalData = null;
        document.body.style.overflow = 'auto';
    }"
    @keydown.escape.window="if (open) { 
        $dispatch('modal-close', { id: '{{ $id }}' }); 
    }"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="`${$id('modal-title')}`"
>
    {{-- Backdrop --}}
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @click="$dispatch('modal-close', { id: '{{ $id }}' })"
    ></div>

    {{-- Modal Container --}}
    <div class="flex min-h-full items-end sm:items-center justify-center p-2 sm:p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $sizeClasses }} bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl"
            @click.stop
        >
            {{-- Mobile Handle (iOS style) --}}
            <div class="sm:hidden flex justify-center pt-2 pb-1">
                <div class="w-8 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
            </div>

            {{-- Header --}}
            <div class="flex items-center justify-between p-4 sm:p-6 {{ $danger ? 'border-b border-red-200 dark:border-red-800' : 'border-b border-gray-200 dark:border-gray-700' }}">
                <div class="flex items-center gap-3">
                    @if($danger)
                        <div class="flex-shrink-0 w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-sm"></i>
                        </div>
                    @endif
                    <h3 class="text-lg sm:text-xl font-semibold {{ $headerClasses }}" :id="$id('modal-title')">
                        {{ $title }}
                    </h3>
                </div>
                
                {{-- Close Button --}}
                <button
                    type="button"
                    @click="$dispatch('modal-close', { id: '{{ $id }}' })"
                    class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Close modal"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-4 sm:p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

{{-- Global Styles for Modal --}}
<style>
    /* Prevent body scroll when modal is open on mobile */
    .modal-open {
        overflow: hidden;
        position: fixed;
        width: 100%;
    }

    /* Smooth backdrop blur */
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
    }

    /* Handle safe area for mobile devices */
    @supports (padding: env(safe-area-inset-bottom)) {
        .modal-mobile-padding {
            padding-bottom: env(safe-area-inset-bottom);
        }
    }

    /* Focus trap styling */
    .modal-focus-trap:focus {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    /* Animation improvements for mobile */
    @media (max-width: 640px) {
        .modal-container {
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modal-container.open {
            transform: translateY(0);
        }
    }
</style>

{{-- JavaScript for enhanced mobile behavior --}}
<script>
    // Prevent background scrolling on mobile when modal is open
    document.addEventListener('alpine:init', () => {
        let originalBodyOverflow = '';
        
        window.addEventListener('modal-open', (event) => {
            if (event.detail.id === '{{ $id }}') {
                originalBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                document.body.classList.add('modal-open');
                
                // Prevent scroll on touch devices
                document.addEventListener('touchmove', preventScroll, { passive: false });
            }
        });
        
        window.addEventListener('modal-close', (event) => {
            if (event.detail.id === '{{ $id }}') {
                document.body.style.overflow = originalBodyOverflow;
                document.body.classList.remove('modal-open');
                
                // Remove scroll prevention
                document.removeEventListener('touchmove', preventScroll);
            }
        });
        
        function preventScroll(e) {
            // Allow scrolling within the modal content
            if (!e.target.closest('[x-show="open"]')) {
                e.preventDefault();
            }
        }
    });
</script>