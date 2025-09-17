@php
    $isEdit = isset($campaign) && $campaign->exists;
    $title = $isEdit ? 'Edit Campaign' : 'Create Campaign';
    $subtitle = $isEdit ? 'Update your campaign details and settings.' : 'Share your cause with the ACME community and make a positive impact together.';
    $formAction = $isEdit ? route('campaigns.update', $campaign->id) : route('campaigns.store');
    $buttonText = $isEdit ? 'Update Campaign' : 'Create Campaign';
@endphp

<x-layout :title="$title">
    <section class="py-8 sm:py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                {{-- Header --}}
                <div class="text-center mb-8 sm:mb-12">
                    <h1 class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ $title }}</h1>
                    <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        {{ $subtitle }}
                    </p>
                    
                    @if($isEdit)
                        <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                            <x-button 
                                variant="outline" 
                                href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}"
                                icon="fas fa-eye"
                                size="sm"
                            >
                                View Campaign
                            </x-button>
                            <x-button 
                                variant="ghost" 
                                href="{{ route('campaigns.my-campaigns') }}"
                                icon="fas fa-arrow-left"
                                size="sm"
                            >
                                Back to My Campaigns
                            </x-button>
                        </div>
                    @endif
                </div>

                {{-- Form Card --}}
                <x-card>
                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="space-y-6 sm:space-y-8">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif
                        
                        {{-- Campaign Basics --}}
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Campaign Basics</h2>
                            <div class="grid grid-cols-1 gap-6">
                                <x-translation-input
                                    name="title"
                                    label="Campaign Title"
                                    placeholder="Enter a compelling title for your campaign"
                                    required
                                    :value="old('title', $isEdit ? $campaign->getTranslations('title') : [])"
                                    :error="$errors->first('title')"
                                    hint="Choose a clear, descriptive title that explains what your campaign is about"
                                />

                                <x-translation-textarea
                                    name="description"
                                    label="Campaign Description"
                                    placeholder="Tell people about your campaign. What is the cause? Why is it important? How will donations be used?"
                                    rows="6"
                                    required
                                    :value="old('description', $isEdit ? $campaign->getTranslations('description') : [])"
                                    :error="$errors->first('description')"
                                    hint="Provide detailed information about your campaign to help donors understand the cause"
                                />

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-select
                                        name="category_id"
                                        label="Category"
                                        :options="$categories"
                                        placeholder="Select a category"
                                        :value="old('category_id', $isEdit ? $campaign->category_id : '')"
                                        :error="$errors->first('category_id')"
                                        :required="true"
                                    />

                                    <x-input
                                        type="file"
                                        name="featured_image"
                                        label="Campaign Image"
                                        accept="image/*"
                                        :error="$errors->first('featured_image')"
                                        hint="Upload an image that represents your campaign"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- Financial Goals --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Financial Goals</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-input
                                    type="number"
                                    name="goal_amount"
                                    label="Funding Goal"
                                    placeholder="10000"
                                    min="1"
                                    step="1"
                                    required
                                    :value="old('goal_amount', $isEdit ? (int) $campaign->goal_amount : '')"
                                    :error="$errors->first('goal_amount')"
                                    hint="Set a realistic funding goal for your campaign"
                                    icon="fas fa-dollar-sign"
                                />

                                <x-input
                                    type="date"
                                    name="start_date"
                                    label="Start Date"
                                    min="{{ now()->format('Y-m-d') }}"
                                    required
                                    :value="old('start_date', $isEdit ? $campaign->start_date?->format('Y-m-d') : now()->format('Y-m-d'))"
                                    :error="$errors->first('start_date')"
                                    hint="Choose when your campaign should begin"
                                />

                                <x-input
                                    type="date"
                                    name="end_date"
                                    label="End Date"
                                    min="{{ now()->format('Y-m-d') }}"
                                    required
                                    :value="old('end_date', $isEdit ? $campaign->end_date?->format('Y-m-d') : now()->addMonths(3)->format('Y-m-d'))"
                                    :error="$errors->first('end_date')"
                                    hint="Choose when your campaign should end"
                                />
                            </div>
                        </div>

                        {{-- Additional Details --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Additional Details</h2>
                            <div class="space-y-6">
                                <x-textarea
                                    name="creator_note"
                                    label="Personal Message (Optional)"
                                    placeholder="Add a personal message about why this cause is important to you"
                                    rows="3"
                                    :error="$errors->first('creator_note')"
                                    hint="Share your personal connection to this cause"
                                >{{ old('creator_note', $isEdit ? $campaign->creator_note : '') }}</x-textarea>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-input
                                        name="organization_name"
                                        label="Beneficiary Organization (Optional)"
                                        placeholder="Enter the name of the organization that will receive funds"
                                        :error="$errors->first('organization_name')"
                                    />

                                    <x-input
                                        name="organization_website"
                                        label="Organization Website (Optional)"
                                        placeholder="https://example.org"
                                        type="url"
                                        :error="$errors->first('organization_website')"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- Campaign Settings --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Campaign Settings</h2>
                            <div class="space-y-6">
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        id="allow_anonymous_donations" 
                                        name="allow_anonymous_donations" 
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                        {{ old('allow_anonymous_donations', true) ? 'checked' : '' }}
                                    >
                                    <label for="allow_anonymous_donations" class="text-gray-700 dark:text-gray-300">
                                        Allow anonymous donations
                                    </label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        id="show_donation_comments" 
                                        name="show_donation_comments" 
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                        {{ old('show_donation_comments', true) ? 'checked' : '' }}
                                    >
                                    <label for="show_donation_comments" class="text-gray-700 dark:text-gray-300">
                                        Show donation comments publicly
                                    </label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        id="email_notifications" 
                                        name="email_notifications" 
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                        {{ old('email_notifications', true) ? 'checked' : '' }}
                                    >
                                    <label for="email_notifications" class="text-gray-700 dark:text-gray-300">
                                        Receive email notifications for new donations
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Terms and Guidelines --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-1"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">Campaign Guidelines</h3>
                                        <ul class="text-blue-800 dark:text-blue-200 space-y-1 text-sm">
                                            <li>• All campaigns must align with ACME Corp's values and ethics</li>
                                            <li>• Funds must be used for the stated purpose</li>
                                            <li>• Campaign creators are responsible for providing updates to donors</li>
                                            <li>• Personal fundraising campaigns are not permitted</li>
                                            <li>• All campaigns are subject to review and approval</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        id="agree_to_terms" 
                                        name="agree_to_terms" 
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                        required
                                    >
                                    <label for="agree_to_terms" class="text-gray-700 dark:text-gray-300">
                                        I agree to the <a href="/terms" class="text-primary hover:underline">Terms of Service</a> and <a href="/guidelines" class="text-primary hover:underline">Campaign Guidelines</a>
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                </div>
                                @error('agree_to_terms')
                                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <div class="flex flex-col sm:flex-row gap-4 justify-end">
                                <x-button type="button" variant="outline" href="{{ route('campaigns.index') }}">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </x-button>
                                @if($isEdit)
                                    <x-button type="submit" variant="primary">
                                        <i class="fas fa-save mr-2"></i>
                                        Update Campaign
                                    </x-button>
                                @else
                                    <x-button type="submit" name="action" value="draft">
                                        <i class="fas fa-save mr-2"></i>
                                        Save as Draft
                                    </x-button>
                                    <x-button type="submit" name="action" value="submit">
                                        <i class="fas fa-paper-plane mr-2"></i>
                                        Submit for Review
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </section>
</x-layout>