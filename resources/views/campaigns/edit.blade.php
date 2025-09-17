<x-layout :title="'Edit Campaign: ' . $campaign->getTitle()">
    <div class="px-2 sm:px-4 md:px-6 lg:px-8 py-4 sm:py-6" x-data="campaignEditor()">
        {{-- Mobile-First Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="flex items-center gap-3">
                {{-- Back Button --}}
                <button
                    type="button"
                    onclick="history.back()"
                    class="flex-shrink-0 p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors sm:hidden"
                    aria-label="Go back"
                >
                    <i class="fas fa-arrow-left text-sm"></i>
                </button>
                
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                        Edit Campaign
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Update your campaign details and settings
                    </p>
                </div>
            </div>

            {{-- Desktop Back Link --}}
            <div class="hidden sm:flex items-center gap-3">
                <x-button 
                    variant="outline" 
                    size="sm"
                    href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Campaign
                </x-button>
                
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <span class="px-2 py-1 bg-{{ $campaign->status === 'active' ? 'green' : ($campaign->status === 'completed' ? 'blue' : 'gray') }}-100 text-{{ $campaign->status === 'active' ? 'green' : ($campaign->status === 'completed' ? 'blue' : 'gray') }}-700 rounded-full text-xs font-medium">
                        {{ $campaign->status->getLabel() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Container --}}
        <form 
            x-ref="campaignForm"
            @submit.prevent="submitForm()"
            class="space-y-4 sm:space-y-6"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            {{-- Basic Information Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-info-circle text-primary text-sm"></i>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h2>
                </div>

                <div class="space-y-4">
                    {{-- Campaign Title --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Campaign Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            x-model="form.title"
                            @input="updateSlug()"
                            maxlength="100"
                            required
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                            placeholder="Enter a compelling campaign title..."
                            value="{{ old('title', $campaign->getTitle()) }}"
                        >
                        <div class="flex justify-between items-center mt-1">
                            <div class="text-xs text-gray-500" x-show="errors.title" x-text="errors.title"></div>
                            <div class="text-xs text-gray-500" x-text="`${form.title.length}/100`"></div>
                        </div>
                    </div>

                    {{-- Campaign Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            x-model="form.description"
                            rows="4"
                            maxlength="1000"
                            required
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                            placeholder="Describe your campaign's mission and goals..."
                        >{{ old('description', $campaign->getDescription()) }}</textarea>
                        <div class="flex justify-between items-center mt-1">
                            <div class="text-xs text-gray-500" x-show="errors.description" x-text="errors.description"></div>
                            <div class="text-xs text-gray-500" x-text="`${form.description.length}/1000`"></div>
                        </div>
                    </div>

                    {{-- Category Selection --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category
                        </label>
                        <select
                            id="category"
                            name="category_id"
                            x-model="form.category_id"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            <option value="">Select a category</option>
                            <option value="education" {{ old('category_id', $campaign->category_id) == 'education' ? 'selected' : '' }}>Education</option>
                            <option value="healthcare" {{ old('category_id', $campaign->category_id) == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                            <option value="environment" {{ old('category_id', $campaign->category_id) == 'environment' ? 'selected' : '' }}>Environment</option>
                            <option value="poverty" {{ old('category_id', $campaign->category_id) == 'poverty' ? 'selected' : '' }}>Poverty Alleviation</option>
                            <option value="disaster" {{ old('category_id', $campaign->category_id) == 'disaster' ? 'selected' : '' }}>Disaster Relief</option>
                            <option value="community" {{ old('category_id', $campaign->category_id) == 'community' ? 'selected' : '' }}>Community Development</option>
                            <option value="arts" {{ old('category_id', $campaign->category_id) == 'arts' ? 'selected' : '' }}>Arts & Culture</option>
                            <option value="human-rights" {{ old('category_id', $campaign->category_id) == 'human-rights' ? 'selected' : '' }}>Human Rights</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Goal and Timeline Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-bullseye text-primary text-sm"></i>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Goal & Timeline</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Goal Amount --}}
                    <div>
                        <label for="goal_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Funding Goal <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                            <input
                                type="number"
                                id="goal_amount"
                                name="goal_amount"
                                x-model="form.goal_amount"
                                @input="updateProgress()"
                                min="100"
                                max="1000000"
                                step="50"
                                required
                                class="w-full pl-8 pr-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="10000"
                                value="{{ old('goal_amount', $campaign->goal_amount) }}"
                            >
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Minimum: $100, Maximum: $1,000,000</div>
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            x-model="form.end_date"
                            @change="validateEndDate()"
                            min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                            max="{{ date('Y-m-d', strtotime('+2 years')) }}"
                            required
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                            value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}"
                        >
                        <div class="text-xs text-gray-500 mt-1" x-text="getDaysFromNow(form.end_date)"></div>
                    </div>
                </div>

                {{-- Current Progress Display --}}
                @if($campaign->current_amount > 0)
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Current Progress</span>
                        <span class="text-sm text-blue-600 dark:text-blue-400">${{ number_format($campaign->current_amount) }} raised</span>
                    </div>
                    <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                        <div 
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                            style="width: {{ min(($campaign->current_amount / max($campaign->goal_amount, 1)) * 100, 100) }}%"
                        ></div>
                    </div>
                    <div class="flex justify-between text-xs text-blue-600 dark:text-blue-400 mt-1">
                        <span>{{ $campaign->donations_count ?? 0 }} donations</span>
                        <span>{{ number_format(($campaign->current_amount / max($campaign->goal_amount, 1)) * 100, 1) }}% complete</span>
                    </div>
                </div>
                @endif
            </div>

            {{-- Media Upload Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-image text-primary text-sm"></i>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Campaign Image</h2>
                </div>

                <div class="space-y-4">
                    {{-- Current Image Display --}}
                    @if($campaign->featured_image)
                    <div class="relative group">
                        <img 
                            src="{{ $campaign->featured_image }}" 
                            alt="Current campaign image"
                            class="w-full h-48 sm:h-64 object-cover rounded-lg"
                        >
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                            <button
                                type="button"
                                @click="removeCurrentImage()"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700"
                            >
                                <i class="fas fa-trash mr-2"></i>
                                Remove Image
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- File Upload --}}
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 sm:p-6 text-center">
                        <input
                            type="file"
                            id="featured_image"
                            name="featured_image"
                            accept="image/*"
                            @change="previewImage($event)"
                            class="hidden"
                        >
                        
                        <div x-show="!imagePreview">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                            <div>
                                <button
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-primary hover:text-primary-dark font-medium"
                                >
                                    Click to upload
                                </button>
                                <span class="text-gray-500"> or drag and drop</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF up to 5MB</p>
                        </div>

                        <div x-show="imagePreview" class="space-y-3">
                            <img :src="imagePreview" alt="Image preview" class="max-h-48 mx-auto rounded-lg">
                            <button
                                type="button"
                                @click="clearImagePreview()"
                                class="text-sm text-red-600 hover:text-red-700"
                            >
                                Remove new image
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Campaign Settings Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-cog text-primary text-sm"></i>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Settings</h2>
                </div>

                <div class="space-y-4">
                    {{-- Campaign Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Campaign Status
                        </label>
                        <select
                            id="status"
                            name="status"
                            x-model="form.status"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            <option value="draft" {{ old('status', $campaign->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $campaign->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ old('status', $campaign->status) == 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="completed" {{ old('status', $campaign->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <div class="text-xs text-gray-500 mt-1">
                            <template x-if="form.status === 'active'">
                                <span class="text-green-600">Campaign will be visible to all employees</span>
                            </template>
                            <template x-if="form.status === 'draft'">
                                <span class="text-gray-500">Campaign will not be visible until activated</span>
                            </template>
                            <template x-if="form.status === 'paused'">
                                <span class="text-yellow-600">Campaign is paused - no new donations accepted</span>
                            </template>
                        </div>
                    </div>

                    {{-- Allow Anonymous Donations --}}
                    <div class="flex items-start space-x-3">
                        <input
                            type="checkbox"
                            id="allow_anonymous"
                            name="allow_anonymous"
                            x-model="form.allow_anonymous"
                            class="mt-1 rounded border-gray-300 text-primary focus:ring-primary"
                            {{ old('allow_anonymous', $campaign->allow_anonymous) ? 'checked' : '' }}
                        >
                        <div>
                            <label for="allow_anonymous" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Allow Anonymous Donations
                            </label>
                            <p class="text-xs text-gray-500 mt-1">
                                Donors can choose to keep their identity private
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile-Sticky Action Buttons --}}
            <div class="sticky bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 sm:relative sm:bg-transparent sm:border-t-0 sm:p-0">
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 sm:justify-end">
                    {{-- Save as Draft (mobile hidden if already active) --}}
                    <template x-if="form.status !== 'active'">
                        <button
                            type="button"
                            @click="saveDraft()"
                            :disabled="saving"
                            class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50"
                        >
                            <i class="fas fa-save mr-2"></i>
                            <span x-show="!saving">Save as Draft</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </template>

                    {{-- Preview Button --}}
                    <button
                        type="button"
                        @click="previewCampaign()"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <i class="fas fa-eye mr-2"></i>
                        Preview
                    </button>

                    {{-- Update Campaign --}}
                    <button
                        type="submit"
                        :disabled="saving"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50"
                    >
                        <i class="fas fa-check mr-2"></i>
                        <span x-show="!saving">Update Campaign</span>
                        <span x-show="saving">Updating...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Alpine.js Script --}}
    <script>
        function campaignEditor() {
            return {
                // Form data
                form: {
                    title: @json(old('title', $campaign->getTitle())),
                    description: @json(old('description', $campaign->getDescription())),
                    goal_amount: @json(old('goal_amount', $campaign->goal_amount)),
                    end_date: @json(old('end_date', $campaign->end_date?->format('Y-m-d'))),
                    category_id: @json(old('category_id', $campaign->category_id)),
                    status: @json(old('status', $campaign->status)),
                    allow_anonymous: @json((bool) old('allow_anonymous', $campaign->allow_anonymous))
                },
                
                // State
                saving: false,
                errors: {},
                imagePreview: null,
                
                // Initialize
                init() {
                    // Set up auto-save (every 30 seconds)
                    setInterval(() => {
                        if (this.hasUnsavedChanges()) {
                            this.autoSave();
                        }
                    }, 30000);
                    
                    // Handle beforeunload to warn about unsaved changes
                    window.addEventListener('beforeunload', (e) => {
                        if (this.hasUnsavedChanges()) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    });
                },

                // Form submission
                async submitForm() {
                    if (this.saving) return;
                    
                    this.saving = true;
                    this.errors = {};
                    
                    try {
                        const formData = new FormData(this.$refs.campaignForm);
                        
                        const response = await fetch(`/campaigns/{{ $campaign->uuid ?? $campaign->id }}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            this.showNotification('Campaign updated successfully!', 'success');
                            // Redirect to campaign page
                            setTimeout(() => {
                                window.location.href = `/campaigns/{{ $campaign->uuid ?? $campaign->id }}`;
                            }, 1000);
                        } else {
                            const result = await response.json();
                            if (result.errors) {
                                this.errors = result.errors;
                            }
                            throw new Error('Failed to update campaign');
                        }
                        
                    } catch (error) {
                        console.error('Error updating campaign:', error);
                        this.showNotification('Failed to update campaign. Please try again.', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                // Save as draft
                async saveDraft() {
                    const originalStatus = this.form.status;
                    this.form.status = 'draft';
                    await this.submitForm();
                    this.form.status = originalStatus;
                },

                // Auto-save functionality
                async autoSave() {
                    // Simple auto-save to localStorage
                    localStorage.setItem('campaign-edit-{{ $campaign->uuid ?? $campaign->id }}', JSON.stringify(this.form));
                },

                // Check for unsaved changes
                hasUnsavedChanges() {
                    const originalForm = {
                        title: @json($campaign->getTitle()),
                        description: @json($campaign->getDescription()),
                        goal_amount: @json($campaign->goal_amount),
                        end_date: @json($campaign->end_date?->format('Y-m-d')),
                        category_id: @json($campaign->category_id),
                        status: @json($campaign->status),
                        allow_anonymous: @json((bool) $campaign->allow_anonymous)
                    };
                    
                    return JSON.stringify(this.form) !== JSON.stringify(originalForm);
                },

                // Image handling
                previewImage(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.imagePreview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                },

                clearImagePreview() {
                    this.imagePreview = null;
                    document.getElementById('featured_image').value = '';
                },

                removeCurrentImage() {
                    if (confirm('Are you sure you want to remove the current image?')) {
                        // Add hidden input to mark image for removal
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'remove_image';
                        input.value = '1';
                        this.$refs.campaignForm.appendChild(input);
                        
                        // Hide the current image
                        event.target.closest('.relative').style.display = 'none';
                    }
                },

                // Validation helpers
                validateEndDate() {
                    const endDate = new Date(this.form.end_date);
                    const today = new Date();
                    const maxDate = new Date();
                    maxDate.setFullYear(today.getFullYear() + 2);
                    
                    if (endDate <= today) {
                        this.errors.end_date = 'End date must be in the future';
                    } else if (endDate > maxDate) {
                        this.errors.end_date = 'End date cannot be more than 2 years from now';
                    } else {
                        delete this.errors.end_date;
                    }
                },

                // Helper functions
                updateSlug() {
                    // Could be used for URL slug generation if needed
                },

                updateProgress() {
                    // Update any progress-related calculations
                },

                getDaysFromNow(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    const today = new Date();
                    const diffTime = date - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays === 1) return 'Tomorrow';
                    if (diffDays < 0) return 'In the past';
                    return `${diffDays} days from now`;
                },

                previewCampaign() {
                    // Open campaign preview in new tab
                    window.open(`/campaigns/{{ $campaign->uuid ?? $campaign->id }}?preview=1`, '_blank');
                },

                showNotification(message, type = 'info') {
                    // Simple notification system
                    console.log(`${type.toUpperCase()}: ${message}`);
                    
                    // Create a simple toast notification
                    const toast = document.createElement('div');
                    toast.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white text-sm ${
                        type === 'success' ? 'bg-green-600' : 
                        type === 'error' ? 'bg-red-600' : 'bg-blue-600'
                    }`;
                    toast.textContent = message;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            };
        }
    </script>
</x-layout>