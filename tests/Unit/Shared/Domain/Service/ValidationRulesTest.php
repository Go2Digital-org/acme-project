<?php

declare(strict_types=1);

use Modules\Shared\Domain\Service\ValidationRulesService;

beforeEach(function (): void {
    $this->service = new ValidationRulesService;
});

describe('Email Validation', function (): void {
    it('validates standard email addresses', function (): void {
        expect($this->service->validateEmail('user@example.com'))->toBeTrue()
            ->and($this->service->validateEmail('test.email@domain.org'))->toBeTrue()
            ->and($this->service->validateEmail('user+tag@example.com'))->toBeTrue()
            ->and($this->service->validateEmail('user_name@example.com'))->toBeTrue();
    });

    it('validates international domain emails', function (): void {
        expect($this->service->validateEmail('user@пример.рф'))->toBeFalse() // Cyrillic domain
            ->and($this->service->validateEmail('user@münchen.de'))->toBeFalse() // Umlaut
            ->and($this->service->validateEmail('user@example.москва'))->toBeFalse() // Cyrillic TLD
            ->and($this->service->validateEmail('user@sub.domain.co.uk'))->toBeTrue(); // Multiple subdomains
    });

    it('rejects invalid email formats', function (): void {
        expect($this->service->validateEmail('invalid-email'))->toBeFalse()
            ->and($this->service->validateEmail('@example.com'))->toBeFalse()
            ->and($this->service->validateEmail('user@'))->toBeFalse()
            ->and($this->service->validateEmail('user@.com'))->toBeFalse()
            ->and($this->service->validateEmail('user@domain.'))->toBeFalse()
            ->and($this->service->validateEmail('user@domain..com'))->toBeFalse();
    });

    it('handles edge cases for email validation', function (): void {
        expect($this->service->validateEmail(''))->toBeFalse()
            ->and($this->service->validateEmail('user@localhost'))->toBeFalse() // No TLD
            ->and($this->service->validateEmail('user@192.168.1.1'))->toBeFalse() // IP address
            ->and($this->service->validateEmail('very.long.email.address.that.should.work@very.long.domain.name.example.com'))->toBeTrue();
    });
});

describe('Phone Number Validation', function (): void {
    it('validates US phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('(555) 123-4567', 'US'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('555-123-4567', 'US'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('5551234567', 'US'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+1 555 123 4567', 'US'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('555-123-456', 'US'))->toBeFalse(); // Too short
    });

    it('validates Canadian phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('416-555-1234', 'CA'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+1 416 555 1234', 'CA'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('4165551234', 'CA'))->toBeTrue();
    });

    it('validates UK phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('020 7946 0958', 'GB'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+44 20 7946 0958', 'GB'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('02079460958', 'GB'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('07700 900123', 'GB'))->toBeTrue(); // Mobile
    });

    it('validates German phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('030 12345678', 'DE'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+49 30 12345678', 'DE'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('03012345678', 'DE'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('0151 12345678', 'DE'))->toBeTrue(); // Mobile
    });

    it('validates French phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('01 23 45 67 89', 'FR'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+33 1 23 45 67 89', 'FR'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('0123456789', 'FR'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('06 12 34 56 78', 'FR'))->toBeTrue(); // Mobile
    });

    it('validates Dutch phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('020 123 4567', 'NL'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('+31 20 123 4567', 'NL'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('0201234567', 'NL'))->toBeTrue()
            ->and($this->service->validatePhoneNumber('06 12345678', 'NL'))->toBeTrue(); // Mobile
    });

    it('validates international phone numbers', function (): void {
        expect($this->service->validatePhoneNumber('+81 3 1234 5678'))->toBeTrue() // Japan
            ->and($this->service->validatePhoneNumber('+61 2 1234 5678'))->toBeTrue() // Australia
            ->and($this->service->validatePhoneNumber('+86 138 0013 8000'))->toBeTrue() // China
            ->and($this->service->validatePhoneNumber('123456'))->toBeFalse() // Too short
            ->and($this->service->validatePhoneNumber('12345678901234567890'))->toBeFalse(); // Too long
    });
});

