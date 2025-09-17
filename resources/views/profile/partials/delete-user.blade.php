{{-- Delete Account --}}
<div class="dark:bg-gray-800 rounded-lg shadow-one p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-red-600 dark:text-red-400">{{ __('profile.delete_account') }}</h3>
        <p class="mt-1 text-sm text-body-color">
            {{ __('profile.delete_account_desc') }}
        </p>
    </div>

    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('profile.permanently_delete') }}</p>
            <p class="text-sm text-red-700 dark:text-red-300">{{ __('profile.action_cannot_be_undone') }}</p>
        </div>
        <x-button 
            type="button" 
            variant="danger" 
            size="md"
            onclick="showDeleteModal()"
        >
            {{ __('profile.delete_account') }}
        </x-button>
    </div>
</div>

{{-- Delete Account Modal --}}
<div id="delete-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="hideDeleteModal()">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-red-600 dark:text-red-400">{{ __('profile.delete_account') }}</h3>
                <button onclick="hideDeleteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('profile.delete_warning_title') }}</p>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                            {{ __('profile.delete_warning_desc') }}
                        </p>
                    </div>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('profile.delete_confirmation_desc') }}
            </p>
            
            <form id="delete-form" onsubmit="deleteAccount(event)">
                <div class="mb-4">
                    <label for="delete-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.password') }}
                    </label>
                    <input type="password" 
                           id="delete-password" 
                           name="password" 
                           required
                           placeholder="{{ __('profile.confirm_password_to_delete') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-start gap-2">
                        <input type="checkbox" 
                               id="confirm-delete" 
                               required
                               class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('profile.delete_confirmation_checkbox') }}
                        </span>
                    </label>
                </div>
                
                <div class="flex justify-end gap-3">
                    <x-button type="button" variant="outline" size="md" onclick="hideDeleteModal()">
                        {{ __('common.cancel') }}
                    </x-button>
                    <x-button type="submit" variant="danger" size="md">
                        {{ __('profile.delete_account_permanently') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDeleteModal() {
    document.getElementById('delete-modal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    document.getElementById('delete-password').value = '';
    document.getElementById('confirm-delete').checked = false;
}

function deleteAccount(event) {
    event.preventDefault();
    const password = document.getElementById('delete-password').value;
    const confirmed = document.getElementById('confirm-delete').checked;
    
    if (!confirmed) {
        alert('{{ __("profile.delete_warning_title") }}');
        return;
    }
    
    if (confirm('{{ __("profile.final_delete_confirmation") }}')) {
        fetch('{{ route("profile.delete") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ password })
        })
        .then(response => {
            if (response.ok) {
                window.location.href = '{{ route("welcome") }}';
            } else {
                return response.json().then(data => {
                    throw new Error(data.message || 'Error deleting account');
                });
            }
        })
        .catch(error => {
            console.error('Error deleting account:', error);
            alert('Error deleting account: ' + error.message);
        });
    }
}
</script>