@props([
    'user' => null,
    'size' => 'md',
    'clickable' => false,
    'realtime' => true,
    'showStatus' => false,
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-lg',
        '2xl' => 'w-20 h-20 text-xl',
        '3xl' => 'w-24 h-24 text-2xl',
        '4xl' => 'w-40 h-40 text-3xl',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $hasPhoto = $user && $user->profile_photo_path;
    $initials = $user ? substr($user->name ?? $user->first_name ?? 'U', 0, 2) : 'U';
    $avatarId = $user ? 'avatar-' . $user->id : 'avatar-guest';
    $userId = $user ? $user->id : 0;
    $photoUrl = $hasPhoto ? $user->profile_photo_url : '';
@endphp

<div
    @if($realtime && $user)
        x-data="userAvatarComponent"
        x-init="initAvatar({{ $userId }}, '{{ $photoUrl }}')"
    @endif
    class="relative inline-block {{ $class }}"
    @if($clickable && auth()->check() && auth()->id() === optional($user)->id)
        x-on:click="openAvatarUpload()"
    @endif
>
    <div
        class="{{ $sizeClass }} rounded-full overflow-hidden flex items-center justify-center font-semibold
               {{ $clickable && auth()->check() && auth()->id() === optional($user)->id ? 'cursor-pointer group' : '' }}
               {{ $hasPhoto ? '' : 'bg-primary text-white' }}"
        data-user-avatar="{{ optional($user)->id }}"
        id="{{ $avatarId }}"
    >
        @if($hasPhoto)
            <img
                src="{{ $user->profile_photo_url }}"
                alt="{{ $user->name }}"
                class="w-full h-full object-cover"
                x-ref="avatarImage"
            />
        @else
            <span x-ref="avatarInitials">{{ strtoupper($initials) }}</span>
        @endif

        @if($clickable && auth()->check() && auth()->id() === optional($user)->id)
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-full">
                <i class="fas fa-camera text-white {{ $size === 'xs' || $size === 'sm' ? 'text-xs' : 'text-sm' }}"></i>
            </div>
        @endif
    </div>

    @if($showStatus && $user)
        <div class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white dark:ring-gray-800"></div>
    @endif

    @if($clickable && auth()->check() && auth()->id() === optional($user)->id)
        <input
            type="file"
            x-ref="avatarFileInput"
            class="hidden"
            accept="image/*"
            @change="handleAvatarUpload($event)"
        />
    @endif
</div>

{{-- Define the Alpine component once, globally --}}
@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userAvatarComponent', () => ({
        userId: 0,
        currentPhotoUrl: '',

        initAvatar(userId, photoUrl) {
            this.userId = userId;
            this.currentPhotoUrl = photoUrl;

            // Listen for avatar updates via custom event
            window.addEventListener(`avatar-updated-${userId}`, (event) => {
                this.updateAvatar(event.detail.photoUrl);
            });

            // Listen for global avatar updates (for current user)
            @auth
            if (userId === {{ auth()->id() }}) {
                window.addEventListener('current-user-avatar-updated', (event) => {
                    this.updateAvatar(event.detail.photoUrl);
                });
            }
            @endauth
        },

        updateAvatar(newPhotoUrl) {
            // Force update ALL avatar instances for this user across the entire page
            const updateAllAvatars = () => {
                document.querySelectorAll(`[data-user-avatar="${this.userId}"]`).forEach(element => {
                    const img = element.querySelector('img');
                    const initials = element.querySelector('span');

                    if (newPhotoUrl) {
                        if (img) {
                            // Force cache bypass with timestamp
                            img.src = newPhotoUrl + (newPhotoUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
                        } else if (initials) {
                            // Replace initials with image
                            const newImg = document.createElement('img');
                            newImg.src = newPhotoUrl + (newPhotoUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
                            newImg.alt = 'User Avatar';
                            newImg.className = 'w-full h-full object-cover';
                            initials.replaceWith(newImg);
                            element.classList.remove('bg-primary', 'text-white');
                        }
                    } else {
                        // Remove image and show initials
                        if (img) {
                            const newSpan = document.createElement('span');
                            newSpan.textContent = this.$refs.avatarInitials ? this.$refs.avatarInitials.textContent : 'U';
                            img.replaceWith(newSpan);
                            element.classList.add('bg-primary', 'text-white');
                        }
                    }
                });
            };

            // Update immediately
            updateAllAvatars();

            // Update again after a short delay to catch any late-rendered components
            setTimeout(updateAllAvatars, 100);

            this.currentPhotoUrl = newPhotoUrl;
        },

        openAvatarUpload() {
            if (this.$refs.avatarFileInput) {
                this.$refs.avatarFileInput.click();
            }
        },

        handleAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            // Show loading state
            const avatarElements = document.querySelectorAll(`[data-user-avatar="${this.userId}"]`);
            avatarElements.forEach(el => el.style.opacity = '0.5');

            fetch('{{ route("profile.avatar") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.photo_url) {
                    // Update all avatars
                    this.updateAvatar(data.photo_url);

                    // Dispatch multiple events to ensure all components get notified
                    window.dispatchEvent(new CustomEvent('current-user-avatar-updated', {
                        detail: { photoUrl: data.photo_url, userId: this.userId }
                    }));

                    // Also dispatch a specific user event
                    window.dispatchEvent(new CustomEvent(`avatar-updated-${this.userId}`, {
                        detail: { photoUrl: data.photo_url }
                    }));

                    // Force update ALL avatar components on the page
                    document.querySelectorAll('[x-data*="userAvatarComponent"]').forEach(el => {
                        if (el.__x && el.__x.$data && el.__x.$data.userId === this.userId) {
                            el.__x.$data.updateAvatar(data.photo_url);
                        }
                    });

                    // Show success message
                    if (window.showToast) {
                        window.showToast('success', data.message || 'Avatar updated successfully');
                    } else {
                        // Fallback toast
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-20 right-4 z-50 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg shadow-lg';
                        toast.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-3"></i><span>' + (data.message || 'Avatar updated successfully') + '</span></div>';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    }
                }
            })
            .catch(error => {
                console.error('Avatar upload failed:', error);
                if (window.showToast) {
                    window.showToast('error', 'Failed to update avatar. Please try again.');
                }
            })
            .finally(() => {
                // Restore opacity
                avatarElements.forEach(el => el.style.opacity = '1');
                // Reset file input
                event.target.value = '';
            });
        }
    }));
});
</script>
@endonce