describe('URL Validation', function (): void {
    it('validates standard URLs', function (): void {
        expect($this->service->validateUrl('https://www.example.com'))->toBeTrue()
            ->and($this->service->validateUrl('http://example.com'))->toBeTrue()
            ->and($this->service->validateUrl('https://subdomain.example.com/path'))->toBeTrue()
            ->and($this->service->validateUrl('https://example.com/path/to/resource?param=value'))->toBeTrue();
    });

    it('validates FTP URLs', function (): void {
        expect($this->service->validateUrl('ftp://ftp.example.com'))->toBeTrue()
            ->and($this->service->validateUrl('ftps://secure.ftp.example.com'))->toBeTrue();
    });

    it('validates localhost URLs', function (): void {
        expect($this->service->validateUrl('http://localhost:8000'))->toBeTrue()
            ->and($this->service->validateUrl('https://127.0.0.1:3000'))->toBeTrue()
            ->and($this->service->validateUrl('http://[::1]:8080'))->toBeTrue();
    });

    it('rejects invalid URL schemes', function (): void {
        expect($this->service->validateUrl('javascript:alert(1)'))->toBeFalse()
            ->and($this->service->validateUrl('data:text/html,<script>alert(1)</script>'))->toBeFalse()
            ->and($this->service->validateUrl('file:///etc/passwd'))->toBeFalse();
    });

    it('rejects malformed URLs', function (): void {
        expect($this->service->validateUrl('not-a-url'))->toBeFalse()
            ->and($this->service->validateUrl('http://'))->toBeFalse()
            ->and($this->service->validateUrl('https://.'))->toBeFalse()
            ->and($this->service->validateUrl('https://example..com'))->toBeFalse();
    });
});

describe('Credit Card Validation', function (): void {
    it('validates Visa cards', function (): void {
        expect($this->service->validateCreditCard('4532015112830366'))->toBeTrue() // Valid Visa
            ->and($this->service->validateCreditCard('4532-0151-1283-0366'))->toBeTrue() // With dashes
            ->and($this->service->validateCreditCard('4532 0151 1283 0366'))->toBeTrue(); // With spaces
    });

    it('validates MasterCard cards', function (): void {
        expect($this->service->validateCreditCard('5555555555554444'))->toBeTrue() // Valid MasterCard
            ->and($this->service->validateCreditCard('5105105105105100'))->toBeTrue();
    });

    it('validates American Express cards', function (): void {
        expect($this->service->validateCreditCard('378282246310005'))->toBeTrue() // Valid AmEx
            ->and($this->service->validateCreditCard('371449635398431'))->toBeTrue();
    });

    it('rejects invalid credit cards', function (): void {
        expect($this->service->validateCreditCard('4532015112830367'))->toBeFalse() // Invalid Luhn
            ->and($this->service->validateCreditCard('123456789'))->toBeFalse() // Too short
            ->and($this->service->validateCreditCard('12345678901234567890'))->toBeFalse() // Too long
            ->and($this->service->validateCreditCard('abcd1234efgh5678'))->toBeFalse(); // Non-numeric
    });
});

describe('Tax ID Validation', function (): void {
    it('validates US Social Security Numbers', function (): void {
        expect($this->service->validateTaxId('123456789', 'US'))->toBeTrue()
            ->and($this->service->validateTaxId('123-45-6789', 'US'))->toBeTrue()
            ->and($this->service->validateTaxId('12345678', 'US'))->toBeFalse() // Too short
            ->and($this->service->validateTaxId('1234567890', 'US'))->toBeFalse(); // Too long
    });

    it('validates UK National Insurance Numbers', function (): void {
        expect($this->service->validateTaxId('AB123456C', 'GB'))->toBeTrue()
            ->and($this->service->validateTaxId('JG 12 34 56 C', 'GB'))->toBeTrue()
            ->and($this->service->validateTaxId('AB123456', 'GB'))->toBeTrue() // Without suffix
            ->and($this->service->validateTaxId('AB12345', 'GB'))->toBeFalse(); // Too short
    });

    it('validates German Tax IDs', function (): void {
        expect($this->service->validateTaxId('12345678901', 'DE'))->toBeTrue()
            ->and($this->service->validateTaxId('123 456 789 01', 'DE'))->toBeTrue()
            ->and($this->service->validateTaxId('1234567890', 'DE'))->toBeFalse(); // Too short
    });

    it('validates French SIREN numbers', function (): void {
        expect($this->service->validateTaxId('123456789', 'FR'))->toBeTrue()
            ->and($this->service->validateTaxId('123 456 789', 'FR'))->toBeTrue()
            ->and($this->service->validateTaxId('12345678', 'FR'))->toBeFalse(); // Too short
    });

    it('validates Dutch BSN numbers', function (): void {
        expect($this->service->validateTaxId('123456782', 'NL'))->toBeTrue() // Valid BSN
            ->and($this->service->validateTaxId('111222333', 'NL'))->toBeTrue()
            ->and($this->service->validateTaxId('123456781', 'NL'))->toBeFalse() // Invalid checksum
            ->and($this->service->validateTaxId('1234567', 'NL'))->toBeFalse(); // Too short
    });
});

