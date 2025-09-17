<x-layout title="Optimizing Experience">
    {{-- Breadcrumb removed to avoid warnings --}}
    
    <section class="py-8 flex-1 flex items-center justify-center">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-2xl text-center">
            <!-- Header -->
            <div class="mb-8">
                <div class="mx-auto w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Optimizing Your Experience
                </h1>
                
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    We're preparing the platform for faster loading times.
                    <span class="loading-dots">...</span>
                </p>
            </div>
            
            <!-- Progress Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300" id="progress-text">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div 
                            id="progress-bar"
                            class="bg-gradient-to-r from-blue-500 to-purple-600 h-full rounded-full transition-all duration-500 ease-out"
                            style="width: {{ $progress }}%"
                        ></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <p class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2" id="current-page">
                        {{ $currentPage }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400" id="status-message">
                        @if($isComplete)
                            🎉 All done! Redirecting you back...
                        @else
                            This usually takes less than a minute
                        @endif
                    </p>
                </div>
            </div>
            
            <!-- Skip Button -->
            <div class="mb-8 text-center">
                <a 
                    href="{{ $returnUrl }}" 
                    class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 group"
                    onclick="skipOptimization(event)"
                >
                    Skip Optimization
                    <svg class="ml-2 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    You can skip this process, but pages may load slower initially
                </p>
            </div>
            
            <!-- Additional Info -->
            <div class="text-center text-gray-500 dark:text-gray-400">
                <p class="text-sm mb-4">
                    We're optimizing pages for better performance. You'll be automatically redirected when complete.
                </p>
                <div class="flex items-center justify-center space-x-6 text-xs">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        Cached Pages Load Instantly
                    </div>
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                        One-Time Process
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .loading-dots {
            animation: loadingDots 1.5s infinite;
        }
        
        @keyframes loadingDots {
            0%, 20% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>

    <script>
        let pollingInterval;
        let retryCount = 0;
        const maxRetries = 60; // 1 minute of retries
        const returnUrl = @json($returnUrl);
        const isComplete = @json($isComplete);

        function skipOptimization(event) {
            event.preventDefault();
            // Set cookie to skip cache warming for 1 hour
            document.cookie = "skip_cache_warming=1; path=/; max-age=3600";
            // Redirect to return URL if available, otherwise home page
            window.location.href = returnUrl || '/';
        }
        
        // If already complete, set cookie and redirect immediately
        if (isComplete) {
            document.cookie = "skip_cache_warming=1; path=/; max-age=3600";
            setTimeout(() => {
                window.location.href = returnUrl || '/';
            }, 1500);
        }

        function startPolling() {
            fetchStatus();
            pollingInterval = setInterval(fetchStatus, 2000); // Poll every 2 seconds
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        async function fetchStatus() {
            try {
                const response = await fetch('/api/cache-status', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                
                // Update progress
                const progress = Math.round(data.progress?.progress_percentage || 0);
                document.getElementById('progress-bar').style.width = `${progress}%`;
                document.getElementById('progress-text').textContent = `${progress}%`;
                
                // Update current page
                if (data.progress?.current_page) {
                    document.getElementById('current-page').textContent = data.progress.current_page;
                }
                
                // Check if complete
                if (data.progress?.is_complete || progress >= 100) {
                    document.getElementById('status-message').innerHTML = '🎉 All done! Redirecting you back...';
                    stopPolling();
                    
                    // Set skip cookie to prevent redirect loop
                    document.cookie = "skip_cache_warming=1; path=/; max-age=3600";
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = returnUrl || '/';
                    }, 1500);
                }
                
                retryCount = 0; // Reset retry count on success
                
            } catch (error) {
                console.error('Failed to fetch cache status:', error);
                retryCount++;
                
                if (retryCount >= maxRetries) {
                    const continueUrl = returnUrl || '/';
                    document.getElementById('status-message').innerHTML = '⚠️ Taking longer than expected. <a href="' + continueUrl + '" class="text-blue-500 hover:underline">Continue anyway</a>';
                    stopPolling();
                }
            }
        }

        // Start polling when page loads (unless already complete)
        document.addEventListener('DOMContentLoaded', () => {
            if (!isComplete) {
                startPolling();
            }
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', stopPolling);
    </script>
</x-layout>