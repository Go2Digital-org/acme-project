<x-layout title="{{ __('Security Settings') }}">
    <section class="py-12">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                {{-- Header --}}
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Security Settings') }}</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        {{ __('profile.manage_account_security_authentication_preferences') }}
                    </p>
                    
                    {{-- Breadcrumbs --}}
                    <nav class="flex mt-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('profile.show') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary dark:text-gray-400 dark:hover:text-white">
                                    <i class="fas fa-user mr-2"></i>
                                    {{ __('profile.profile') }}
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.security') }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                {{-- Security Status Overview --}}
                <div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                @if($profile['two_factor_enabled'])
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    @if($profile['two_factor_enabled'])
                                        {{ __('profile.account_secure') }}
                                    @else
                                        {{ __('profile.security_improvements_available') }}
                                    @endif
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($profile['two_factor_enabled'])
                                        {{ __('profile.your_account_protected_two_factor') }}
                                    @else
                                        {{ __('profile.enable_two_factor_enhanced_security') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(!$profile['two_factor_enabled'])
                            <x-button 
                                type="button" 
                                variant="primary" 
                                size="sm"
                                onclick="enableTwoFactor()"
                            >
                                {{ __('profile.enable_2fa') }}
                            </x-button>
                        @endif
                    </div>
                </div>

                {{-- Security Settings Forms --}}
                <div class="space-y-8">
                    {{-- Profile Photo Upload --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('profile.profile_photo') }}</h3>
                            <div class="flex items-center space-x-6">
                                <div class="shrink-0">
                                    <img id="profile-photo-preview" 
                                         class="h-20 w-20 object-cover rounded-full border-2 border-gray-200 dark:border-gray-700" 
                                         src="{{ $user->profile_photo_url }}" 
                                         alt="{{ $user->name }}">
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <label for="profile_photo" class="cursor-pointer">
                                            <div class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                                <i class="fas fa-upload mr-2"></i>
                                                {{ __('profile.upload_photo') }}
                                            </div>
                                            <input id="profile_photo" 
                                                   name="profile_photo" 
                                                   type="file" 
                                                   accept="image/*" 
                                                   class="sr-only" 
                                                   onchange="uploadProfilePhoto(this)">
                                        </label>
                                        @if($user->profile_photo_path)
                                        <button type="button" 
                                                onclick="deleteProfilePhoto()" 
                                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                            <i class="fas fa-trash mr-2"></i>
                                            {{ __('profile.remove') }}
                                        </button>
                                        @endif
                                    </div>
                                    <div id="upload-progress" class="mt-3 hidden">
                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div id="upload-progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('profile.uploading') }}</p>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                        {{ __('profile.jpg_png_gif_max_2mb') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Update Password --}}
                    @include('profile.partials.update-password')
                    
                    {{-- Two Factor Authentication --}}
                    @include('profile.partials.two-factor-authentication')
                </div>
            </div>
        </div>
    </section>

    {{-- Scripts for profile photo and 2FA functionality --}}
    <script>
    function uploadProfilePhoto(input) {
        const file = input.files[0];
        if (!file) return;

        // Validate file size (2MB max)
        if (file.size > 2048 * 1024) {
            showNotification('{{ __('profile.file_size_less_than_2mb') }}', 'error');
            input.value = '';
            return;
        }

        // Validate file type
        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            showNotification('{{ __('profile.select_valid_image_file') }}', 'error');
            input.value = '';
            return;
        }

        // Show preview immediately
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-photo-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Show progress bar
        const progressContainer = document.getElementById('upload-progress');
        const progressBar = document.getElementById('upload-progress-bar');
        progressContainer.classList.remove('hidden');
        
        // Create FormData
        const formData = new FormData();
        formData.append('profile_photo', file);
        formData.append('_token', '{{ csrf_token() }}');

        // Upload with progress tracking
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        });

        xhr.addEventListener('load', function() {
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
            
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                showNotification(response.message, 'success');
                
                // Update photo URL and show remove button if it wasn't there before
                document.getElementById('profile-photo-preview').src = response.photo_url;
                window.location.reload(); // Reload to show/hide remove button properly
            } else {
                const response = JSON.parse(xhr.responseText);
                showNotification(response.message || '{{ __('profile.upload_failed') }}', 'error');
                // Revert preview to original
                document.getElementById('profile-photo-preview').src = '{{ $user->profile_photo_url }}';
            }
            
            input.value = ''; // Clear input
        });

        xhr.addEventListener('error', function() {
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
            showNotification('{{ __('profile.upload_failed_try_again') }}', 'error');
            document.getElementById('profile-photo-preview').src = '{{ $user->profile_photo_url }}';
            input.value = '';
        });

        xhr.open('POST', '{{ route("profile.upload-photo") }}');
        xhr.send(formData);
    }

    function deleteProfilePhoto() {
        if (!confirm('{{ __('profile.are_you_sure_remove_profile_photo') }}')) {
            return;
        }

        fetch('{{ route("profile.remove-photo") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, 'success');
            document.getElementById('profile-photo-preview').src = data.photo_url;
            window.location.reload(); // Reload to hide remove button
        })
        .catch(error => {
            console.error('Error removing photo:', error);
            showNotification('{{ __('profile.failed_remove_photo_try_again') }}', 'error');
        });
    }

    function enableTwoFactor() {
        fetch('{{ route("profile.two-factor.enable") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            window.location.reload();
        })
        .catch(error => {
            console.error('Error enabling 2FA:', error);
        });
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg transition-all duration-300 transform translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }
    </script>
</x-layout>