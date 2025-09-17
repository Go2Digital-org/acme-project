@props([
    'campaign',
    'sharingData' => null, // Data from SocialSharingService
    'show' => false,
    'id' => 'share-modal'
])

@php
    // Generate the campaign URL with the specific campaign ID
    $campaignUrl = isset($campaign) ? route('campaigns.show', $campaign->uuid ?? $campaign->id) : url()->current();
    
    // Prepare sharing text
    $shareText = isset($campaign) ? 
        "Help support: {$campaign->getTitle()}" : 
        "Check out this campaign on " . config('app.name');
    
    // Generate platform-specific URLs
    $platformUrls = [
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($campaignUrl),
        'twitter' => "https://twitter.com/intent/tweet?url=" . urlencode($campaignUrl) . "&text=" . urlencode($shareText),
        'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($campaignUrl),
        'whatsapp' => "https://wa.me/?text=" . urlencode($shareText . " " . $campaignUrl),
        'email' => "mailto:?subject=" . urlencode($shareText) . "&body=" . urlencode("Check out this campaign: " . $campaignUrl),
    ];
    
    // Generate QR code URL using a QR code API service
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($campaignUrl);
@endphp

{{-- Modal Overlay --}}
<div 
    x-data="shareModal('{{ $id }}')"
    x-show="isOpen"
    x-cloak
    @keydown.escape.window="close()"
    @share-modal-open.window="if ($event.detail && $event.detail.id === '{{ $id }}') { open() } else if (!$event.detail) { open() }"
    @share-modal-close.window="if ($event.detail && $event.detail.id === '{{ $id }}') { close() } else if (!$event.detail) { close() }"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- Background overlay --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 transition-opacity" 
         @click="close()"></div>

    {{-- Modal panel --}}
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div 
            class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all w-full sm:my-8 sm:max-w-lg max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.stop
        >
            {{-- Modal Header --}}
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary bg-opacity-10 dark:bg-primary-dark dark:bg-opacity-20 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-share-alt text-primary dark:text-primary-dark text-lg"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            {{ __('common.share_campaign') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $campaignTitle }}
                            </p>
                        </div>
                    </div>
                    <div class="absolute top-4 right-4">
                        <button @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="bg-white dark:bg-gray-800 px-4 pb-4 sm:px-6">
                {{-- Copy Link Section --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.copy_link') }}
                    </label>
                    <div class="flex rounded-md shadow-sm">
                        <input 
                            type="text" 
                            readonly 
                            value="{{ $campaignUrl }}" 
                            class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-primary focus:border-primary"
                            x-ref="linkInput"
                        >
                        <button 
                            @click="copyLink()"
                            class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-md bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-300 text-sm hover:bg-gray-100 dark:hover:bg-gray-500 transition-all"
                            :class="{ 
                                'text-green-600 dark:text-green-400 copy-success': copied,
                                'bg-green-50 dark:bg-green-900': copied 
                            }"
                        >
                            <i class="fas fa-copy mr-2" x-show="!copied"></i>
                            <i class="fas fa-check mr-2 text-green-600 dark:text-green-400" x-show="copied" x-cloak></i>
                            <span x-text="copied ? '{{ __('common.link_copied') }}' : '{{ __('common.copy') }}'">{{ __('common.copy') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Social Media Buttons --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('common.share_on_social') }}
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                        {{-- Facebook --}}
                        <button 
                            @click="openShare('{{ $platformUrls['facebook'] }}')"
                            class="share-button-hover inline-flex items-center justify-center px-2 py-3 sm:px-3 sm:py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white social-facebook focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all min-h-[44px] sm:min-h-[auto]"
                        >
                            <i class="fab fa-facebook-f mr-1 sm:mr-2"></i>
                            <span class="hidden sm:inline">Facebook</span>
                        </button>

                        {{-- Twitter --}}
                        <button 
                            @click="openShare('{{ $platformUrls['twitter'] }}')"
                            class="share-button-hover inline-flex items-center justify-center px-2 py-3 sm:px-3 sm:py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white social-twitter focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 transition-all min-h-[44px] sm:min-h-[auto]"
                        >
                            <i class="fab fa-twitter mr-1 sm:mr-2"></i>
                            <span class="hidden sm:inline">Twitter</span>
                        </button>

                        {{-- LinkedIn --}}
                        <button 
                            @click="openShare('{{ $platformUrls['linkedin'] }}')"
                            class="share-button-hover inline-flex items-center justify-center px-2 py-3 sm:px-3 sm:py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white social-linkedin focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-700 transition-all min-h-[44px] sm:min-h-[auto]"
                        >
                            <i class="fab fa-linkedin-in mr-1 sm:mr-2"></i>
                            <span class="hidden sm:inline">LinkedIn</span>
                        </button>

                        {{-- WhatsApp --}}
                        <button 
                            @click="openShare('{{ $platformUrls['whatsapp'] }}')"
                            class="share-button-hover inline-flex items-center justify-center px-2 py-3 sm:px-3 sm:py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white social-whatsapp focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all min-h-[44px] sm:min-h-[auto]"
                        >
                            <i class="fab fa-whatsapp mr-1 sm:mr-2"></i>
                            <span class="hidden sm:inline">WhatsApp</span>
                        </button>
                    </div>
                </div>

                {{-- Email Share --}}
                <div class="mb-6">
                    <button 
                        @click="openShare('{{ $platformUrls['email'] }}')"
                        class="w-full inline-flex items-center justify-center px-4 py-3 sm:py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors min-h-[44px] sm:min-h-[auto]"
                    >
                        <i class="fas fa-envelope mr-2"></i>
                        {{ __('common.share_via_email') }}
                    </button>
                </div>

                {{-- QR Code Section --}}
                <div class="text-center">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('common.qr_code') }}
                    </label>
                    <div class="qr-code-container inline-block">
                        <img 
                            src="{{ $qrCodeUrl }}" 
                            alt="{{ __('common.qr_code') }}" 
                            class="w-32 h-32 mx-auto"
                            loading="lazy"
                        >
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        {{ __('common.scan_qr_code') }}
                    </p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button 
                    @click="close()" 
                    type="button" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                >
                    {{ __('common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for Share Modal --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shareModal', (modalId) => ({
        isOpen: false,
        copied: false,
        modalId: modalId || 'share-modal',
        
        open() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            
            // Focus management for accessibility
            this.$nextTick(() => {
                const firstFocusable = this.$el.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            });
        },
        
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
            this.copied = false;
        },
        
        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.$refs.linkInput.value);
                this.copied = true;
                
                // Show success feedback
                this.showToast('{{ __("common.link_copied") }}', 'success');
                
                // Reset copied state after 3 seconds
                setTimeout(() => {
                    this.copied = false;
                }, 3000);
            } catch (err) {
                // Fallback for older browsers
                try {
                    this.$refs.linkInput.select();
                    this.$refs.linkInput.setSelectionRange(0, 99999); // For mobile devices
                    
                    const successful = document.execCommand('copy');
                    if (successful) {
                        this.copied = true;
                        this.showToast('{{ __("common.link_copied") }}', 'success');
                        
                        setTimeout(() => {
                            this.copied = false;
                        }, 3000);
                    } else {
                        this.showToast('{{ __("common.error") }}', 'error');
                    }
                } catch (fallbackErr) {
                    this.showToast('{{ __("common.error") }}', 'error');
                }
            }
        },
        
        openShare(url) {
            // Check if it's a mailto link
            if (url.startsWith('mailto:')) {
                window.location.href = url;
                return;
            }
            
            // Try native sharing first on mobile
            if (navigator.share && /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                navigator.share({
                    title: '{{ $campaignTitle }}',
                    text: '{{ $shareText }}',
                    url: '{{ $campaignUrl }}'
                }).catch(() => {
                    // Fallback to popup if native sharing fails
                    this.openPopup(url);
                });
            } else {
                // Open social media share in popup window
                this.openPopup(url);
            }
        },
        
        openPopup(url) {
            // Calculate popup position (center of screen)
            const width = 600;
            const height = 400;
            const left = (screen.width / 2) - (width / 2);
            const top = (screen.height / 2) - (height / 2);
            
            const popup = window.open(
                url,
                'share-popup',
                `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,directories=no,status=no`
            );
            
            // Focus the popup window
            if (popup) {
                popup.focus();
            }
        },
        
        showToast(message, type = 'info') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-[60] px-4 py-2 rounded-md text-white text-sm font-medium transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            toast.textContent = message;
            toast.style.transform = 'translateX(100%)';
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);
            
            // Animate out and remove
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 2000);
        }
    }));
});
</script>