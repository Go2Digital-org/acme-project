<x-layout title="Notifications">
    <section class="py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ __('notifications.notifications') }}
                    </h1>
                    
                    @if($notifications->count() > 0)
                        <form action="{{ route('notifications.clear') }}" method="POST">
                            @csrf
                            <x-button type="submit" variant="ghost" size="sm" icon="fas fa-check-double">
                                {{ __('notifications.mark_all_read') }}
                            </x-button>
                        </form>
                    @endif
                </div>

                {{-- Notifications List --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm">
                    @php
                        $notificationItems = method_exists($notifications, 'items') ? $notifications->items() : $notifications;
                    @endphp
                    @forelse($notificationItems as $notification)
                        <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="p-6">
                                <div class="flex items-start gap-4">
                                    {{-- Icon --}}
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 {{ $notification['icon_color'] }} rounded-full flex items-center justify-center">
                                            <i class="fas fa-bell text-white"></i>
                                        </div>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900 dark:text-white {{ !$notification['is_read'] ? 'font-bold' : '' }}">
                                                    {{ $notification['title'] }}
                                                </h3>
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $notification['message'] }}
                                                </p>
                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                                    {{ $notification['time_ago'] }}
                                                </p>
                                            </div>
                                            
                                            {{-- Actions --}}
                                            <div class="flex items-center gap-2 ml-4">
                                                @if($notification['url'])
                                                    <a href="{{ $notification['url'] }}" 
                                                       class="text-primary hover:text-primary-dark text-sm font-medium">
                                                        {{ __('common.view') }}
                                                    </a>
                                                @endif
                                                
                                                @if(!$notification['is_read'])
                                                    <form action="{{ route('notifications.read', $notification['id']) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                                            <i class="fas fa-check text-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                {{ __('notifications.no_notifications') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('notifications.no_notifications_description') }}
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($notifications->hasPages())
                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layout>