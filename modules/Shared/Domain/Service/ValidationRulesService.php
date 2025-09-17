<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Service;

/**
 * Comprehensive validation service for business rules and data validation.
 * Provides validation for various formats including international standards.
 */
class ValidationRulesService
{
    /**
     * Validate email address with international domain support.
     */
    public function validateEmail(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Additional checks for international domains
        $atPosition = strrchr($email, '@');
        if ($atPosition === false) {
            return false;
        }
        $domain = substr($atPosition, 1);

        // Check for valid domain format
        if (! preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
            return false;
        }

        // Check for consecutive dots
        return ! str_contains($domain, '..');
    }

    /**
     * Validate phone number for multiple countries.
     */
    public function validatePhoneNumber(string $phone, ?string $countryCode = null): bool
    {
        // Remove all non-digit characters except +
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        if ($cleanPhone === null || $cleanPhone === '') {
            return false;
        }

        // Country-specific validation
        return match ($countryCode) {
            'US', 'CA' => $this->validateNorthAmericanPhone($cleanPhone),
            'GB' => $this->validateUKPhone($cleanPhone),
            'DE' => $this->validateGermanPhone($cleanPhone),
            'FR' => $this->validateFrenchPhone($cleanPhone),
            'NL' => $this->validateDutchPhone($cleanPhone),
            default => $this->validateInternationalPhone($cleanPhone),
        };
    }

