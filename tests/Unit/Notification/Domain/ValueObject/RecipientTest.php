<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\Recipient;
use Modules\User\Domain\ValueObject\EmailAddress;

describe('Recipient Value Object', function (): void {
    describe('Construction', function (): void {
        it('creates recipient with email only', function (): void {
            $email = new EmailAddress('test@example.com');
            $recipient = new Recipient($email);

            expect($recipient->email)->toBeInstanceOf(EmailAddress::class)
                ->and($recipient->email->value)->toBe('test@example.com')
                ->and($recipient->name)->toBeNull()
                ->and($recipient->userId)->toBeNull();
        });

        it('creates recipient with email and name', function (): void {
            $email = new EmailAddress('john.doe@example.com');
            $recipient = new Recipient($email, 'John Doe');

            expect($recipient->email->value)->toBe('john.doe@example.com')
                ->and($recipient->name)->toBe('John Doe')
                ->and($recipient->userId)->toBeNull();
        });

        it('creates recipient with email, name, and user ID', function (): void {
            $email = new EmailAddress('user@company.com');
            $recipient = new Recipient($email, 'Company User', 123);

            expect($recipient->email->value)->toBe('user@company.com')
                ->and($recipient->name)->toBe('Company User')
                ->and($recipient->userId)->toBe(123);
        });

        it('throws exception for empty string name', function (): void {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new Recipient($email, ''))
                ->toThrow(InvalidArgumentException::class, 'Recipient name cannot be empty string');
        });

        it('throws exception for whitespace-only name', function (): void {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new Recipient($email, '   '))
                ->toThrow(InvalidArgumentException::class, 'Recipient name cannot be empty string');
        });

        it('throws exception for zero user ID', function (): void {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new Recipient($email, 'Valid Name', 0))
                ->toThrow(InvalidArgumentException::class, 'User ID must be a positive integer');
        });

        it('throws exception for negative user ID', function (): void {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new Recipient($email, 'Valid Name', -1))
                ->toThrow(InvalidArgumentException::class, 'User ID must be a positive integer');
        });

        it('accepts minimum valid user ID', function (): void {
            $email = new EmailAddress('test@example.com');
            $recipient = new Recipient($email, 'Valid Name', 1);

            expect($recipient->userId)->toBe(1);
        });

        it('accepts large user ID', function (): void {
            $email = new EmailAddress('test@example.com');
            $recipient = new Recipient($email, 'Valid Name', 999999999);

            expect($recipient->userId)->toBe(999999999);
        });

        it('handles special characters in name', function (): void {
            $email = new EmailAddress('test@example.com');
            $name = "O'Connor-Smith, Jr. (PhD)";
            $recipient = new Recipient($email, $name);

            expect($recipient->name)->toBe($name);
        });

        it('handles unicode characters in name', function (): void {
            $email = new EmailAddress('test@example.com');
            $name = 'José María González';
            $recipient = new Recipient($email, $name);

            expect($recipient->name)->toBe($name);
        });

        it('handles very long names', function (): void {
            $email = new EmailAddress('test@example.com');
            $longName = str_repeat('A', 255);
            $recipient = new Recipient($email, $longName);

            expect($recipient->name)->toBe($longName)
                ->and(strlen($recipient->name))->toBe(255);
        });
    });

    describe('Factory Methods', function (): void {
        it('creates recipient from email string via factory method', function (): void {
            $recipient = Recipient::fromEmail('user@example.com');

            expect($recipient->email->value)->toBe('user@example.com')
                ->and($recipient->name)->toBeNull()
                ->and($recipient->userId)->toBeNull();
        });

        it('creates recipient from email string with name via factory method', function (): void {
            $recipient = Recipient::fromEmail('user@example.com', 'Test User');

            expect($recipient->email->value)->toBe('user@example.com')
                ->and($recipient->name)->toBe('Test User')
                ->and($recipient->userId)->toBeNull();
        });

        it('creates recipient from user data via factory method', function (): void {
            $recipient = Recipient::fromUser(456, 'employee@company.com', 'Employee Name');

            expect($recipient->userId)->toBe(456)
                ->and($recipient->email->value)->toBe('employee@company.com')
                ->and($recipient->name)->toBe('Employee Name');
        });

        it('creates recipient from user without name via factory method', function (): void {
            $recipient = Recipient::fromUser(789, 'noname@company.com');

            expect($recipient->userId)->toBe(789)
                ->and($recipient->email->value)->toBe('noname@company.com')
                ->and($recipient->name)->toBeNull();
        });

        it('validates email in fromEmail factory method', function (): void {
            expect(fn () => Recipient::fromEmail('invalid-email'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('validates email in fromUser factory method', function (): void {
            expect(fn () => Recipient::fromUser(123, 'invalid-email'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('validates user ID in fromUser factory method', function (): void {
            expect(fn () => Recipient::fromUser(0, 'valid@email.com'))
                ->toThrow(InvalidArgumentException::class, 'User ID must be a positive integer');
        });

        it('validates name in factory methods', function (): void {
            expect(fn () => Recipient::fromEmail('valid@email.com', ''))
                ->toThrow(InvalidArgumentException::class, 'Recipient name cannot be empty string');

            expect(fn () => Recipient::fromUser(123, 'valid@email.com', ''))
                ->toThrow(InvalidArgumentException::class, 'Recipient name cannot be empty string');
        });

        it('produces equivalent objects when using different factory methods', function (): void {
            $email = 'test@example.com';
            $name = 'Test User';

            $fromEmail = Recipient::fromEmail($email, $name);
            $fromConstructor = new Recipient(new EmailAddress($email), $name);

            expect($fromEmail->equals($fromConstructor))->toBeTrue();
        });
    });

    describe('isRegisteredUser() method', function (): void {
        it('returns true when user ID is present', function (): void {
            $recipient = Recipient::fromUser(123, 'user@example.com', 'User Name');

            expect($recipient->isRegisteredUser())->toBeTrue();
        });

        it('returns false when user ID is null', function (): void {
            $recipient = Recipient::fromEmail('guest@example.com', 'Guest User');

            expect($recipient->isRegisteredUser())->toBeFalse();
        });

        it('returns true for registered user without name', function (): void {
            $recipient = Recipient::fromUser(456, 'anonymous@example.com');

            expect($recipient->isRegisteredUser())->toBeTrue();
        });

        it('returns false for external email with name', function (): void {
            $recipient = Recipient::fromEmail('external@partner.com', 'External Partner');

            expect($recipient->isRegisteredUser())->toBeFalse();
        });
    });

    describe('getDisplayName() method', function (): void {
        it('returns name when present', function (): void {
            $recipient = Recipient::fromEmail('user@example.com', 'John Doe');

            expect($recipient->getDisplayName())->toBe('John Doe');
        });

        it('returns email local part when name is null', function (): void {
            $recipient = Recipient::fromEmail('john.doe@example.com');

            expect($recipient->getDisplayName())->toBe('john.doe');
        });

        it('returns email local part when name is null for registered user', function (): void {
            $recipient = Recipient::fromUser(123, 'employee@company.com');

            expect($recipient->getDisplayName())->toBe('employee');
        });

        it('prefers name over email when both available', function (): void {
            $recipient = Recipient::fromUser(456, 'short@domain.com', 'Very Long Full Name');

            expect($recipient->getDisplayName())->toBe('Very Long Full Name');
        });

        it('handles complex email local parts', function (): void {
            $recipient = Recipient::fromEmail('first.last+tag@company.co.uk');

            expect($recipient->getDisplayName())->toBe('first.last+tag');
        });

        it('handles single character names', function (): void {
            $recipient = Recipient::fromEmail('user@example.com', 'X');

            expect($recipient->getDisplayName())->toBe('X');
        });
    });

    describe('equals() method', function (): void {
        it('returns true for identical recipients', function (): void {
            $recipient1 = Recipient::fromEmail('test@example.com', 'Test User');
            $recipient2 = Recipient::fromEmail('test@example.com', 'Test User');

            expect($recipient1->equals($recipient2))->toBeTrue()
                ->and($recipient2->equals($recipient1))->toBeTrue();
        });

        it('returns true for identical registered users', function (): void {
            $recipient1 = Recipient::fromUser(123, 'user@company.com', 'Company User');
            $recipient2 = Recipient::fromUser(123, 'user@company.com', 'Company User');

            expect($recipient1->equals($recipient2))->toBeTrue();
        });

        it('returns false for different emails', function (): void {
            $recipient1 = Recipient::fromEmail('user1@example.com', 'Same Name');
            $recipient2 = Recipient::fromEmail('user2@example.com', 'Same Name');

            expect($recipient1->equals($recipient2))->toBeFalse();
        });

        it('returns false for different names', function (): void {
            $recipient1 = Recipient::fromEmail('same@example.com', 'Name One');
            $recipient2 = Recipient::fromEmail('same@example.com', 'Name Two');

            expect($recipient1->equals($recipient2))->toBeFalse();
        });

        it('returns false for different user IDs', function (): void {
            $recipient1 = Recipient::fromUser(123, 'same@example.com', 'Same Name');
            $recipient2 = Recipient::fromUser(456, 'same@example.com', 'Same Name');

            expect($recipient1->equals($recipient2))->toBeFalse();
        });

        it('returns false when one has user ID and other does not', function (): void {
            $recipient1 = Recipient::fromUser(123, 'user@example.com', 'User Name');
            $recipient2 = Recipient::fromEmail('user@example.com', 'User Name');

            expect($recipient1->equals($recipient2))->toBeFalse()
                ->and($recipient2->equals($recipient1))->toBeFalse();
        });

        it('returns false when one has name and other does not', function (): void {
            $recipient1 = Recipient::fromEmail('user@example.com', 'User Name');
            $recipient2 = Recipient::fromEmail('user@example.com');

            expect($recipient1->equals($recipient2))->toBeFalse()
                ->and($recipient2->equals($recipient1))->toBeFalse();
        });

        it('is case-sensitive for names', function (): void {
            $recipient1 = Recipient::fromEmail('user@example.com', 'John Doe');
            $recipient2 = Recipient::fromEmail('user@example.com', 'john doe');

            expect($recipient1->equals($recipient2))->toBeFalse();
        });

        it('handles reflexive equality', function (): void {
            $recipient = Recipient::fromUser(123, 'user@example.com', 'Test User');

            expect($recipient->equals($recipient))->toBeTrue();
        });

        it('handles null name comparisons', function (): void {
            $recipient1 = Recipient::fromEmail('user@example.com');
            $recipient2 = Recipient::fromEmail('user@example.com');

            expect($recipient1->equals($recipient2))->toBeTrue();
        });
    });

    describe('__toString() method', function (): void {
        it('formats recipient with name in email format', function (): void {
            $recipient = Recipient::fromEmail('john.doe@example.com', 'John Doe');

            expect((string) $recipient)->toBe('John Doe <john.doe@example.com>');
        });

        it('returns email only when name is not present', function (): void {
            $recipient = Recipient::fromEmail('user@example.com');

            expect((string) $recipient)->toBe('user@example.com');
        });

        it('formats registered user with name correctly', function (): void {
            $recipient = Recipient::fromUser(123, 'employee@company.com', 'Employee Name');

            expect((string) $recipient)->toBe('Employee Name <employee@company.com>');
        });

        it('returns email only for registered user without name', function (): void {
            $recipient = Recipient::fromUser(456, 'anonymous@company.com');

            expect((string) $recipient)->toBe('anonymous@company.com');
        });

        it('handles special characters in name', function (): void {
            $recipient = Recipient::fromEmail('test@example.com', "O'Connor & Smith");

            expect((string) $recipient)->toBe("O'Connor & Smith <test@example.com>");
        });

        it('handles unicode characters in name', function (): void {
            $recipient = Recipient::fromEmail('test@example.com', 'José María');

            expect((string) $recipient)->toBe('José María <test@example.com>');
        });

        it('handles very long names', function (): void {
            $longName = str_repeat('Name', 50);
            $recipient = Recipient::fromEmail('test@example.com', $longName);

            expect((string) $recipient)->toBe("{$longName} <test@example.com>");
        });
    });

    describe('Stringable Interface', function (): void {
        it('implements Stringable interface', function (): void {
            $recipient = Recipient::fromEmail('test@example.com');

            expect($recipient)->toBeInstanceOf(Stringable::class);
        });

        it('can be used in string context', function (): void {
            $recipient = Recipient::fromEmail('user@example.com', 'Test User');
            $message = 'Sending notification to: ' . $recipient;

            expect($message)->toBe('Sending notification to: Test User <user@example.com>');
        });

        it('can be used in string interpolation', function (): void {
            $recipient = Recipient::fromEmail('admin@system.com', 'System Admin');
            $interpolated = "Recipient: {$recipient}";

            expect($interpolated)->toBe('Recipient: System Admin <admin@system.com>');
        });
    });

    describe('Immutability', function (): void {
        it('has readonly properties', function (): void {
            $email = new EmailAddress('test@example.com');
            $recipient = new Recipient($email, 'Test User', 123);

            expect($recipient->email)->toBe($email)
                ->and($recipient->name)->toBe('Test User')
                ->and($recipient->userId)->toBe(123);
        });

        it('cannot modify email property', function (): void {
            $recipient = Recipient::fromEmail('original@example.com');

            expect(function () use ($recipient): void {
                $recipient->email = new EmailAddress('modified@example.com');
            })->toThrow(Error::class);
        });

        it('cannot modify name property', function (): void {
            $recipient = Recipient::fromEmail('test@example.com', 'Original Name');

            expect(function () use ($recipient): void {
                $recipient->name = 'Modified Name';
            })->toThrow(Error::class);
        });

        it('cannot modify userId property', function (): void {
            $recipient = Recipient::fromUser(123, 'test@example.com');

            expect(function () use ($recipient): void {
                $recipient->userId = 456;
            })->toThrow(Error::class);
        });
    });

    describe('Edge Cases', function (): void {
        it('handles minimum length email addresses', function (): void {
            $recipient = Recipient::fromEmail('a@b.co');

            expect($recipient->email->value)->toBe('a@b.co')
                ->and($recipient->getDisplayName())->toBe('a');
        });

        it('handles very long email addresses', function (): void {
            $longLocal = str_repeat('a', 60);
            $longDomain = str_repeat('b', 60) . '.com';
            $longEmail = $longLocal . '@' . $longDomain;

            $recipient = Recipient::fromEmail($longEmail);

            expect($recipient->email->value)->toBe($longEmail)
                ->and($recipient->getDisplayName())->toBe($longLocal);
        });

        it('handles emails with complex local parts', function (): void {
            $complexEmail = 'user.name+tag@sub.domain.example.com';
            $recipient = Recipient::fromEmail($complexEmail);

            expect($recipient->email->value)->toBe($complexEmail)
                ->and($recipient->getDisplayName())->toBe('user.name+tag');
        });

        it('handles single character names', function (): void {
            $recipient = Recipient::fromEmail('test@example.com', 'X');

            expect($recipient->name)->toBe('X')
                ->and($recipient->getDisplayName())->toBe('X')
                ->and((string) $recipient)->toBe('X <test@example.com>');
        });

        it('handles names with only spaces (after trim)', function (): void {
            // This should throw an exception because trimmed it becomes empty
            expect(fn () => Recipient::fromEmail('test@example.com', '   '))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles maximum possible user ID', function (): void {
            $maxUserId = PHP_INT_MAX;
            $recipient = Recipient::fromUser($maxUserId, 'test@example.com');

            expect($recipient->userId)->toBe($maxUserId);
        });
    });

    describe('Email Address Integration', function (): void {
        it('properly wraps EmailAddress value object', function (): void {
            $emailString = 'test@example.com';
            $recipient = Recipient::fromEmail($emailString);

            expect($recipient->email)->toBeInstanceOf(EmailAddress::class)
                ->and($recipient->email->value)->toBe($emailString);
        });

        it('delegates email validation to EmailAddress', function (): void {
            // These should throw because EmailAddress validates them
            expect(fn () => Recipient::fromEmail('invalid-email'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => Recipient::fromEmail(''))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => Recipient::fromEmail('@example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('uses EmailAddress methods correctly', function (): void {
            $recipient = Recipient::fromEmail('user.name@example.com');

            // This depends on EmailAddress having a getLocalPart method
            expect($recipient->getDisplayName())->toBe('user.name');
        });

        it('maintains EmailAddress immutability', function (): void {
            $originalEmail = new EmailAddress('original@example.com');
            $recipient = new Recipient($originalEmail);

            // The recipient should maintain the same email reference
            expect($recipient->email)->toBe($originalEmail);
        });
    });

    describe('Real-world Usage Scenarios', function (): void {
        it('handles employee notification recipient', function (): void {
            $recipient = Recipient::fromUser(12345, 'john.doe@company.com', 'John Doe');

            expect($recipient->isRegisteredUser())->toBeTrue()
                ->and($recipient->getDisplayName())->toBe('John Doe')
                ->and((string) $recipient)->toBe('John Doe <john.doe@company.com>')
                ->and($recipient->email->value)->toBe('john.doe@company.com')
                ->and($recipient->userId)->toBe(12345);
        });

        it('handles external partner notification', function (): void {
            $recipient = Recipient::fromEmail('partner@external.org', 'External Partner LLC');

            expect($recipient->isRegisteredUser())->toBeFalse()
                ->and($recipient->getDisplayName())->toBe('External Partner LLC')
                ->and((string) $recipient)->toBe('External Partner LLC <partner@external.org>')
                ->and($recipient->userId)->toBeNull();
        });

        it('handles anonymous/guest notification', function (): void {
            $recipient = Recipient::fromEmail('guest@visitor.com');

            expect($recipient->isRegisteredUser())->toBeFalse()
                ->and($recipient->getDisplayName())->toBe('guest')
                ->and((string) $recipient)->toBe('guest@visitor.com')
                ->and($recipient->name)->toBeNull()
                ->and($recipient->userId)->toBeNull();
        });

        it('handles system notification recipient', function (): void {
            $recipient = Recipient::fromUser(1, 'admin@system.local', 'System Administrator');

            expect($recipient->isRegisteredUser())->toBeTrue()
                ->and($recipient->getDisplayName())->toBe('System Administrator')
                ->and($recipient->userId)->toBe(1);
        });

        it('handles notification to user without public name', function (): void {
            $recipient = Recipient::fromUser(98765, 'private.user@company.com');

            expect($recipient->isRegisteredUser())->toBeTrue()
                ->and($recipient->getDisplayName())->toBe('private.user')
                ->and((string) $recipient)->toBe('private.user@company.com')
                ->and($recipient->name)->toBeNull();
        });

        it('handles international user with unicode name', function (): void {
            $recipient = Recipient::fromUser(55555, 'user@international.com', 'André Müller');

            expect($recipient->isRegisteredUser())->toBeTrue()
                ->and($recipient->getDisplayName())->toBe('André Müller')
                ->and((string) $recipient)->toBe('André Müller <user@international.com>');
        });
    });

    describe('Comparison and Sorting', function (): void {
        it('provides consistent equality comparison', function (): void {
            $recipient1 = Recipient::fromUser(123, 'user@example.com', 'Test User');
            $recipient2 = Recipient::fromUser(123, 'user@example.com', 'Test User');
            $recipient3 = Recipient::fromUser(456, 'user@example.com', 'Test User');

            expect($recipient1->equals($recipient2))->toBeTrue()
                ->and($recipient2->equals($recipient1))->toBeTrue()
                ->and($recipient1->equals($recipient3))->toBeFalse()
                ->and($recipient3->equals($recipient1))->toBeFalse();
        });

        it('handles null values in equality', function (): void {
            $withName = Recipient::fromEmail('test@example.com', 'Name');
            $withoutName = Recipient::fromEmail('test@example.com');
            $withUserId = Recipient::fromUser(123, 'test@example.com');

            expect($withName->equals($withoutName))->toBeFalse()
                ->and($withoutName->equals($withUserId))->toBeFalse()
                ->and($withName->equals($withUserId))->toBeFalse();
        });

        it('can distinguish between similar recipients', function (): void {
            $employee = Recipient::fromUser(100, 'john@company.com', 'John Smith');
            $external = Recipient::fromEmail('john@company.com', 'John Smith');
            $different = Recipient::fromUser(200, 'john@company.com', 'John Smith');

            expect($employee->equals($external))->toBeFalse()
                ->and($employee->equals($different))->toBeFalse()
                ->and($external->equals($different))->toBeFalse();
        });
    });

    describe('Type Safety and Error Handling', function (): void {
        it('ensures all properties maintain correct types', function (): void {
            $recipient = Recipient::fromUser(123, 'test@example.com', 'Test User');

            expect($recipient->email)->toBeInstanceOf(EmailAddress::class)
                ->and($recipient->name)->toBeString()
                ->and($recipient->userId)->toBeInt();
        });

        it('ensures null properties are properly null', function (): void {
            $recipient = Recipient::fromEmail('test@example.com');

            expect($recipient->name)->toBeNull()
                ->and($recipient->userId)->toBeNull()
                ->and($recipient->email)->not->toBeNull();
        });

        it('validates constructor parameter types', function (): void {
            $email = new EmailAddress('test@example.com');

            // These would fail at PHP type level, but we ensure the contracts are correct
            expect(fn () => new Recipient($email, '', null))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => new Recipient($email, 'Valid', 0))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
