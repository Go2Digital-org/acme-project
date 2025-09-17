<div class="space-y-1">
    @if($label)
        <label
            for="{{ $componentData['inputId'] }}"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1" aria-label="required">*</span>
            @endif
        </label>
    @endif
    
    <select
        name="{{ $name }}"
        id="{{ $componentData['inputId'] }}"
        @if($componentData['requiredAttribute']) {{ $componentData['requiredAttribute'] }} @endif
        @if($componentData['disabledAttribute']) {{ $componentData['disabledAttribute'] }} @endif
        @if($componentData['ariaInvalid']) aria-invalid="{{ $componentData['ariaInvalid'] }}" @endif
        @if($componentData['ariaDescribedBy']) aria-describedby="{{ $componentData['ariaDescribedBy'] }}" @endif
        {{ $attributes->merge(['class' => $componentData['classes']]) }}
    >
        @if($placeholder)
            <option value="" disabled {{ empty($componentData['selectedValue']) ? 'selected' : '' }}>
                {{ $placeholder }}
            </option>
        @endif
        
        @if($options)
            @foreach($options as $optionValue => $optionLabel)
                <option 
                    value="{{ $optionValue }}" 
                    {{ (string) $componentData['selectedValue'] === (string) $optionValue ? 'selected' : '' }}
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        @endif
        
        {{ $slot }}
    </select>
    
    @if($componentData['hasError'])
        <p
            id="{{ $componentData['errorId'] }}"
            class="text-sm text-red-600 dark:text-red-400"
            role="alert"
        >
            {{ $error }}
        </p>
    @elseif($hint)
        <p
            id="{{ $componentData['hintId'] }}"
            class="text-sm text-gray-500 dark:text-gray-400"
        >
            {{ $hint }}
        </p>
    @endif
</div>