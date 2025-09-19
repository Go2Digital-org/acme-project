<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Support\Str;

final readonly class SearchHighlightService
{
    /**
     * Highlight search terms in text with XSS protection.
     */
    public function highlightText(
        string $text,
        string $searchTerm,
        string $highlightClass = 'search-highlight',
        bool $caseSensitive = false,
        int $contextLength = 0,
    ): string {
        // Sanitize inputs
        $text = $this->sanitizeText($text);
        $searchTerm = $this->sanitizeSearchTerm($searchTerm);

        if ($searchTerm === '' || $searchTerm === '0' || ($text === '' || $text === '0')) {
            return $text;
        }

        // Get context around matches if specified
        if ($contextLength > 0) {
            $text = $this->extractContext($text, $searchTerm, $contextLength, $caseSensitive);
        }

        // Perform highlighting
        return $this->performHighlighting($text, $searchTerm, $highlightClass, $caseSensitive);
    }

    /**
     * Highlight multiple search terms in text.
     *
     * @param  array<int, string>  $searchTerms
     */
    public function highlightMultipleTerms(
        string $text,
        array $searchTerms,
        string $highlightClass = 'search-highlight',
        bool $caseSensitive = false,
        int $contextLength = 0,
    ): string {
        // Sanitize inputs
        $text = $this->sanitizeText($text);
        $searchTerms = array_filter(array_map([$this, 'sanitizeSearchTerm'], $searchTerms));

        if ($searchTerms === [] || ($text === '' || $text === '0')) {
            return $text;
        }

        // Get context around matches if specified
        if ($contextLength > 0) {
            $allTerms = implode('|', array_map('preg_quote', $searchTerms));
            $text = $this->extractContextMultiple($text, $allTerms, $contextLength, $caseSensitive);
        }

        // Highlight each term with different classes
        foreach ($searchTerms as $index => $term) {
            $termClass = $highlightClass . ($index > 0 ? '-' . ($index + 1) : '');
            $text = $this->performHighlighting($text, $term, $termClass, $caseSensitive);
        }

        return $text;
    }

    /**
     * Generate search result snippet with highlighted terms.
     */
    public function generateSnippet(
        string $text,
        string $searchTerm,
        int $snippetLength = 200,
        string $highlightClass = 'search-highlight',
        bool $caseSensitive = false,
    ): string {
        // Sanitize inputs
        $text = $this->sanitizeText($text);
        $searchTerm = $this->sanitizeSearchTerm($searchTerm);

        if ($searchTerm === '' || $searchTerm === '0' || ($text === '' || $text === '0')) {
            return Str::limit($text, $snippetLength);
        }

        // Find the first occurrence of the search term
        $position = $caseSensitive
            ? strpos($text, $searchTerm)
            : stripos($text, $searchTerm);

        if ($position === false) {
            // Term not found, return beginning of text
            return Str::limit($text, $snippetLength);
        }

        // Calculate snippet boundaries
        $termLength = strlen($searchTerm);
        $halfLength = (int) (($snippetLength - $termLength) / 2);

        $start = max(0, $position - $halfLength);
        $end = min(strlen($text), $position + $termLength + $halfLength);

        // Adjust to word boundaries
        if ($start > 0) {
            $wordStart = strrpos(substr($text, 0, $start), ' ');

            if ($wordStart !== false) {
                $start = $wordStart + 1;
            }
        }

        if ($end < strlen($text)) {
            $wordEnd = strpos(substr($text, $end), ' ');

            if ($wordEnd !== false) {
                $end += $wordEnd;
            }
        }

        // Extract snippet
        $snippet = substr($text, $start, $end - $start);

        // Add ellipsis if needed
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }

        if ($end < strlen($text)) {
            $snippet .= '...';
        }

        // Highlight the search term
        return $this->performHighlighting($snippet, $searchTerm, $highlightClass, $caseSensitive);
    }

    /**
     * Get HTML-safe highlighting markup.
     *
     * @return array<string, string>
     */
    public function getHighlightMarkup(string $highlightClass = 'search-highlight'): array
    {
        return [
            'open' => '<mark class="' . htmlspecialchars($highlightClass, ENT_QUOTES, 'UTF-8') . '">',
            'close' => '</mark>',
        ];
    }

    /**
     * Remove highlighting from text.
     */
    public function removeHighlighting(string $text): string
    {
        // Remove mark tags and their content preservation
        $cleanedText = preg_replace('/<mark[^>]*>/i', '', $text);

        return str_replace('</mark>', '', $cleanedText ?? '');
    }

    /**
     * Get CSS for search highlighting.
     */
    public function getHighlightCss(): string
    {
        return '
            .search-highlight {
                background-color: #fef08a;
                color: #854d0e;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-weight: 500;
            }
            
            .search-highlight-2 {
                background-color: #bfdbfe;
                color: #1e3a8a;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-weight: 500;
            }
            
            .search-highlight-3 {
                background-color: #fecaca;
                color: #991b1b;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-weight: 500;
            }
            
            .search-highlight-4 {
                background-color: #d8b4fe;
                color: #581c87;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-weight: 500;
            }
            
            .search-highlight-5 {
                background-color: #bbf7d0;
                color: #14532d;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-weight: 500;
            }
            
            @media (prefers-color-scheme: dark) {
                .search-highlight {
                    background-color: #451a03;
                    color: #fef08a;
                }
                
                .search-highlight-2 {
                    background-color: #1e3a8a;
                    color: #bfdbfe;
                }
                
                .search-highlight-3 {
                    background-color: #991b1b;
                    color: #fecaca;
                }
                
                .search-highlight-4 {
                    background-color: #581c87;
                    color: #d8b4fe;
                }
                
                .search-highlight-5 {
                    background-color: #14532d;
                    color: #bbf7d0;
                }
            }
        ';
    }

    /**
     * Count occurrences of search terms in text.
     *
     * @param  array<int, string>  $searchTerms
     * @return array<string, int>
     */
    public function countOccurrences(string $text, array $searchTerms, bool $caseSensitive = false): array
    {
        $text = $this->sanitizeText($text);
        $counts = [];

        foreach ($searchTerms as $term) {
            $term = $this->sanitizeSearchTerm($term);

            if ($term === '') {
                continue;
            }

            if ($term === '0') {
                continue;
            }

            $count = $caseSensitive
                ? substr_count($text, $term)
                : substr_count(strtolower($text), strtolower($term));

            $counts[$term] = $count;
        }

        return $counts;
    }

    /**
     * Check if text contains any of the search terms.
     *
     * @param  array<int, string>  $searchTerms
     */
    public function containsAnyTerm(string $text, array $searchTerms, bool $caseSensitive = false): bool
    {
        $text = $this->sanitizeText($text);

        if (! $caseSensitive) {
            $text = strtolower($text);
        }

        foreach ($searchTerms as $term) {
            $term = $this->sanitizeSearchTerm($term);

            if ($term === '') {
                continue;
            }

            if ($term === '0') {
                continue;
            }

            $searchTerm = $caseSensitive ? $term : strtolower($term);

            if (str_contains($text, $searchTerm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize text input to prevent XSS.
     */
    private function sanitizeText(string $text): string
    {
        // Remove potential XSS vectors but preserve basic formatting
        $text = strip_tags($text, '<p><br><strong><em><u>');

        // Encode remaining HTML entities
        return htmlspecialchars_decode(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), ENT_QUOTES);
    }

    /**
     * Sanitize search term input.
     */
    private function sanitizeSearchTerm(string $searchTerm): string
    {
        // Remove HTML tags and trim whitespace
        $searchTerm = strip_tags(trim($searchTerm));

        // Remove excessive whitespace
        return preg_replace('/\s+/', ' ', $searchTerm) ?? '';
    }

    /**
     * Perform the actual highlighting with proper escaping.
     */
    private function performHighlighting(
        string $text,
        string $searchTerm,
        string $highlightClass,
        bool $caseSensitive,
    ): string {
        if ($searchTerm === '' || $searchTerm === '0') {
            return $text;
        }

        $markup = $this->getHighlightMarkup($highlightClass);

        // Escape search term for regex
        $escapedTerm = preg_quote($searchTerm, '/');
        $pattern = $caseSensitive ? "/{$escapedTerm}/" : "/{$escapedTerm}/i";

        // Replace with highlighting markup
        return preg_replace(
            $pattern,
            $markup['open'] . '$0' . $markup['close'],
            $text,
        ) ?? $text;
    }

    /**
     * Extract context around search term.
     */
    private function extractContext(
        string $text,
        string $searchTerm,
        int $contextLength,
        bool $caseSensitive,
    ): string {
        $position = $caseSensitive
            ? strpos($text, $searchTerm)
            : stripos($text, $searchTerm);

        if ($position === false) {
            return Str::limit($text, $contextLength * 2);
        }

        $start = max(0, $position - $contextLength);
        $end = min(strlen($text), $position + strlen($searchTerm) + $contextLength);

        $context = substr($text, $start, $end - $start);

        // Add ellipsis if we truncated
        if ($start > 0) {
            $context = '...' . $context;
        }

        if ($end < strlen($text)) {
            return $context . '...';
        }

        return $context;
    }

    /**
     * Extract context around multiple search terms.
     */
    private function extractContextMultiple(
        string $text,
        string $pattern,
        int $contextLength,
        bool $caseSensitive,
    ): string {
        $modifiers = $caseSensitive ? '' : 'i';
        $fullPattern = "/{$pattern}/{$modifiers}";

        if (! preg_match($fullPattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return Str::limit($text, $contextLength * 2);
        }

        $position = $matches[0][1];
        $matchLength = strlen($matches[0][0]);

        $start = max(0, $position - $contextLength);
        $end = min(strlen($text), $position + $matchLength + $contextLength);

        $context = substr($text, $start, $end - $start);

        // Add ellipsis if we truncated
        if ($start > 0) {
            $context = '...' . $context;
        }

        if ($end < strlen($text)) {
            return $context . '...';
        }

        return $context;
    }
}
