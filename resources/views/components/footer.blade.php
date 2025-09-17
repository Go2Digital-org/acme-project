<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 mt-auto" aria-labelledby="footer-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- Main Footer Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
            {{-- Company Info --}}
            <div class="lg:col-span-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-xl">
                        A
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('footer.company_name') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('footer.company_subtitle') }}</p>
                    </div>
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                    {{ __('footer.company_description') }}
                </p>

                {{-- Social Media Links --}}
                <div class="flex items-center gap-4">
                        @forelse($socialMediaLinks as $social)
                            <a
                                href="{{ $social->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors"
                                aria-label="{{ __('footer.follow_' . strtolower($social->platform)) }}"
                            >
                                <i class="fab fa-{{ strtolower($social->platform) }}"></i>
                            </a>
                        @empty
                            {{-- Fallback static social links --}}
                            <a
                                href="https://linkedin.com/company/acme-corp"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors"
                                aria-label="{{ __('footer.follow_linkedin') }}"
                            >
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <a
                                href="https://twitter.com/acme_corp"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors"
                                aria-label="{{ __('footer.follow_twitter') }}"
                            >
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a
                                href="https://facebook.com/acmecorp"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors"
                                aria-label="{{ __('footer.follow_facebook') }}"
                            >
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a
                                href="https://instagram.com/acme_corp"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors"
                                aria-label="{{ __('footer.follow_instagram') }}"
                            >
                                <i class="fab fa-instagram"></i>
                            </a>
                        @endforelse
                </div>
            </div>



            {{-- Categories --}}
            <div class="lg:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('footer.popular_causes') }}</h4>
                <ul class="space-y-3">
                    <li>
                        <a
                            href="{{ route('campaigns.index', ['category' => 'education']) }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors"
                        >
                            {{ __('footer.education') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('campaigns.index', ['category' => 'healthcare']) }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors"
                        >
                            {{ __('footer.healthcare') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('campaigns.index', ['category' => 'environment']) }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors"
                        >
                            {{ __('footer.environment') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('campaigns.index', ['category' => 'community']) }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors"
                        >
                            {{ __('footer.community_development') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Support & Resources --}}
            <div class="lg:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('footer.support') }}</h4>
                <ul class="space-y-3">
                    <li>
                        <a
                            href="{{ route('page', 'help-center') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/help-center') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.help_center') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'faq') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/faq') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.faq') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'contact') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/contact') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.contact_support') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'csr-guidelines') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/csr-guidelines') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.csr_guidelines') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Corporate Resources --}}
            <div class="lg:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('footer.corporate') }}</h4>
                <ul class="space-y-3">
                    <li>
                        <a
                            href="{{ route('page', 'about') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/about') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.about_company') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'sustainability') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/sustainability') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.sustainability_report') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'blog') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/blog') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.corporate_blog') }}
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('page', 'employee-resources') }}"
                            class="text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/employee-resources') ? 'text-primary' : '' }}"
                        >
                            {{ __('footer.employee_resources') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>



        {{-- Divider --}}
        <div class="flex items-center mb-8" aria-hidden="true">
            <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
            <div class="w-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full mx-4"></div>
            <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
        </div>

        {{-- Bottom Section --}}
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            {{-- Copyright --}}
            <div class="flex flex-col lg:flex-row items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ __('footer.copyright') }}</p>

                <div class="flex items-center gap-6">
                    <a
                        href="{{ route('page', 'privacy') }}"
                        class="hover:text-gray-900 dark:hover:text-white transition-colors {{ request()->is('page/privacy') ? 'text-primary' : '' }}"
                    >
                        {{ __('footer.privacy_policy') }}
                    </a>
                    <a
                        href="{{ route('page', 'terms') }}"
                        class="hover:text-gray-900 dark:hover:text-white transition-colors {{ request()->is('page/terms') ? 'text-primary' : '' }}"
                    >
                        {{ __('footer.terms_of_service') }}
                    </a>
                    <a
                        href="{{ route('page', 'cookies') }}"
                        class="hover:text-gray-900 dark:hover:text-white transition-colors {{ request()->is('page/cookies') ? 'text-primary' : '' }}"
                    >
                        {{ __('footer.cookie_policy') }}
                    </a>
                </div>
            </div>

            {{-- Additional Links --}}
            <div class="flex items-center gap-4 text-sm">
                <a
                    href="{{ route('page', 'security') }}"
                    class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/security') ? 'text-primary' : '' }}"
                >
                    <i class="fas fa-shield-alt text-xs"></i>
                    {{ __('footer.security') }}
                </a>
                <a
                    href="{{ route('page', 'accessibility') }}"
                    class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/accessibility') ? 'text-primary' : '' }}"
                >
                    <i class="fas fa-accessibility text-xs"></i>
                    {{ __('footer.accessibility') }}
                </a>
                <a
                    href="{{ route('page', 'compliance') }}"
                    class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary transition-colors {{ request()->is('page/compliance') ? 'text-primary' : '' }}"
                >
                    <i class="fas fa-certificate text-xs"></i>
                    {{ __('footer.compliance') }}
                </a>
            </div>
        </div>

        {{-- Hidden heading for screen readers --}}
        <h2 id="footer-heading" class="sr-only">{{ __('footer.footer_heading') }}</h2>
    </div>

    {{-- Back to top button --}}
    <button
        x-data="{ show: false }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @scroll.window="show = window.pageYOffset > 400"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 w-12 h-12 bg-primary text-white rounded-full shadow-lg hover:bg-primary-dark hover:shadow-xl transition-all duration-200 z-40 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
        aria-label="{{ __('footer.back_to_top') }}"
    >
        <i class="fas fa-chevron-up"></i>
    </button>
</footer>