describe('Postal Code Validation', function (): void {
    it('validates US ZIP codes', function (): void {
        expect($this->service->validatePostalCode('12345', 'US'))->toBeTrue()
            ->and($this->service->validatePostalCode('12345-6789', 'US'))->toBeTrue()
            ->and($this->service->validatePostalCode('1234', 'US'))->toBeFalse() // Too short
            ->and($this->service->validatePostalCode('ABCDE', 'US'))->toBeFalse(); // Non-numeric
    });

    it('validates Canadian postal codes', function (): void {
        expect($this->service->validatePostalCode('K1A 0A6', 'CA'))->toBeTrue()
            ->and($this->service->validatePostalCode('K1A0A6', 'CA'))->toBeTrue()
            ->and($this->service->validatePostalCode('k1a 0a6', 'CA'))->toBeTrue() // Lowercase
            ->and($this->service->validatePostalCode('K1A 0A', 'CA'))->toBeFalse(); // Incomplete
    });

    it('validates UK postal codes', function (): void {
        expect($this->service->validatePostalCode('SW1A 1AA', 'GB'))->toBeTrue()
            ->and($this->service->validatePostalCode('M1 1AA', 'GB'))->toBeTrue()
            ->and($this->service->validatePostalCode('M60 1NW', 'GB'))->toBeTrue()
            ->and($this->service->validatePostalCode('B33 8TH', 'GB'))->toBeTrue()
            ->and($this->service->validatePostalCode('SW1A', 'GB'))->toBeFalse(); // Incomplete
    });

    it('validates German postal codes', function (): void {
        expect($this->service->validatePostalCode('12345', 'DE'))->toBeTrue()
            ->and($this->service->validatePostalCode('01234', 'DE'))->toBeTrue()
            ->and($this->service->validatePostalCode('1234', 'DE'))->toBeFalse() // Too short
            ->and($this->service->validatePostalCode('123456', 'DE'))->toBeFalse(); // Too long
    });

    it('validates Dutch postal codes', function (): void {
        expect($this->service->validatePostalCode('1234 AB', 'NL'))->toBeTrue()
            ->and($this->service->validatePostalCode('1234AB', 'NL'))->toBeTrue()
            ->and($this->service->validatePostalCode('1234 ab', 'NL'))->toBeTrue() // Lowercase
            ->and($this->service->validatePostalCode('123 AB', 'NL'))->toBeFalse() // Wrong format
            ->and($this->service->validatePostalCode('1234 A1', 'NL'))->toBeFalse(); // Number in suffix
    });

    it('validates other country postal codes', function (): void {
        expect($this->service->validatePostalCode('2000', 'AU'))->toBeTrue() // Australia
            ->and($this->service->validatePostalCode('100-0001', 'JP'))->toBeTrue() // Japan
            ->and($this->service->validatePostalCode('1000001', 'JP'))->toBeTrue() // Japan without dash
            ->and($this->service->validatePostalCode('75001', 'FR'))->toBeTrue(); // France
    });
});

describe('IBAN Validation', function (): void {
    it('validates European IBANs', function (): void {
        expect($this->service->validateIban('GB82 WEST 1234 5698 7654 32'))->toBeTrue() // UK
            ->and($this->service->validateIban('DE89 3704 0044 0532 0130 00'))->toBeTrue() // Germany
            ->and($this->service->validateIban('FR14 2004 1010 0505 0001 3M02 606'))->toBeTrue() // France
            ->and($this->service->validateIban('NL91 ABNA 0417 1643 00'))->toBeTrue(); // Netherlands
    });

    it('validates IBANs without spaces', function (): void {
        expect($this->service->validateIban('GB82WEST12345698765432'))->toBeTrue()
            ->and($this->service->validateIban('DE89370400440532013000'))->toBeTrue();
    });

    it('rejects invalid IBANs', function (): void {
        expect($this->service->validateIban('GB82 WEST 1234 5698 7654 33'))->toBeFalse() // Wrong checksum
            ->and($this->service->validateIban('XY12 3456 7890 1234'))->toBeFalse() // Invalid country
            ->and($this->service->validateIban('GB82'))->toBeFalse() // Too short
            ->and($this->service->validateIban('GB82WEST123456987654321234567890123456789'))->toBeFalse(); // Too long
    });
});

