{{-- Browser Sessions --}}
<div class="dark:bg-gray-800 rounded-lg shadow-one p-6 mb-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-dark dark:text-white">Browser Sessions</h3>
        <p class="mt-1 text-sm text-body-color">
            Manage and log out your active sessions on other browsers and devices.
        </p>
    </div>

    <div class="space-y-6">
        {{-- Current Session --}}
        <div class="flex items-start justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-1">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h4 class="font-medium text-green-800 dark:text-green-200">This Device</h4>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200">
                            Current Session
                        </span>
                    </div>
                    <div class="mt-1 space-y-1 text-sm text-green-700 dark:text-green-300">
                        <div class="flex items-center gap-4">
                            <span>{{ request()->userAgent() }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span>{{ request()->ip() }}</span>
                            <span>Active now</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Other Sessions (would be loaded via AJAX) --}}
        <div id="other-sessions" class="space-y-4">
            <div class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Loading other sessions...</p>
            </div>
        </div>

        {{-- Log Out Other Sessions --}}
        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Log Out Other Browser Sessions</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.
                    </p>
                </div>
                <x-button 
                    type="button" 
                    variant="outline" 
                    size="md"
                    onclick="showLogoutModal()"
                >
                    Log Out Other Sessions
                </x-button>
            </div>
        </div>
    </div>
</div>

{{-- Logout Modal --}}
<div id="logout-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="hideLogoutModal()">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Log Out Other Browser Sessions</h3>
                <button onclick="hideLogoutModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.
            </p>
            
            <form id="logout-form" onsubmit="logoutOtherSessions(event)">
                <div class="mb-4">
                    <label for="logout-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password
                    </label>
                    <input type="password" 
                           id="logout-password" 
                           name="password" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="flex justify-end gap-3">
                    <x-button type="button" variant="outline" size="md" onclick="hideLogoutModal()">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="danger" size="md">
                        Log Out Other Sessions
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadOtherSessions() {
    fetch('{{ route("profile.sessions") }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const container = document.getElementById('other-sessions');
        
        if (!container) {
            console.error('Container element not found');
            return;
        }
        
        if (data.sessions && data.sessions.length > 1) {
            const otherSessions = data.sessions.filter(session => !session.is_current);
            
            if (otherSessions.length > 0) {
                container.innerHTML = otherSessions.map(session => `
                    <div class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    ${session.device.browser} on ${session.device.platform}
                                </h4>
                                <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <div>IP: ${session.ip_address}</div>
                                    <div>Last active: ${new Date(session.last_activity).toLocaleString()}</div>
                                </div>
                            </div>
                        </div>
                        <button onclick="logoutSession('${session.id}')" 
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            Revoke
                        </button>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No other active sessions.</p>';
            }
        } else {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No other active sessions.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading sessions:', error);
        const container = document.getElementById('other-sessions');
        if (container) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Unable to load other sessions at this time.</p>';
        }
    });
}

function showLogoutModal() {
    document.getElementById('logout-modal').classList.remove('hidden');
}

function hideLogoutModal() {
    document.getElementById('logout-modal').classList.add('hidden');
    document.getElementById('logout-password').value = '';
}

function logoutOtherSessions(event) {
    event.preventDefault();
    const password = document.getElementById('logout-password').value;
    
    fetch('{{ route("profile.sessions.logout-others") }}', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ password })
    })
    .then(response => response.json())
    .then(data => {
        hideLogoutModal();
        loadOtherSessions();
    })
    .catch(error => {
        console.error('Error logging out sessions:', error);
    });
}

function logoutSession(sessionId) {
    fetch(`{{ route("profile.sessions.logout", "__PLACEHOLDER__") }}`.replace('__PLACEHOLDER__', sessionId), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        loadOtherSessions();
    })
    .catch(error => {
        console.error('Error logging out session:', error);
    });
}

// Load other sessions when page loads
document.addEventListener('DOMContentLoaded', loadOtherSessions);
</script>