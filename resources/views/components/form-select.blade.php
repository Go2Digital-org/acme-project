<div class="space-y-2" x-data="{ focused: false, hasValue: {{ $componentData['hasValue'] ? 'true' : 'false' }} }">
    {{-- Label --}}
    @if($label)
        <label 
            for="{{ $componentData['selectId'] }}" 
            class="block text-sm font-medium text-gray-900 dark:text-white"
        >
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1" aria-label="required">*</span>
            @endif
        </label>
    @endif

    {{-- Select wrapper --}}
    <div class="relative">
        {{-- Icon --}}
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="{{ $icon }} text-gray-400 dark:text-gray-500"></i>
            </div>
        @endif

        {{-- Select field --}}
        <select
            id="{{ $componentData['selectId'] }}"
            name="{{ $name }}"
            @if($componentData['requiredAttribute']) {{ $componentData['requiredAttribute'] }} @endif
            @if($componentData['disabledAttribute']) {{ $componentData['disabledAttribute'] }} @endif
            @if($componentData['ariaInvalid']) aria-invalid="{{ $componentData['ariaInvalid'] }}" @endif
            @if($componentData['ariaDescribedBy']) aria-describedby="{{ $componentData['ariaDescribedBy'] }}" @endif
            class="{{ $componentData['classes'] }}"
            x-on:focus="focused = true"
            x-on:blur="focused = false"
            x-on:change="hasValue = $event.target.value.length > 0"
            {{ $attributes }}
        >
            {{-- Empty option --}}
            @if($emptyOption)
                <option value="" {{ empty($componentData['selectedValue']) ? 'selected' : '' }}>{{ $placeholder }}</option>
            @endif

            {{-- Options from array --}}
            @if(is_array($options))
                @foreach($options as $optionValue => $optionLabel)
                    <option 
                        value="{{ $optionValue }}" 
                        {{ $componentData['selectedValue'] == $optionValue ? 'selected' : '' }}
                    >
                        {{ $optionLabel }}
                    </option>
                @endforeach
            @else
                {{-- Slot for custom options --}}
                {{ $slot }}
            @endif
        </select>

        {{-- Custom dropdown arrow --}}
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        {{-- State indicator --}}
        <div 
            class="absolute inset-y-0 right-8 flex items-center pointer-events-none"
            x-show="hasValue && !focused"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            @if($componentData['hasError'])
                <i class="fas fa-exclamation-circle text-red-500 text-sm"></i>
            @else
                <i class="fas fa-check-circle text-secondary text-sm"></i>
            @endif
        </div>
    </div>

    {{-- Error message --}}
    @if($componentData['hasError'])
        <p 
            id="{{ $componentData['errorId'] }}" 
            class="text-sm text-red-600 dark:text-red-400"
            role="alert"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform translate-y-1"
            x-transition:enter-end="opacity-100 transform translate-y-0"
        >
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ $error }}
        </p>
    @endif

    {{-- Help text --}}
    @if($help && !$componentData['hasError'])
        <p 
            id="{{ $componentData['helpId'] }}" 
            class="text-sm text-gray-600 dark:text-gray-400"
        >
            {{ $help }}
        </p>
    @endif
</div>