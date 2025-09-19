<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates HTML content for security and safety.
 */
final readonly class SafeHtmlRule implements ValidationRule
{
    /** @var list<string> */
    private array $allowedTags;

    /** @var list<string> */
    private array $dangerousPatterns;

    /**
     * @param  list<string>|null  $allowedTags
     */
    public function __construct(?array $allowedTags = null)
    {
        $this->allowedTags = $allowedTags ?? [
            'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'a', 'blockquote',
        ];

        $this->dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onclick\s*=/i',
            '/onerror\s*=/i',
            '/onmouseover\s*=/i',
        ];
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        // Check for dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute contains potentially dangerous content.');

                return;
            }
        }

        // Extract all tags from the HTML
        preg_match_all('/<([a-zA-Z0-9]+)(?:\s|>|\/)/i', $value, $matches);
        $usedTags = array_map('strtolower', $matches[1]);

        // Check if all used tags are allowed
        $disallowedTags = array_diff(array_unique($usedTags), $this->allowedTags);

        if ($disallowedTags !== []) {
            $fail('The :attribute contains disallowed HTML tags: ' . implode(', ', $disallowedTags) . '.');
        }
    }
}
