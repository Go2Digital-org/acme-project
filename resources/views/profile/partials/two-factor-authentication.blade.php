{{-- Two Factor Authentication --}}
<div class="dark:bg-gray-800 rounded-lg shadow-one p-6 mb-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-dark dark:text-white">Two Factor Authentication</h3>
        <p class="mt-1 text-sm text-body-color">
            Add additional security to your account using two factor authentication.
        </p>
    </div>

    @if($profile['two_factor_enabled'])
        {{-- Two Factor is Enabled --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        Two factor authentication is enabled.
                    </p>
                    <p class="text-sm text-green-700 dark:text-green-300">
                        Your account is protected with two factor authentication.
                    </p>
                </div>
            </div>

            {{-- Recovery Codes --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Recovery Codes</span>
                    <x-button 
                        type="button" 
                        variant="outline" 
                        size="sm"
                        onclick="regenerateRecoveryCodes()"
                    >
                        Regenerate
                    </x-button>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                        Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.
                    </p>
                    <div id="recovery-codes" class="space-y-1">
                        {{-- Recovery codes would be loaded here via AJAX --}}
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Click "Show Recovery Codes" to view your codes.
                        </div>
                    </div>
                    <x-button 
                        type="button" 
                        variant="ghost" 
                        size="sm"
                        onclick="showRecoveryCodes()"
                        class="mt-2"
                    >
                        Show Recovery Codes
                    </x-button>
                </div>
            </div>

            {{-- Disable 2FA --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Disable Two Factor Authentication</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Remove two factor authentication from your account.</p>
                </div>
                <x-button 
                    type="button" 
                    variant="danger" 
                    size="sm"
                    onclick="disableTwoFactor()"
                >
                    Disable
                </x-button>
            </div>
        </div>
    @else
        {{-- Two Factor is Disabled --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Two factor authentication is not enabled.
                    </p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        Enable two factor authentication to add an extra layer of security to your account.
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.
                </p>
                
                <x-button 
                    type="button" 
                    variant="primary" 
                    size="md"
                    onclick="enableTwoFactor()"
                >
                    Enable Two Factor Authentication
                </x-button>
            </div>
        </div>
    @endif
</div>

<script>
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

function disableTwoFactor() {
    if (confirm('Are you sure you want to disable two factor authentication?')) {
        fetch('{{ route("profile.two-factor.disable") }}', {
            method: 'DELETE',
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
            console.error('Error disabling 2FA:', error);
        });
    }
}

function showRecoveryCodes() {
    fetch('{{ route("profile.two-factor.recovery-codes") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('recovery-codes');
        container.innerHTML = data.codes.map(code => 
            `<div class="font-mono text-sm text-gray-900 dark:text-white">${code}</div>`
        ).join('');
    })
    .catch(error => {
        console.error('Error showing recovery codes:', error);
    });
}

function regenerateRecoveryCodes() {
    if (confirm('Are you sure you want to regenerate your recovery codes? This will invalidate your current codes.')) {
        fetch('{{ route("profile.two-factor.recovery-codes") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showRecoveryCodes();
        })
        .catch(error => {
            console.error('Error regenerating recovery codes:', error);
        });
    }
}
</script>