describe('Strong Password Validation', function (): void {
    it('validates strong passwords', function (): void {
        expect($this->service->validateStrongPassword('Password123!'))->toBeTrue()
            ->and($this->service->validateStrongPassword('MyStr0ng@Pass'))->toBeTrue()
            ->and($this->service->validateStrongPassword('C0mpl3x#P@ssw0rd'))->toBeTrue();
    });

    it('validates passwords with custom requirements', function (): void {
        expect($this->service->validateStrongPassword('shortpw', 6, false, true, false, false))->toBeTrue() // Only lowercase required
            ->and($this->service->validateStrongPassword('PASSWORD', 6, true, false, false, false))->toBeTrue() // Only uppercase required
            ->and($this->service->validateStrongPassword('password123', 6, false, true, true, false))->toBeTrue(); // Lowercase + numbers
    });

    it('rejects weak passwords', function (): void {
        expect($this->service->validateStrongPassword('password'))->toBeFalse() // No uppercase, numbers, special chars
            ->and($this->service->validateStrongPassword('PASSWORD'))->toBeFalse() // No lowercase, numbers, special chars
            ->and($this->service->validateStrongPassword('Password'))->toBeFalse() // No numbers, special chars
            ->and($this->service->validateStrongPassword('Pass1'))->toBeFalse() // Too short
            ->and($this->service->validateStrongPassword(''))->toBeFalse(); // Empty
    });
});

describe('Username Validation', function (): void {
    it('validates proper usernames', function (): void {
        expect($this->service->validateUsername('user123'))->toBeTrue()
            ->and($this->service->validateUsername('john_doe'))->toBeTrue()
            ->and($this->service->validateUsername('jane-smith'))->toBeTrue()
            ->and($this->service->validateUsername('a1b2c3'))->toBeTrue()
            ->and($this->service->validateUsername('user'))->toBeTrue(); // Minimum length
    });

    it('rejects invalid usernames', function (): void {
        expect($this->service->validateUsername('ab'))->toBeFalse() // Too short
            ->and($this->service->validateUsername('this_username_is_way_too_long_for_our_system'))->toBeFalse() // Too long
            ->and($this->service->validateUsername('_underscore_start'))->toBeFalse() // Starts with underscore
            ->and($this->service->validateUsername('-hyphen_start'))->toBeFalse() // Starts with hyphen
            ->and($this->service->validateUsername('username_'))->toBeFalse() // Ends with underscore
            ->and($this->service->validateUsername('username-'))->toBeFalse() // Ends with hyphen
            ->and($this->service->validateUsername('user__name'))->toBeFalse() // Consecutive underscores
            ->and($this->service->validateUsername('user--name'))->toBeFalse() // Consecutive hyphens
            ->and($this->service->validateUsername('user@name'))->toBeFalse() // Invalid character
            ->and($this->service->validateUsername('user name'))->toBeFalse(); // Space
    });
});

describe('Campaign Amount Validation', function (): void {
    it('validates valid campaign amounts', function (): void {
        expect($this->service->validateCampaignAmount(100.00))->toBeTrue()
            ->and($this->service->validateCampaignAmount(1.0))->toBeTrue() // Minimum
            ->and($this->service->validateCampaignAmount(50000.50))->toBeTrue()
            ->and($this->service->validateCampaignAmount(999999.99))->toBeTrue();
    });

    it('validates with custom limits', function (): void {
        expect($this->service->validateCampaignAmount(500.0, 100.0, 1000.0))->toBeTrue()
            ->and($this->service->validateCampaignAmount(100.0, 100.0, 1000.0))->toBeTrue() // At minimum
            ->and($this->service->validateCampaignAmount(1000.0, 100.0, 1000.0))->toBeTrue(); // At maximum
    });

    it('rejects invalid campaign amounts', function (): void {
        expect($this->service->validateCampaignAmount(0.50))->toBeFalse() // Below minimum
            ->and($this->service->validateCampaignAmount(1000001.0))->toBeFalse() // Above maximum
            ->and($this->service->validateCampaignAmount(100.123))->toBeFalse() // Too many decimal places
            ->and($this->service->validateCampaignAmount(50.0, 100.0, 1000.0))->toBeFalse() // Below custom minimum
            ->and($this->service->validateCampaignAmount(1500.0, 100.0, 1000.0))->toBeFalse(); // Above custom maximum
    });
});

