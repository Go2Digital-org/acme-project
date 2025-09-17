<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

final readonly class FormComponentService
{
    /**
     * Generate form field data with styling and error handling.
     *
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateFieldData(
        string $name,
        ?string $label = null,
        ?string $type = 'text',
        mixed $value = null,
        ?string $placeholder = null,
        bool $required = false,
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $fieldId = $this->generateFieldId($name);
        $hasError = $errors instanceof ViewErrorBag && $errors->has($name);
        $errorMessage = $hasError ? $errors->first($name) : null;

        return [
            'id' => $fieldId,
            'name' => $name,
            'type' => $type,
            'label' => $label ?? $this->generateLabelFromName($name),
            'value' => old($name, $value),
            'placeholder' => $placeholder,
            'required' => $required,
            'has_error' => $hasError,
            'error_message' => $errorMessage,
            'classes' => $this->generateFieldClasses($hasError, $required),
            'attributes' => $this->generateFieldAttributes($name, $type ?? 'text', $required, $attributes),
            'wrapper_classes' => $this->generateWrapperClasses($hasError),
            'label_classes' => $this->generateLabelClasses($required, $hasError),
            'error_classes' => $this->generateErrorClasses(),
            'help_text_classes' => $this->generateHelpTextClasses(),
        ];
    }

    /**
     * Generate select field data with options.
     *
     * @param  array<mixed, mixed>  $options
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateSelectFieldData(
        string $name,
        array $options,
        ?string $label = null,
        mixed $selected = null,
        ?string $placeholder = null,
        bool $required = false,
        bool $multiple = false,
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $baseData = $this->generateFieldData($name, $label, 'select', $selected, $placeholder, $required, $attributes, $errors);

        return array_merge($baseData, [
            'options' => $this->processSelectOptions($options, old($name, $selected), $multiple),
            'multiple' => $multiple,
            'classes' => $this->generateSelectClasses($baseData['has_error'], $required),
        ]);
    }

    /**
     * Generate checkbox field data.
     *
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateCheckboxFieldData(
        string $name,
        ?string $label = null,
        bool $checked = false,
        ?string $value = '1',
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $fieldId = $this->generateFieldId($name);
        $hasError = $errors instanceof ViewErrorBag && $errors->has($name);
        $isChecked = old($name, $checked ? $value : null) === $value;

        return [
            'id' => $fieldId,
            'name' => $name,
            'type' => 'checkbox',
            'label' => $label ?? $this->generateLabelFromName($name),
            'value' => $value,
            'checked' => $isChecked,
            'has_error' => $hasError,
            'error_message' => $hasError ? $errors->first($name) : null,
            'classes' => $this->generateCheckboxClasses($hasError),
            'wrapper_classes' => $this->generateCheckboxWrapperClasses($hasError),
            'label_classes' => $this->generateCheckboxLabelClasses($hasError),
            'attributes' => $this->generateFieldAttributes($name, 'checkbox', false, $attributes),
        ];
    }

    /**
     * Generate radio button group data.
     *
     * @param  array<mixed, mixed>  $options
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateRadioGroupData(
        string $name,
        array $options,
        ?string $label = null,
        mixed $selected = null,
        bool $required = false,
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $hasError = $errors instanceof ViewErrorBag && $errors->has($name);
        $selectedValue = old($name, $selected);

        $radioButtons = [];

        foreach ($options as $value => $text) {
            $radioId = $this->generateFieldId($name . '_' . $value);
            $radioButtons[] = [
                'id' => $radioId,
                'name' => $name,
                'value' => $value,
                'label' => $text,
                'checked' => $selectedValue === $value,
                'classes' => $this->generateRadioClasses($hasError),
                'label_classes' => $this->generateRadioLabelClasses($hasError),
            ];
        }

        return [
            'name' => $name,
            'group_label' => $label ?? $this->generateLabelFromName($name),
            'required' => $required,
            'has_error' => $hasError,
            'error_message' => $hasError ? $errors->first($name) : null,
            'radio_buttons' => $radioButtons,
            'wrapper_classes' => $this->generateRadioGroupWrapperClasses($hasError),
            'group_label_classes' => $this->generateLabelClasses($required, $hasError),
            'error_classes' => $this->generateErrorClasses(),
        ];
    }

    /**
     * Generate textarea field data.
     *
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateTextareaFieldData(
        string $name,
        ?string $label = null,
        ?string $value = null,
        ?string $placeholder = null,
        bool $required = false,
        int $rows = 4,
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $baseData = $this->generateFieldData($name, $label, 'textarea', $value, $placeholder, $required, $attributes, $errors);

        return array_merge($baseData, [
            'rows' => $rows,
            'classes' => $this->generateTextareaClasses($baseData['has_error'], $required),
        ]);
    }

    /**
     * Generate file upload field data.
     *
     * @param  array<int, string>|null  $acceptedTypes
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public function generateFileFieldData(
        string $name,
        ?string $label = null,
        bool $required = false,
        bool $multiple = false,
        ?array $acceptedTypes = null,
        ?int $maxSizeMB = null,
        ?array $attributes = null,
        ?ViewErrorBag $errors = null,
    ): array {
        $baseData = $this->generateFieldData($name, $label, 'file', null, null, $required, $attributes, $errors);

        return array_merge($baseData, [
            'multiple' => $multiple,
            'accepted_types' => $acceptedTypes,
            'max_size_mb' => $maxSizeMB,
            'accept_attribute' => $acceptedTypes ? implode(',', $acceptedTypes) : null,
            'classes' => $this->generateFileInputClasses($baseData['has_error']),
            'dropzone_classes' => $this->generateDropzoneClasses($baseData['has_error']),
        ]);
    }

    /**
     * Generate form validation summary.
     *
     * @return array<string, mixed>
     */
    public function generateValidationSummary(?ViewErrorBag $errors = null): array
    {
        if (! $errors instanceof ViewErrorBag || ! $errors->any()) {
            return [
                'has_errors' => false,
                'error_count' => 0,
                'errors' => [],
                'summary_classes' => '',
            ];
        }

        return [
            'has_errors' => true,
            'error_count' => $errors->count(),
            'errors' => $errors->all(),
            'summary_classes' => $this->generateValidationSummaryClasses(),
            'title' => $this->getValidationSummaryTitle($errors->count()),
        ];
    }

    /**
     * Generate unique field ID.
     */
    private function generateFieldId(string $name): string
    {
        // Convert array notation to dot notation for ID
        $cleanName = str_replace(['[', ']'], ['.', ''], $name);
        $cleanName = rtrim($cleanName, '.');

        return 'field_' . str_replace('.', '_', $cleanName) . '_' . substr(md5($name), 0, 8);
    }

    /**
     * Generate label from field name.
     */
    private function generateLabelFromName(string $name): string
    {
        // Remove array notation
        $clean = preg_replace('/\[.*?\]/', '', $name);

        // Convert snake_case to Title Case
        return Str::title(str_replace('_', ' ', $clean ?? $name));
    }

    /**
     * Generate field CSS classes.
     */
    private function generateFieldClasses(bool $hasError, bool $required): string
    {
        $baseClasses = [
            'block',
            'w-full',
            'rounded-md',
            'border-gray-300',
            'dark:border-gray-600',
            'dark:bg-gray-700',
            'dark:text-white',
            'shadow-sm',
            'focus:ring-primary',
            'focus:border-primary',
            'sm:text-sm',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'border-red-300',
                'dark:border-red-500',
                'text-red-900',
                'dark:text-red-100',
                'placeholder-red-300',
                'focus:ring-red-500',
                'focus:border-red-500',
            ]);
        }

        if ($required) {
            $baseClasses[] = 'required';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate select field CSS classes.
     */
    private function generateSelectClasses(bool $hasError, bool $required): string
    {
        $baseClasses = [
            'block',
            'w-full',
            'rounded-md',
            'border-gray-300',
            'dark:border-gray-600',
            'dark:bg-gray-700',
            'dark:text-white',
            'shadow-sm',
            'focus:ring-primary',
            'focus:border-primary',
            'sm:text-sm',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'border-red-300',
                'dark:border-red-500',
                'text-red-900',
                'dark:text-red-100',
                'focus:ring-red-500',
                'focus:border-red-500',
            ]);
        }

        if ($required) {
            $baseClasses[] = 'required';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate textarea CSS classes.
     */
    private function generateTextareaClasses(bool $hasError, bool $required): string
    {
        return $this->generateFieldClasses($hasError, $required) . ' resize-vertical';
    }

    /**
     * Generate checkbox CSS classes.
     */
    private function generateCheckboxClasses(bool $hasError): string
    {
        $baseClasses = [
            'h-4',
            'w-4',
            'rounded',
            'border-gray-300',
            'dark:border-gray-600',
            'text-primary',
            'focus:ring-primary',
            'focus:ring-offset-0',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'border-red-300',
                'dark:border-red-500',
                'text-red-600',
                'focus:ring-red-500',
            ]);
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate radio button CSS classes.
     */
    private function generateRadioClasses(bool $hasError): string
    {
        $baseClasses = [
            'h-4',
            'w-4',
            'border-gray-300',
            'dark:border-gray-600',
            'text-primary',
            'focus:ring-primary',
            'focus:ring-offset-0',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'border-red-300',
                'dark:border-red-500',
                'text-red-600',
                'focus:ring-red-500',
            ]);
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate file input CSS classes.
     */
    private function generateFileInputClasses(bool $hasError): string
    {
        $baseClasses = [
            'block',
            'w-full',
            'text-sm',
            'text-gray-500',
            'dark:text-gray-400',
            'file:mr-4',
            'file:py-2',
            'file:px-4',
            'file:rounded-md',
            'file:border-0',
            'file:text-sm',
            'file:font-semibold',
            'file:bg-primary',
            'file:text-white',
            'hover:file:bg-primary-dark',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'text-red-500',
                'dark:text-red-400',
                'file:bg-red-500',
                'hover:file:bg-red-600',
            ]);
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate wrapper CSS classes.
     */
    private function generateWrapperClasses(bool $hasError): string
    {
        $baseClasses = ['mb-4'];

        if ($hasError) {
            $baseClasses[] = 'form-group-error';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate label CSS classes.
     */
    private function generateLabelClasses(bool $required, bool $hasError): string
    {
        $baseClasses = [
            'block',
            'text-sm',
            'font-medium',
            'mb-2',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'text-red-700',
                'dark:text-red-400',
            ]);
        }

        if (! $hasError) {
            $baseClasses = array_merge($baseClasses, [
                'text-gray-700',
                'dark:text-gray-300',
            ]);
        }

        if ($required) {
            $baseClasses[] = 'required-field';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate checkbox label CSS classes.
     */
    private function generateCheckboxLabelClasses(bool $hasError): string
    {
        $baseClasses = [
            'ml-2',
            'text-sm',
            'font-medium',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'text-red-700',
                'dark:text-red-400',
            ]);
        }

        if (! $hasError) {
            $baseClasses = array_merge($baseClasses, [
                'text-gray-700',
                'dark:text-gray-300',
            ]);
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate checkbox wrapper CSS classes.
     */
    private function generateCheckboxWrapperClasses(bool $hasError): string
    {
        $baseClasses = ['flex', 'items-center', 'mb-4'];

        if ($hasError) {
            $baseClasses[] = 'form-group-error';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate radio label CSS classes.
     */
    private function generateRadioLabelClasses(bool $hasError): string
    {
        return $this->generateCheckboxLabelClasses($hasError);
    }

    /**
     * Generate radio group wrapper CSS classes.
     */
    private function generateRadioGroupWrapperClasses(bool $hasError): string
    {
        $baseClasses = ['mb-4'];

        if ($hasError) {
            $baseClasses[] = 'form-group-error';
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate error message CSS classes.
     */
    private function generateErrorClasses(): string
    {
        return 'mt-1 text-sm text-red-600 dark:text-red-400';
    }

    /**
     * Generate help text CSS classes.
     */
    private function generateHelpTextClasses(): string
    {
        return 'mt-1 text-sm text-gray-500 dark:text-gray-400';
    }

    /**
     * Generate dropzone CSS classes.
     */
    private function generateDropzoneClasses(bool $hasError): string
    {
        $baseClasses = [
            'border-2',
            'border-dashed',
            'border-gray-300',
            'dark:border-gray-600',
            'rounded-lg',
            'p-6',
            'text-center',
            'hover:border-primary',
            'dark:hover:border-primary-dark',
            'transition-colors',
        ];

        if ($hasError) {
            $baseClasses = array_merge($baseClasses, [
                'border-red-300',
                'dark:border-red-500',
                'hover:border-red-400',
                'dark:hover:border-red-400',
            ]);
        }

        return implode(' ', $baseClasses);
    }

    /**
     * Generate validation summary CSS classes.
     */
    private function generateValidationSummaryClasses(): string
    {
        return 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-4 mb-6';
    }

    /**
     * Generate field attributes.
     *
     * @param  array<string, mixed>|null  $customAttributes
     * @return array<string, mixed>
     */
    private function generateFieldAttributes(string $name, string $type, bool $required, ?array $customAttributes): array
    {
        $attributes = [
            'name' => $name,
            'type' => $type,
        ];

        if ($required) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }

        if ($customAttributes) {
            return array_merge($attributes, $customAttributes);
        }

        return $attributes;
    }

    /**
     * Process select options.
     *
     * @param  array<mixed, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function processSelectOptions(array $options, mixed $selected, bool $multiple): array
    {
        $processedOptions = [];
        $selectedValues = $multiple && is_array($selected) ? $selected : [$selected];

        foreach ($options as $value => $label) {
            $processedOptions[] = [
                'value' => $value,
                'label' => $label,
                'selected' => in_array($value, $selectedValues, true),
            ];
        }

        return $processedOptions;
    }

    /**
     * Get validation summary title.
     */
    private function getValidationSummaryTitle(int $errorCount): string
    {
        return $errorCount === 1
            ? __('validation.summary_single')
            : __('validation.summary_multiple', ['count' => $errorCount]);
    }
}
