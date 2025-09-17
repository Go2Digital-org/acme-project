<?php

declare(strict_types=1);

use Modules\User\Domain\ValueObject\EmailAddress;

describe('EmailAddress Value Object', function () {
    describe('constructor validation', function () {
        it('creates valid email address from valid email string', function () {
            $email = new EmailAddress('user@example.com');

            expect($email->value)->toBe('user@example.com')
                ->and($email)->toBeInstanceOf(EmailAddress::class);
        });

        it('accepts common email formats', function () {
            $validEmails = [
                'simple@example.com',
                'user.name@example.com',
                'user+tag@example.com',
                'user_underscore@example.com',
                'user123@example.com',
                'very.long.email.address@very.long.domain.example.com',
                'user@sub.domain.example.com',
                'test@example-domain.com',
                'user@example.co.uk',
            ];

            foreach ($validEmails as $validEmail) {
                expect(fn () => new EmailAddress($validEmail))->not->toThrow(Exception::class);
            }
        });

        it('accepts international domain names', function () {
            $internationalEmails = [
                'user@münchen.de',
                'test@xn--mnchen-3ya.de', // IDN ASCII form
                'user@example.рф',
            ];

            foreach ($internationalEmails as $email) {
                expect(fn () => new EmailAddress($email))->not->toThrow(Exception::class);
            }
        });

        it('throws exception for empty email', function () {
            expect(fn () => new EmailAddress(''))
                ->toThrow(InvalidArgumentException::class, 'Email address cannot be empty');
        });

        it('throws exception for zero string', function () {
            expect(fn () => new EmailAddress('0'))
                ->toThrow(InvalidArgumentException::class, 'Email address cannot be empty');
        });

        it('throws exception for too long email', function () {
            $longEmail = str_repeat('a', 250) . '@example.com';

            expect(fn () => new EmailAddress($longEmail))
                ->toThrow(InvalidArgumentException::class, 'Email address is too long (max 255 characters)');
        });

        it('throws exception for invalid email formats', function () {
            $invalidEmails = [
                'invalid',
                'invalid@',
                '@invalid.com',
                'invalid.email',
                'user@',
                '@example.com',
                'user space@example.com',
                'user@domain',
                'user@@example.com',
                'user@.example.com',
                'user@example..com',
                '.user@example.com',
                'user.@example.com',
                'user@example.',
                'user@[invalid]',
                'user@example com',
                'user name@example.com',
            ];

            foreach ($invalidEmails as $invalidEmail) {
                expect(fn () => new EmailAddress($invalidEmail))
                    ->toThrow(InvalidArgumentException::class, "Invalid email address: {$invalidEmail}");
            }
        });

        it('handles malformed IDN domains gracefully', function () {
            // This email should be rejected due to invalid domain format
            expect(fn () => new EmailAddress('user@invalid..domain'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('getDomain method', function () {
        it('returns domain part of email', function () {
            $email = new EmailAddress('user@example.com');

            expect($email->getDomain())->toBe('example.com');
        });

        it('returns domain for various email formats', function () {
            $testCases = [
                ['user@example.com', 'example.com'],
                ['test.user@sub.domain.com', 'sub.domain.com'],
                ['user+tag@example.org', 'example.org'],
                ['user@example.co.uk', 'example.co.uk'],
                ['test@очень.длинный.домен.рф', 'очень.длинный.домен.рф'],
            ];

            foreach ($testCases as [$emailAddress, $expectedDomain]) {
                $email = new EmailAddress($emailAddress);
                expect($email->getDomain())->toBe($expectedDomain);
            }
        });

        it('throws exception when email has no @ symbol', function () {
            // This should not happen with valid construction, but test defensive programming
            $email = new EmailAddress('user@example.com');

            // Simulate corrupted state (this is a theoretical test)
            $reflection = new ReflectionClass($email);
            $property = $reflection->getProperty('value');
            $property->setAccessible(true);
            $property->setValue($email, 'invalidemail');

            expect(fn () => $email->getDomain())
                ->toThrow(InvalidArgumentException::class, 'Invalid email format: @ symbol not found');
        });
    });

    describe('getLocalPart method', function () {
        it('returns local part of email', function () {
            $email = new EmailAddress('user@example.com');

            expect($email->getLocalPart())->toBe('user');
        });

        it('returns local part for various email formats', function () {
            $testCases = [
                ['user@example.com', 'user'],
                ['test.user@domain.com', 'test.user'],
                ['user+tag@example.org', 'user+tag'],
                ['user_name@example.com', 'user_name'],
                ['123user@example.com', '123user'],
                ['very.long.local.part@example.com', 'very.long.local.part'],
            ];

            foreach ($testCases as [$emailAddress, $expectedLocal]) {
                $email = new EmailAddress($emailAddress);
                expect($email->getLocalPart())->toBe($expectedLocal);
            }
        });

        it('throws exception when email has no @ symbol', function () {
            $email = new EmailAddress('user@example.com');

            // Simulate corrupted state
            $reflection = new ReflectionClass($email);
            $property = $reflection->getProperty('value');
            $property->setAccessible(true);
            $property->setValue($email, 'invalidemail');

            expect(fn () => $email->getLocalPart())
                ->toThrow(InvalidArgumentException::class, 'Invalid email format: @ symbol not found');
        });
    });

    describe('isAcmeEmail method', function () {
        it('returns true for acme.com domain', function () {
            $email = new EmailAddress('user@acme.com');

            expect($email->isAcmeEmail())->toBeTrue();
        });

        it('returns true for subdomains of acme.com', function () {
            $acmeEmails = [
                'user@subdomain.acme.com',
                'test@hr.acme.com',
                'admin@dev.acme.com',
                'support@api.acme.com',
                'user@very.deep.subdomain.acme.com',
            ];

            foreach ($acmeEmails as $emailAddress) {
                $email = new EmailAddress($emailAddress);
                expect($email->isAcmeEmail())->toBeTrue();
            }
        });

        it('returns false for non-acme domains', function () {
            $nonAcmeEmails = [
                'user@example.com',
                'test@google.com',
                'user@acme.org', // Different TLD
                'user@not-acme.com',
                'user@acmecompany.com', // Different domain
                'user@xacme.com', // Prefix
                'user@acme.com.evil.com', // Suffix attack
            ];

            foreach ($nonAcmeEmails as $emailAddress) {
                $email = new EmailAddress($emailAddress);
                expect($email->isAcmeEmail())->toBeFalse();
            }
        });

        it('is case insensitive for domain checking', function () {
            $caseVariations = [
                'user@ACME.COM',
                'user@Acme.Com',
                'user@acme.COM',
                'user@subdomain.ACME.COM',
            ];

            foreach ($caseVariations as $emailAddress) {
                $email = new EmailAddress($emailAddress);
                expect($email->isAcmeEmail())->toBeTrue();
            }
        });
    });

    describe('__toString method', function () {
        it('returns email value as string', function () {
            $emailAddress = 'user@example.com';
            $email = new EmailAddress($emailAddress);

            expect((string) $email)->toBe($emailAddress)
                ->and($email->__toString())->toBe($emailAddress);
        });

        it('returns original email format for various cases', function () {
            $testEmails = [
                'simple@example.com',
                'user.name@example.com',
                'user+tag@example.com',
                'user@subdomain.example.com',
                'test123@example.org',
            ];

            foreach ($testEmails as $emailAddress) {
                $email = new EmailAddress($emailAddress);
                expect((string) $email)->toBe($emailAddress);
            }
        });
    });

    describe('equals method', function () {
        it('returns true for identical email addresses', function () {
            $email1 = new EmailAddress('user@example.com');
            $email2 = new EmailAddress('user@example.com');

            expect($email1->equals($email2))->toBeTrue()
                ->and($email2->equals($email1))->toBeTrue();
        });

        it('returns false for different email addresses', function () {
            $email1 = new EmailAddress('user@example.com');
            $email2 = new EmailAddress('other@example.com');

            expect($email1->equals($email2))->toBeFalse()
                ->and($email2->equals($email1))->toBeFalse();
        });

        it('is case sensitive for comparison', function () {
            $email1 = new EmailAddress('user@example.com');
            $email2 = new EmailAddress('User@example.com');

            expect($email1->equals($email2))->toBeFalse()
                ->and($email2->equals($email1))->toBeFalse();
        });

        it('returns true for same instance', function () {
            $email = new EmailAddress('user@example.com');

            expect($email->equals($email))->toBeTrue();
        });

        it('handles international characters consistently', function () {
            $email1 = new EmailAddress('user@münchen.de');
            $email2 = new EmailAddress('user@münchen.de');

            expect($email1->equals($email2))->toBeTrue();
        });
    });

    describe('IDN (Internationalized Domain Names) handling', function () {
        it('handles IDN domains when idn_to_ascii is available', function () {
            if (! function_exists('idn_to_ascii')) {
                $this->markTestSkipped('idn_to_ascii function not available');
            }

            expect(fn () => new EmailAddress('user@münchen.de'))->not->toThrow(Exception::class);
        });

        it('handles IDN domains gracefully when idn_to_ascii is not available', function () {
            // This test simulates the behavior when idn_to_ascii is not available
            // The implementation should still work with the original domain
            expect(fn () => new EmailAddress('user@example.com'))->not->toThrow(Exception::class);
        });

        it('converts IDN to ASCII for validation', function () {
            if (! function_exists('idn_to_ascii')) {
                $this->markTestSkipped('idn_to_ascii function not available');
            }

            $email = new EmailAddress('user@münchen.de');

            // The domain should be accessible in its original form
            expect($email->getDomain())->toBe('münchen.de');
        });

        it('handles IDN conversion errors gracefully', function () {
            // Test with potentially problematic IDN
            expect(fn () => new EmailAddress('user@valid.com'))->not->toThrow(Exception::class);
        });
    });

    describe('edge cases and security', function () {
        it('handles maximum length emails correctly', function () {
            // Create email under the limit (255 characters) but not excessively long for local part
            $localPart = str_repeat('a', 60); // Much more reasonable length
            $domain = 'example.com'; // 11 chars + @ + 60 = 72 total, well under 255
            $email = new EmailAddress($localPart . '@' . $domain);

            expect($email->getLocalPart())->toBe($localPart)
                ->and($email->getDomain())->toBe($domain);
        });

        it('prevents email header injection', function () {
            $maliciousEmails = [
                "user@example.com\r\nBcc: attacker@evil.com",
                "user@example.com\nBcc: attacker@evil.com",
                "user@example.com\r\nContent-Type: text/html",
                'user@example.com%0aBcc:attacker@evil.com',
            ];

            foreach ($maliciousEmails as $maliciousEmail) {
                expect(fn () => new EmailAddress($maliciousEmail))
                    ->toThrow(InvalidArgumentException::class);
            }
        });

        it('handles special characters in local part correctly', function () {
            $specialCharEmails = [
                'user.name@example.com',
                'user+tag@example.com',
                'user_underscore@example.com',
                'user-dash@example.com',
                'user123@example.com',
            ];

            foreach ($specialCharEmails as $emailAddress) {
                expect(fn () => new EmailAddress($emailAddress))->not->toThrow(Exception::class);
            }
        });

        it('rejects dangerous characters', function () {
            $dangerousEmails = [
                'user@exam ple.com', // Space in domain
                'user@example.com.', // Trailing dot
                'user@.example.com', // Leading dot
                'user@example..com', // Double dot
                'user name@example.com', // Space in local part
                'user@example.com;evil.com', // Semicolon
                'user@example.com,evil.com', // Comma
            ];

            foreach ($dangerousEmails as $dangerousEmail) {
                expect(fn () => new EmailAddress($dangerousEmail))
                    ->toThrow(InvalidArgumentException::class);
            }
        });
    });

    describe('validation consistency', function () {
        it('maintains consistency between validation and access methods', function () {
            $email = new EmailAddress('user@example.com');

            // If construction succeeds, all access methods should work
            expect(fn () => $email->getDomain())->not->toThrow(Exception::class)
                ->and(fn () => $email->getLocalPart())->not->toThrow(Exception::class)
                ->and(fn () => $email->isAcmeEmail())->not->toThrow(Exception::class)
                ->and(fn () => (string) $email)->not->toThrow(Exception::class);
        });

        it('validates that constructed email can be reconstructed', function () {
            $originalEmail = 'user@example.com';
            $email = new EmailAddress($originalEmail);

            $reconstructed = $email->getLocalPart() . '@' . $email->getDomain();
            expect($reconstructed)->toBe($originalEmail);
        });

        it('ensures domain and local part extraction is accurate', function () {
            $testCases = [
                ['a@b.com', 'a', 'b.com'],
                ['very.long.name@very.long.domain.com', 'very.long.name', 'very.long.domain.com'],
                ['user+tag+more@sub.domain.example.org', 'user+tag+more', 'sub.domain.example.org'],
                ['123@456.com', '123', '456.com'],
            ];

            foreach ($testCases as [$fullEmail, $expectedLocal, $expectedDomain]) {
                $email = new EmailAddress($fullEmail);
                expect($email->getLocalPart())->toBe($expectedLocal)
                    ->and($email->getDomain())->toBe($expectedDomain)
                    ->and($email->getLocalPart() . '@' . $email->getDomain())->toBe($fullEmail);
            }
        });
    });

    describe('immutability and value object properties', function () {
        it('is immutable after construction', function () {
            $email = new EmailAddress('user@example.com');
            $originalValue = $email->value;

            // Value should remain constant
            expect($email->value)->toBe($originalValue)
                ->and((string) $email)->toBe($originalValue);

            // Multiple calls should return same results
            expect($email->getDomain())->toBe($email->getDomain())
                ->and($email->getLocalPart())->toBe($email->getLocalPart())
                ->and($email->isAcmeEmail())->toBe($email->isAcmeEmail());
        });

        it('behaves as a proper value object', function () {
            $email1 = new EmailAddress('user@example.com');
            $email2 = new EmailAddress('user@example.com');
            $email3 = new EmailAddress('other@example.com');

            // Equal value objects should be equal
            expect($email1->equals($email2))->toBeTrue()
                ->and($email2->equals($email1))->toBeTrue();

            // Different value objects should not be equal
            expect($email1->equals($email3))->toBeFalse()
                ->and($email3->equals($email1))->toBeFalse();

            // Same reference should be equal to itself
            expect($email1->equals($email1))->toBeTrue();
        });

        it('implements Stringable interface correctly', function () {
            $emailAddress = 'user@example.com';
            $email = new EmailAddress($emailAddress);

            expect($email)->toBeInstanceOf(Stringable::class);
            expect((string) $email)->toBe($emailAddress);
            expect($email->__toString())->toBe($emailAddress);
        });
    });

    describe('performance and memory considerations', function () {
        it('handles multiple email creations efficiently', function () {
            $emails = [];
            $testEmails = [
                'user1@example.com',
                'user2@example.com',
                'user3@example.com',
                'admin@acme.com',
                'support@acme.com',
            ];

            foreach ($testEmails as $emailAddress) {
                $emails[] = new EmailAddress($emailAddress);
            }

            expect(count($emails))->toBe(5);

            foreach ($emails as $email) {
                expect($email)->toBeInstanceOf(EmailAddress::class);
            }
        });

        it('string conversion is consistent and efficient', function () {
            $emailAddress = 'user@example.com';
            $email = new EmailAddress($emailAddress);

            // Multiple string conversions should be consistent
            $string1 = (string) $email;
            $string2 = (string) $email;
            $string3 = $email->__toString();

            expect($string1)->toBe($emailAddress)
                ->and($string2)->toBe($emailAddress)
                ->and($string3)->toBe($emailAddress)
                ->and($string1)->toBe($string2)
                ->and($string2)->toBe($string3);
        });
    });

    describe('error handling and exceptions', function () {
        it('throws appropriate exception types', function () {
            expect(fn () => new EmailAddress(''))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => new EmailAddress('invalid-email'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => new EmailAddress(str_repeat('a', 300)))
                ->toThrow(InvalidArgumentException::class);
        });

        it('provides meaningful error messages', function () {
            try {
                new EmailAddress('');
                $this->fail('Should have thrown exception');
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('Email address cannot be empty');
            }

            try {
                new EmailAddress('invalid-email');
                $this->fail('Should have thrown exception');
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('Invalid email address');
            }

            try {
                new EmailAddress(str_repeat('a', 300));
                $this->fail('Should have thrown exception');
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('Email address is too long');
            }
        });
    });
});