describe('Tax Exemption Status Validation', function (): void {
    it('validates default tax exemption statuses', function (): void {
        expect($this->service->validateTaxExemptionStatus('exempt'))->toBeTrue()
            ->and($this->service->validateTaxExemptionStatus('non-exempt'))->toBeTrue()
            ->and($this->service->validateTaxExemptionStatus('pending'))->toBeTrue()
            ->and($this->service->validateTaxExemptionStatus('EXEMPT'))->toBeTrue() // Case insensitive
            ->and($this->service->validateTaxExemptionStatus('Pending'))->toBeTrue();
    });

    it('validates custom tax exemption statuses', function (): void {
        $customStatuses = ['approved', 'rejected', 'under-review'];
        expect($this->service->validateTaxExemptionStatus('approved', $customStatuses))->toBeTrue()
            ->and($this->service->validateTaxExemptionStatus('rejected', $customStatuses))->toBeTrue()
            ->and($this->service->validateTaxExemptionStatus('under-review', $customStatuses))->toBeTrue();
    });

    it('rejects invalid tax exemption statuses', function (): void {
        expect($this->service->validateTaxExemptionStatus('invalid'))->toBeFalse()
            ->and($this->service->validateTaxExemptionStatus(''))->toBeFalse()
            ->and($this->service->validateTaxExemptionStatus('unknown', ['approved', 'rejected']))->toBeFalse();
    });
});

describe('Edge Cases and Security Tests', function (): void {
    it('handles null and empty inputs safely', function (): void {
        expect($this->service->validateEmail(''))->toBeFalse()
            ->and($this->service->validatePhoneNumber('', 'US'))->toBeFalse()
            ->and($this->service->validateUrl(''))->toBeFalse()
            ->and($this->service->validateCreditCard(''))->toBeFalse()
            ->and($this->service->validateIban(''))->toBeFalse()
            ->and($this->service->validateUsername(''))->toBeFalse();
    });

    it('handles malicious inputs safely', function (): void {
        expect($this->service->validateEmail('<script>alert(1)</script>@example.com'))->toBeFalse()
            ->and($this->service->validateUrl('javascript:alert(document.cookie)'))->toBeFalse()
            ->and($this->service->validateUsername('admin\'; DROP TABLE users; --'))->toBeFalse()
            ->and($this->service->validateStrongPassword('password\x00null'))->toBeFalse();
    });

    it('handles unicode and special characters properly', function (): void {
        expect($this->service->validateUsername('user🎉'))->toBeFalse() // Emoji
            ->and($this->service->validateEmail('test@example.com'))->toBeTrue() // Standard email
            ->and($this->service->validateStrongPassword('Pässwørd123!'))->toBeTrue() // Unicode in password
            ->and($this->service->validatePhoneNumber('+33 1 23 45 67 89', 'FR'))->toBeTrue(); // Spaces in phone
    });

    it('validates extremely long inputs', function (): void {
        $longString = str_repeat('a', 1000);
        expect($this->service->validateEmail($longString . '@example.com'))->toBeFalse()
            ->and($this->service->validateUsername($longString))->toBeFalse()
            ->and($this->service->validateCreditCard($longString))->toBeFalse();
    });

    it('validates boundary conditions', function (): void {
        // Minimum valid lengths
        expect($this->service->validateUsername('abc'))->toBeTrue() // Minimum username
            ->and($this->service->validateStrongPassword('Aa1!', 4, true, true, true, true))->toBeTrue() // Minimum strong password
            ->and($this->service->validateCampaignAmount(1.0))->toBeTrue(); // Minimum campaign amount

        // Maximum valid lengths
        expect($this->service->validateUsername(str_repeat('a', 30)))->toBeTrue() // Maximum username
            ->and($this->service->validateCampaignAmount(999999.99))->toBeTrue(); // Near maximum campaign amount
    });
});
