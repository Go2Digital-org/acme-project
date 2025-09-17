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
    
    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="{{ $icon }} h-5 w-5 text-gray-400" aria-hidden="true"></i>
            </div>
        @endif
        
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $componentData['inputId'] }}"
            value="{{ $componentData['oldValue'] }}"
            placeholder="{{ $placeholder }}"
            @if($componentData['requiredAttribute']) {{ $componentData['requiredAttribute'] }} @endif
            @if($componentData['disabledAttribute']) {{ $componentData['disabledAttribute'] }} @endif
            @if($componentData['readonlyAttribute']) {{ $componentData['readonlyAttribute'] }} @endif
            @if($componentData['ariaInvalid']) aria-invalid="{{ $componentData['ariaInvalid'] }}" @endif
            @if($componentData['ariaDescribedBy']) aria-describedby="{{ $componentData['ariaDescribedBy'] }}" @endif
            {{ $attributes->merge(['class' => $componentData['classes']]) }}
        />
    </div>
    
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