    /**
     * Validate URL including special cases.
     */
    public function validateUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check for valid scheme
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https', 'ftp', 'ftps'], true)) {
            return false;
        }

        // Check for localhost and IP addresses
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        // Allow localhost for development (including IPv6)
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        // Handle IPv6 addresses in brackets
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $ipv6 = substr($host, 1, -1);
            if ($ipv6 === '::1') {
                return true;
            }
        }

        // Check for valid domain
        return $this->validateDomain($host);
    }

    /**
     * Validate credit card using Luhn algorithm.
     */
    public function validateCreditCard(string $cardNumber): bool
    {
        $cleanedCardNumber = preg_replace('/\D/', '', $cardNumber);

        if ($cleanedCardNumber === null) {
            return false;
        }

        if (strlen($cleanedCardNumber) < 13 || strlen($cleanedCardNumber) > 19) {
            return false;
        }

        return $this->luhnCheck($cleanedCardNumber);
    }

    /**
     * Validate tax ID for different countries.
     */
    public function validateTaxId(string $taxId, string $countryCode): bool
    {
        $cleanTaxId = preg_replace('/[^A-Za-z0-9]/', '', $taxId);
        if ($cleanTaxId === null) {
            return false;
        }

        return match (strtoupper($countryCode)) {
            'US' => $this->validateUSSSN($cleanTaxId),
            'GB' => $this->validateUKNIN($cleanTaxId),
            'DE' => $this->validateGermanTaxId($cleanTaxId),
            'FR' => $this->validateFrenchSIREN($cleanTaxId),
            'NL' => $this->validateDutchBSN($cleanTaxId),
            default => strlen($cleanTaxId) >= 5 && strlen($cleanTaxId) <= 20,
        };
    }

    /**
     * Validate postal code for different countries.
     */
    public function validatePostalCode(string $postalCode, string $countryCode): bool
    {
        $cleanCode = trim($postalCode);

        return match (strtoupper($countryCode)) {
            'US' => preg_match('/^\d{5}(-\d{4})?$/', $cleanCode) === 1,
            'CA' => preg_match('/^[A-Za-z]\d[A-Za-z] ?\d[A-Za-z]\d$/', $cleanCode) === 1,
            'GB' => preg_match('/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/i', $cleanCode) === 1,
            'DE' => preg_match('/^\d{5}$/', $cleanCode) === 1,
            'FR' => preg_match('/^\d{5}$/', $cleanCode) === 1,
            'NL' => preg_match('/^\d{4} ?[A-Z]{2}$/i', $cleanCode) === 1,
            'AU' => preg_match('/^\d{4}$/', $cleanCode) === 1,
            'JP' => preg_match('/^\d{3}-?\d{4}$/', $cleanCode) === 1,
            default => strlen($cleanCode) >= 3 && strlen($cleanCode) <= 10,
        };
    }

    /**
     * Validate IBAN (International Bank Account Number).
     */
    public function validateIban(string $iban): bool
    {
        $cleanIban = preg_replace('/\s/', '', $iban);
        if ($cleanIban === null) {
            return false;
        }
        $iban = strtoupper($cleanIban);

        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Move first 4 characters to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, etc.)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (string) (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod 97
        return $this->mod97($numeric) === 1;
    }

    /**
     * Validate strong password with configurable requirements.
     */
    public function validateStrongPassword(
        string $password,
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ): bool {
        if (strlen($password) < $minLength) {
            return false;
        }

        if ($requireUppercase && ! preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($requireLowercase && ! preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($requireNumbers && ! preg_match('/\d/', $password)) {
            return false;
        }

        return ! ($requireSpecialChars && ! preg_match('/[^A-Za-z0-9]/', $password));
    }

    /**
     * Validate username with business rules.
     */
    public function validateUsername(string $username): bool
    {
        // Length check
        if (strlen($username) < 3 || strlen($username) > 30) {
            return false;
        }

        // Only alphanumeric, underscore, and hyphen allowed
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return false;
        }

        // Must start with letter or number
        if (! preg_match('/^[a-zA-Z0-9]/', $username)) {
            return false;
        }

        // Must not end with underscore or hyphen
        if (preg_match('/[_-]$/', $username)) {
            return false;
        }

        // No consecutive special characters
        return ! preg_match('/[_-]{2,}/', $username);
    }

    /**
     * Validate custom business rule for campaign amounts.
     */
    public function validateCampaignAmount(float $amount, float $minAmount = 1.0, float $maxAmount = 1000000.0): bool
    {
        if ($amount < $minAmount || $amount > $maxAmount) {
            return false;
        }

        // Check for reasonable decimal places (max 2)
        $decimalParts = explode('.', (string) $amount);

        return ! (count($decimalParts) > 1 && strlen($decimalParts[1]) > 2);
    }

    /**
     * Validate organization tax exemption status.
     */
    /**
     * @param  array<string>  $allowedStatuses
     */
    public function validateTaxExemptionStatus(string $status, array $allowedStatuses = ['exempt', 'non-exempt', 'pending']): bool
    {
        return in_array(strtolower($status), $allowedStatuses, true);
    }

    /**
     * Luhn algorithm for credit card validation.
     */
    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Validate North American phone numbers.
     */
    private function validateNorthAmericanPhone(string $phone): bool
    {
        // Remove +1 country code if present
        if (str_starts_with($phone, '+1')) {
            $phone = substr($phone, 2);
        }

        return preg_match('/^\d{10}$/', $phone) === 1;
    }

    /**
     * Validate UK phone numbers.
     */
    private function validateUKPhone(string $phone): bool
    {
        // Remove +44 country code if present
        if (str_starts_with($phone, '+44')) {
            $phone = '0' . substr($phone, 3);
        }

        return preg_match('/^0\d{10}$/', $phone) === 1;
    }

    /**
     * Validate German phone numbers.
     */
    private function validateGermanPhone(string $phone): bool
    {
        // Remove +49 country code if present
        if (str_starts_with($phone, '+49')) {
            $phone = '0' . substr($phone, 3);
        }

        return preg_match('/^0\d{10,11}$/', $phone) === 1;
    }

    /**
     * Validate French phone numbers.
     */
    private function validateFrenchPhone(string $phone): bool
    {
        // Remove +33 country code if present
        if (str_starts_with($phone, '+33')) {
            $phone = '0' . substr($phone, 3);
        }

        return preg_match('/^0[1-9]\d{8}$/', $phone) === 1;
    }

    /**
     * Validate Dutch phone numbers.
     */
    private function validateDutchPhone(string $phone): bool
    {
        // Remove +31 country code if present
        if (str_starts_with($phone, '+31')) {
            $phone = '0' . substr($phone, 3);
        }

        return preg_match('/^0[1-9]\d{8}$/', $phone) === 1;
    }

    /**
     * Validate international phone numbers.
     */
    private function validateInternationalPhone(string $phone): bool
    {
        // Basic international format validation
        if (str_starts_with($phone, '+')) {
            return preg_match('/^\+\d{7,15}$/', $phone) === 1;
        }

        return preg_match('/^\d{7,15}$/', $phone) === 1;
    }

    /**
     * Validate domain name.
     */
    private function validateDomain(string $domain): bool
    {
        return preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain) === 1
            && ! str_contains($domain, '..');
    }

    /**
     * Validate US Social Security Number.
     */
    private function validateUSSSN(string $ssn): bool
    {
        return preg_match('/^\d{9}$/', $ssn) === 1;
    }

    /**
     * Validate UK National Insurance Number.
     */
    private function validateUKNIN(string $nin): bool
    {
        return preg_match('/^[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z]\d{6}[A-D]?$/i', $nin) === 1;
    }

    /**
     * Validate German Tax ID.
     */
    private function validateGermanTaxId(string $taxId): bool
    {
        return preg_match('/^\d{11}$/', $taxId) === 1;
    }

    /**
     * Validate French SIREN number.
     */
    private function validateFrenchSIREN(string $siren): bool
    {
        return preg_match('/^\d{9}$/', $siren) === 1;
    }

    /**
     * Validate Dutch BSN (Burgerservicenummer).
     */
    private function validateDutchBSN(string $bsn): bool
    {
        if (! preg_match('/^\d{8,9}$/', $bsn)) {
            return false;
        }

        // BSN validation algorithm
        $digits = str_split($bsn);
        $sum = 0;

        for ($i = 0; $i < count($digits) - 1; $i++) {
            $sum += (int) $digits[$i] * (9 - $i);
        }

        return ($sum % 11) === (int) $digits[count($digits) - 1];
    }

    /**
     * Calculate mod 97 for large numbers (IBAN validation).
     */
    private function mod97(string $number): int
    {
        $remainder = 0;

        for ($i = 0; $i < strlen($number); $i++) {
            $remainder = ($remainder * 10 + (int) $number[$i]) % 97;
        }

        return $remainder;
    }
}
