<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ViewPresenter;

use Illuminate\Support\Str;

final class FormComponentPresenter
{
    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function generateInputData(array $props): array
    {
        $inputId = $props['id'] ?? $props['name'] ?? 'input-' . Str::random(8);
        $errorId = $inputId . '-error';
        $hintId = $inputId . '-hint';
        $hasError = ! empty($props['error']);

        return [
            'inputId' => $inputId,
            'errorId' => $errorId,
            'hintId' => $hintId,
            'hasError' => $hasError,
            'classes' => self::buildInputClasses($props, $hasError),
            'ariaInvalid' => $hasError ? 'true' : null,
            'ariaDescribedBy' => self::buildAriaDescribedBy($hasError, $errorId, $hintId, $props),
            'oldValue' => old($props['name'], $props['value'] ?? ''),
            'requiredAttribute' => $props['required'] ? 'required' : null,
            'disabledAttribute' => $props['disabled'] ? 'disabled' : null,
            'readonlyAttribute' => $props['readonly'] ? 'readonly' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function generateTextareaData(array $props): array
    {
        $inputId = $props['id'] ?? $props['name'] ?? 'textarea-' . Str::random(8);
        $errorId = $inputId . '-error';
        $hintId = $inputId . '-hint';
        $hasError = ! empty($props['error']);

        return [
            'inputId' => $inputId,
            'errorId' => $errorId,
            'hintId' => $hintId,
            'hasError' => $hasError,
            'classes' => self::buildTextareaClasses($hasError),
            'ariaInvalid' => $hasError ? 'true' : null,
            'ariaDescribedBy' => self::buildAriaDescribedBy($hasError, $errorId, $hintId, $props),
            'oldValue' => old($props['name'], $props['value'] ?? ''),
            'requiredAttribute' => $props['required'] ? 'required' : null,
            'disabledAttribute' => $props['disabled'] ? 'disabled' : null,
            'readonlyAttribute' => $props['readonly'] ? 'readonly' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function generateSelectData(array $props): array
    {
        $inputId = $props['id'] ?? $props['name'] ?? 'select-' . Str::random(8);
        $errorId = $inputId . '-error';
        $hintId = $inputId . '-hint';
        $hasError = ! empty($props['error']);

        return [
            'inputId' => $inputId,
            'errorId' => $errorId,
            'hintId' => $hintId,
            'hasError' => $hasError,
            'classes' => self::buildSelectClasses($hasError),
            'ariaInvalid' => $hasError ? 'true' : null,
            'ariaDescribedBy' => self::buildAriaDescribedBy($hasError, $errorId, $hintId, $props),
            'selectedValue' => old($props['name'], $props['value'] ?? ''),
            'requiredAttribute' => $props['required'] ? 'required' : null,
            'disabledAttribute' => $props['disabled'] ? 'disabled' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function generateEnhancedInputData(array $props): array
    {
        $inputId = $props['name'] ?: 'input-' . Str::random(8);
        $errorId = $inputId . '-error';
        $helpId = $inputId . '-help';
        $hasError = ! empty($props['error']);

        return [
            'inputId' => $inputId,
            'errorId' => $errorId,
            'helpId' => $helpId,
            'hasError' => $hasError,
            'classes' => self::buildEnhancedInputClasses($props, $hasError),
            'ariaInvalid' => $hasError ? 'true' : null,
            'ariaDescribedBy' => self::buildEnhancedAriaDescribedBy($hasError, $errorId, $helpId, $props),
            'oldValue' => old($props['name'], $props['value'] ?? ''),
            'hasValue' => ! empty($props['value']),
            'requiredAttribute' => $props['required'] ? 'required' : null,
            'disabledAttribute' => $props['disabled'] ? 'disabled' : null,
            'readonlyAttribute' => $props['readonly'] ? 'readonly' : null,
            'minAttribute' => $props['min'] ?? null,
            'maxAttribute' => $props['max'] ?? null,
            'stepAttribute' => $props['step'] ?? null,
            'autocompleteAttribute' => $props['autocomplete'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function generateEnhancedSelectData(array $props): array
    {
        $selectId = $props['name'] ?: 'select-' . Str::random(8);
        $errorId = $selectId . '-error';
        $helpId = $selectId . '-help';
        $hasError = ! empty($props['error']);
        $selectedValue = old($props['name'], $props['value'] ?? '');

        return [
            'selectId' => $selectId,
            'errorId' => $errorId,
            'helpId' => $helpId,
            'hasError' => $hasError,
            'classes' => self::buildEnhancedSelectClasses($props, $hasError),
            'ariaInvalid' => $hasError ? 'true' : null,
            'ariaDescribedBy' => self::buildEnhancedAriaDescribedBy($hasError, $errorId, $helpId, $props),
            'selectedValue' => $selectedValue,
            'hasValue' => ! empty($selectedValue),
            'requiredAttribute' => $props['required'] ? 'required' : null,
            'disabledAttribute' => $props['disabled'] ? 'disabled' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function buildInputClasses(array $props, bool $hasError): string
    {
        $baseClasses = 'block w-full rounded-lg border px-4 py-3 text-gray-900 placeholder-gray-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 dark:disabled:bg-gray-700';

        if ($hasError) {
            $baseClasses .= ' border-red-300 focus:ring-red-500 focus:border-red-500 dark:border-red-600';
        } else {
            $baseClasses .= ' border-gray-300 dark:border-gray-600';
        }

        if (! empty($props['icon'])) {
            $baseClasses .= ' pl-10';
        }

        return $baseClasses;
    }

    private static function buildTextareaClasses(bool $hasError): string
    {
        $baseClasses = 'block w-full rounded-lg border px-4 py-3 text-gray-900 placeholder-gray-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 dark:disabled:bg-gray-700 resize-vertical';

        if ($hasError) {
            $baseClasses .= ' border-red-300 focus:ring-red-500 focus:border-red-500 dark:border-red-600';
        } else {
            $baseClasses .= ' border-gray-300 dark:border-gray-600';
        }

        return $baseClasses;
    }

    private static function buildSelectClasses(bool $hasError): string
    {
        $baseClasses = 'block w-full rounded-lg border px-4 py-3 text-gray-900 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-100 dark:disabled:bg-gray-700';

        if ($hasError) {
            $baseClasses .= ' border-red-300 focus:ring-red-500 focus:border-red-500 dark:border-red-600';
        } else {
            $baseClasses .= ' border-gray-300 dark:border-gray-600';
        }

        return $baseClasses;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function buildEnhancedInputClasses(array $props, bool $hasError): string
    {
        $classes = [
            'block w-full rounded-lg border-0 py-3 px-4 text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-inset transition-colors duration-200',
            'bg-white dark:bg-gray-800',
            $hasError
                ? 'ring-2 ring-red-300 dark:ring-red-600 focus:ring-red-500'
                : 'ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-primary',
            (bool) ($props['disabled'] ?? false) ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed' : '',
            (bool) ($props['readonly'] ?? false) ? 'bg-gray-50 dark:bg-gray-700' : '',
            empty($props['icon']) ? '' : 'pl-10',
            empty($props['prefix']) ? '' : 'pl-12',
            empty($props['suffix']) ? '' : 'pr-12',
        ];

        return implode(' ', array_filter($classes, fn (string $class): bool => $class !== ''));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function buildEnhancedSelectClasses(array $props, bool $hasError): string
    {
        $classes = [
            'block w-full rounded-lg border-0 py-3 px-4 text-gray-900 dark:text-white focus:ring-2 focus:ring-inset transition-colors duration-200',
            'bg-white dark:bg-gray-800',
            $hasError
                ? 'ring-2 ring-red-300 dark:ring-red-600 focus:ring-red-500'
                : 'ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-primary',
            (bool) ($props['disabled'] ?? false) ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed' : '',
            empty($props['icon']) ? '' : 'pl-10',
        ];

        return implode(' ', array_filter($classes, fn (string $class): bool => $class !== ''));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function buildAriaDescribedBy(bool $hasError, string $errorId, string $hintId, array $props): ?string
    {
        if ($hasError) {
            return $errorId;
        }

        if (! empty($props['hint'])) {
            return $hintId;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function buildEnhancedAriaDescribedBy(bool $hasError, string $errorId, string $helpId, array $props): ?string
    {
        if ($hasError) {
            return $errorId;
        }

        if (! empty($props['help'])) {
            return $helpId;
        }

        return null;
    }
}
