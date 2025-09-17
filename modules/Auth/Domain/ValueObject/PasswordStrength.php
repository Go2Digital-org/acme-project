<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Password Strength Value Object.
 *
 * Validates and scores password strength based on security criteria.
 */
class PasswordStrength
{
    private readonly int $score;

    /**
     * @var array<string, mixed>
     */
    private readonly array $requirements;

    /**
     * @var array<string>
     */
    private readonly array $violations;

    public function __construct(private readonly string $password)
    {
        $this->requirements = $this->getSecurityRequirements();
        $this->violations = $this->checkViolations();
        $this->score = $this->calculateScore($this->password);

        if (! $this->isValid()) {
            throw new InvalidArgumentException(
                'Password does not meet security requirements: ' . implode(', ', $this->violations)
            );
        }
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getStrengthLevel(): string
    {
        return match (true) {
            $this->score >= 90 => 'very_strong',
            $this->score >= 75 => 'strong',
            $this->score >= 60 => 'moderate',
            $this->score >= 40 => 'weak',
            default => 'very_weak',
        };
    }

    public function isValid(): bool
    {
        return $this->violations === [];
    }

    /**
     * @return array<string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function hasMinimumLength(): bool
    {
        return strlen($this->password) >= 8;
    }

    public function hasUppercase(): bool
    {
        return preg_match('/[A-Z]/', $this->password) === 1;
    }

    public function hasLowercase(): bool
    {
        return preg_match('/[a-z]/', $this->password) === 1;
    }

    public function hasNumbers(): bool
    {
        return preg_match('/\d/', $this->password) === 1;
    }

    public function hasSpecialCharacters(): bool
    {
        return preg_match('/[^A-Za-z0-9]/', $this->password) === 1;
    }

    public function hasNoCommonPasswords(): bool
    {
        $commonPasswords = [
            'password', '123456', 'password123', 'admin', 'qwerty',
            'letmein', 'welcome', '123456789', 'password1', 'abc123',
        ];

        return ! in_array(strtolower($this->password), $commonPasswords, true);
    }

    public function hasNoSequentialCharacters(): bool
    {
        $sequences = ['123', 'abc', 'qwe', 'asd', 'zxc'];
        $lowerPassword = strtolower($this->password);

        foreach ($sequences as $sequence) {
            if (str_contains($lowerPassword, $sequence)) {
                return false;
            }
        }

        return true;
    }

    public function hasNoRepeatingCharacters(): bool
    {
        return ! preg_match('/(.)\1{2,}/', $this->password);
    }

    public static function validate(string $password): self
    {
        return new self($password);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'strength_level' => $this->getStrengthLevel(),
            'is_valid' => $this->isValid(),
            'violations' => $this->violations,
            'requirements' => $this->requirements,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSecurityRequirements(): array
    {
        return [
            'minimum_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_characters' => true,
            'no_common_passwords' => true,
            'no_sequential_characters' => true,
            'no_repeating_characters' => true,
        ];
    }

    /**
     * @return array<string>
     */
    private function checkViolations(): array
    {
        $violations = [];
        if (! $this->hasMinimumLength()) {
            $violations[] = 'Password must be at least 8 characters long';
        }
        if (! $this->hasUppercase()) {
            $violations[] = 'Password must contain at least one uppercase letter';
        }
        if (! $this->hasLowercase()) {
            $violations[] = 'Password must contain at least one lowercase letter';
        }
        if (! $this->hasNumbers()) {
            $violations[] = 'Password must contain at least one number';
        }
        if (! $this->hasSpecialCharacters()) {
            $violations[] = 'Password must contain at least one special character';
        }
        if (! $this->hasNoCommonPasswords()) {
            $violations[] = 'Password cannot be a common password';
        }
        if (! $this->hasNoSequentialCharacters()) {
            $violations[] = 'Password cannot contain sequential characters';
        }
        if (! $this->hasNoRepeatingCharacters()) {
            $violations[] = 'Password cannot contain more than 2 repeating characters';
        }

        return $violations;
    }

    private function calculateScore(string $password): int
    {
        $score = 0;

        // Length scoring
        $length = strlen($password);
        $score += min(25, $length * 2);

        // Character variety scoring
        if ($this->hasUppercase()) {
            $score += 10;
        }
        if ($this->hasLowercase()) {
            $score += 10;
        }
        if ($this->hasNumbers()) {
            $score += 10;
        }
        if ($this->hasSpecialCharacters()) {
            $score += 15;
        }

        // Complexity bonuses
        if ($length >= 12) {
            $score += 10;
        }
        if ($length >= 16) {
            $score += 10;
        }

        // Security deductions
        if (! $this->hasNoCommonPasswords()) {
            $score -= 30;
        }
        if (! $this->hasNoSequentialCharacters()) {
            $score -= 15;
        }
        if (! $this->hasNoRepeatingCharacters()) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }
}
