<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObject\Address;
use Modules\Organization\Domain\ValueObject\ContactInfo;
use Modules\User\Domain\ValueObject\EmailAddress;

describe('ContactInfo', function () {
    describe('construction', function () {
        it('creates contact info with only email', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo = new ContactInfo($email);

            expect($contactInfo->email)->toBe($email)
                ->and($contactInfo->phone)->toBeNull()
                ->and($contactInfo->address)->toBeNull()
                ->and($contactInfo->website)->toBeNull()
                ->and($contactInfo->contactPerson)->toBeNull()
                ->and($contactInfo)->toBeInstanceOf(ContactInfo::class)
                ->and($contactInfo)->toBeInstanceOf(Stringable::class);
        });

        it('creates contact info with all fields', function () {
            $email = new EmailAddress('contact@acme.com');
            $address = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $contactInfo = new ContactInfo(
                email: $email,
                phone: '+1-555-123-4567',
                address: $address,
                website: 'https://www.acme.com',
                contactPerson: 'John Doe'
            );

            expect($contactInfo->email)->toBe($email)
                ->and($contactInfo->phone)->toBe('+1-555-123-4567')
                ->and($contactInfo->address)->toBe($address)
                ->and($contactInfo->website)->toBe('https://www.acme.com')
                ->and($contactInfo->contactPerson)->toBe('John Doe');
        });

        it('creates contact info with partial fields', function () {
            $email = new EmailAddress('info@company.org');
            $contactInfo = new ContactInfo(
                email: $email,
                phone: '555-1234',
                website: 'https://company.org'
            );

            expect($contactInfo->email)->toBe($email)
                ->and($contactInfo->phone)->toBe('555-1234')
                ->and($contactInfo->address)->toBeNull()
                ->and($contactInfo->website)->toBe('https://company.org')
                ->and($contactInfo->contactPerson)->toBeNull();
        });

        it('validates phone number format', function () {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new ContactInfo($email, 'invalid-phone'))
                ->toThrow(InvalidArgumentException::class, 'Invalid phone number format');

            expect(fn () => new ContactInfo($email, '123'))
                ->toThrow(InvalidArgumentException::class, 'Invalid phone number format');

            expect(fn () => new ContactInfo($email, '123456789012345678901'))
                ->toThrow(InvalidArgumentException::class, 'Invalid phone number format');
        });

        it('accepts various valid phone formats', function () {
            $email = new EmailAddress('test@example.com');

            $validPhones = [
                '+1-555-123-4567',
                '555-123-4567',
                '(555) 123-4567',
                '+44 20 7946 0958',
                '1234567',
                '12345678901234567890',
            ];

            foreach ($validPhones as $phone) {
                $contactInfo = new ContactInfo($email, $phone);
                expect($contactInfo->phone)->toBe($phone);
            }
        });

        it('validates website URL format', function () {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new ContactInfo($email, null, null, 'invalid-url'))
                ->toThrow(InvalidArgumentException::class, 'Invalid website URL format');

            expect(fn () => new ContactInfo($email, null, null, 'not-a-url'))
                ->toThrow(InvalidArgumentException::class, 'Invalid website URL format');

            expect(fn () => new ContactInfo($email, null, null, 'ftp://example.com'))
                ->not->toThrow(InvalidArgumentException::class);
        });

        it('accepts various valid website formats', function () {
            $email = new EmailAddress('test@example.com');

            $validWebsites = [
                'https://www.example.com',
                'http://example.com',
                'https://subdomain.example.com',
                'https://example.com/path',
                'https://example.com:8080',
                'ftp://files.example.com',
            ];

            foreach ($validWebsites as $website) {
                $contactInfo = new ContactInfo($email, null, null, $website);
                expect($contactInfo->website)->toBe($website);
            }
        });

        it('validates contact person name', function () {
            $email = new EmailAddress('test@example.com');

            expect(fn () => new ContactInfo($email, null, null, null, ''))
                ->toThrow(InvalidArgumentException::class, 'Contact person name cannot be empty');

            expect(fn () => new ContactInfo($email, null, null, null, '   '))
                ->toThrow(InvalidArgumentException::class, 'Contact person name cannot be empty');
        });

        it('accepts various contact person names', function () {
            $email = new EmailAddress('test@example.com');

            $validNames = [
                'John Doe',
                'Dr. Jane Smith',
                'María García',
                'Jean-Luc Picard',
                'O\'Connor',
                '李小明',
                'Müller',
            ];

            foreach ($validNames as $name) {
                $contactInfo = new ContactInfo($email, null, null, null, $name);
                expect($contactInfo->contactPerson)->toBe($name);
            }
        });
    });

    describe('minimal factory method', function () {
        it('creates minimal contact info with only email', function () {
            $contactInfo = ContactInfo::minimal('admin@example.com');

            expect($contactInfo->email)->toBeInstanceOf(EmailAddress::class)
                ->and($contactInfo->email->value)->toBe('admin@example.com')
                ->and($contactInfo->phone)->toBeNull()
                ->and($contactInfo->address)->toBeNull()
                ->and($contactInfo->website)->toBeNull()
                ->and($contactInfo->contactPerson)->toBeNull();
        });

        it('validates email in minimal factory', function () {
            expect(fn () => ContactInfo::minimal('invalid-email'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => ContactInfo::minimal(''))
                ->toThrow(InvalidArgumentException::class);
        });

        it('creates minimal contact info with various email formats', function () {
            $validEmails = [
                'test@example.com',
                'user.name@domain.org',
                'admin+newsletter@company.co.uk',
                'info@sub.domain.com',
            ];

            foreach ($validEmails as $email) {
                $contactInfo = ContactInfo::minimal($email);
                expect($contactInfo->email->value)->toBe($email);
            }
        });
    });

    describe('complete factory method', function () {
        it('creates complete contact info with all parameters', function () {
            $address = new Address('456 Oak St', 'LA', 'CA', '90210', 'USA');
            $contactInfo = ContactInfo::complete(
                email: 'contact@company.com',
                phone: '555-9876',
                address: $address,
                website: 'https://company.com',
                contactPerson: 'Jane Smith'
            );

            expect($contactInfo->email->value)->toBe('contact@company.com')
                ->and($contactInfo->phone)->toBe('555-9876')
                ->and($contactInfo->address)->toBe($address)
                ->and($contactInfo->website)->toBe('https://company.com')
                ->and($contactInfo->contactPerson)->toBe('Jane Smith');
        });

        it('creates complete contact info with partial parameters', function () {
            $contactInfo = ContactInfo::complete(
                email: 'info@org.net',
                phone: '123-456-7890'
            );

            expect($contactInfo->email->value)->toBe('info@org.net')
                ->and($contactInfo->phone)->toBe('123-456-7890')
                ->and($contactInfo->address)->toBeNull()
                ->and($contactInfo->website)->toBeNull()
                ->and($contactInfo->contactPerson)->toBeNull();
        });

        it('validates all parameters in complete factory', function () {
            expect(fn () => ContactInfo::complete('invalid-email'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => ContactInfo::complete('test@example.com', 'bad-phone'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => ContactInfo::complete('test@example.com', null, null, 'bad-url'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => ContactInfo::complete('test@example.com', null, null, null, ''))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('hasPhone method', function () {
        it('returns false when phone is null', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect($contactInfo->hasPhone())->toBeFalse();
        });

        it('returns true when phone is provided', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo = new ContactInfo($email, '555-1234');

            expect($contactInfo->hasPhone())->toBeTrue();
        });

        it('returns true for various phone formats', function () {
            $email = new EmailAddress('test@example.com');

            $phones = [
                '+1-555-123-4567',
                '(555) 123-4567',
                '555.123.4567',
                '+44 20 7946 0958',
            ];

            foreach ($phones as $phone) {
                $contactInfo = new ContactInfo($email, $phone);
                expect($contactInfo->hasPhone())->toBeTrue();
            }
        });
    });

    describe('hasAddress method', function () {
        it('returns false when address is null', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect($contactInfo->hasAddress())->toBeFalse();
        });

        it('returns true when address is provided', function () {
            $email = new EmailAddress('test@example.com');
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $contactInfo = new ContactInfo($email, null, $address);

            expect($contactInfo->hasAddress())->toBeTrue();
        });

        it('returns true for various address types', function () {
            $email = new EmailAddress('test@example.com');

            $addresses = [
                new Address('123 Main St', 'New York', 'NY', '10001', 'USA'),
                new Address('456 Oak Ave', 'LA', 'CA', '90210', 'USA', 'Apt 5'),
                new Address('789 Pine Rd', 'Chicago', 'IL', '60601', 'USA'),
            ];

            foreach ($addresses as $address) {
                $contactInfo = new ContactInfo($email, null, $address);
                expect($contactInfo->hasAddress())->toBeTrue();
            }
        });
    });

    describe('hasWebsite method', function () {
        it('returns false when website is null', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect($contactInfo->hasWebsite())->toBeFalse();
        });

        it('returns true when website is provided', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo = new ContactInfo($email, null, null, 'https://example.com');

            expect($contactInfo->hasWebsite())->toBeTrue();
        });

        it('returns true for various website formats', function () {
            $email = new EmailAddress('test@example.com');

            $websites = [
                'https://www.example.com',
                'http://example.org',
                'https://subdomain.example.net',
                'https://example.com/path/to/page',
            ];

            foreach ($websites as $website) {
                $contactInfo = new ContactInfo($email, null, null, $website);
                expect($contactInfo->hasWebsite())->toBeTrue();
            }
        });
    });

    describe('hasContactPerson method', function () {
        it('returns false when contact person is null', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect($contactInfo->hasContactPerson())->toBeFalse();
        });

        it('returns true when contact person is provided', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo = new ContactInfo($email, null, null, null, 'John Doe');

            expect($contactInfo->hasContactPerson())->toBeTrue();
        });

        it('returns true for various contact person names', function () {
            $email = new EmailAddress('test@example.com');

            $names = [
                'John Doe',
                'Dr. Jane Smith',
                'María García-López',
                'Jean-Pierre Dubois',
                '李小明',
            ];

            foreach ($names as $name) {
                $contactInfo = new ContactInfo($email, null, null, null, $name);
                expect($contactInfo->hasContactPerson())->toBeTrue();
            }
        });
    });

    describe('equals method', function () {
        it('returns true for identical contact info', function () {
            $email = new EmailAddress('test@example.com');
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');

            $contactInfo1 = new ContactInfo($email, '555-1234', $address, 'https://example.com', 'John Doe');
            $contactInfo2 = new ContactInfo($email, '555-1234', $address, 'https://example.com', 'John Doe');

            expect($contactInfo1->equals($contactInfo2))->toBeTrue();
        });

        it('returns true for same instance', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect($contactInfo->equals($contactInfo))->toBeTrue();
        });

        it('returns false for different emails', function () {
            $contactInfo1 = ContactInfo::minimal('test1@example.com');
            $contactInfo2 = ContactInfo::minimal('test2@example.com');

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });

        it('returns false for different phones', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo1 = new ContactInfo($email, '555-1234');
            $contactInfo2 = new ContactInfo($email, '555-5678');

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });

        it('returns false for different addresses', function () {
            $email = new EmailAddress('test@example.com');
            $address1 = new Address('123 Main St', 'City1', 'State', '12345', 'Country');
            $address2 = new Address('456 Oak St', 'City2', 'State', '67890', 'Country');

            $contactInfo1 = new ContactInfo($email, null, $address1);
            $contactInfo2 = new ContactInfo($email, null, $address2);

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });

        it('returns false for different websites', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo1 = new ContactInfo($email, null, null, 'https://example1.com');
            $contactInfo2 = new ContactInfo($email, null, null, 'https://example2.com');

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });

        it('returns false for different contact persons', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo1 = new ContactInfo($email, null, null, null, 'John Doe');
            $contactInfo2 = new ContactInfo($email, null, null, null, 'Jane Smith');

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });

        it('returns true when both have null optional fields', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo1 = new ContactInfo($email);
            $contactInfo2 = new ContactInfo($email);

            expect($contactInfo1->equals($contactInfo2))->toBeTrue();
        });

        it('returns false when one has optional field and other does not', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo1 = new ContactInfo($email);
            $contactInfo2 = new ContactInfo($email, '555-1234');

            expect($contactInfo1->equals($contactInfo2))->toBeFalse();
        });
    });

    describe('with methods for immutable updates', function () {
        it('creates new instance with updated phone', function () {
            $original = ContactInfo::minimal('test@example.com');
            $updated = $original->withPhone('555-1234');

            expect($original->phone)->toBeNull()
                ->and($updated->phone)->toBe('555-1234')
                ->and($original)->not->toBe($updated)
                ->and($updated->email)->toBe($original->email)
                ->and($updated->address)->toBe($original->address)
                ->and($updated->website)->toBe($original->website)
                ->and($updated->contactPerson)->toBe($original->contactPerson);
        });

        it('creates new instance with updated address', function () {
            $original = ContactInfo::minimal('test@example.com');
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $updated = $original->withAddress($address);

            expect($original->address)->toBeNull()
                ->and($updated->address)->toBe($address)
                ->and($original)->not->toBe($updated)
                ->and($updated->email)->toBe($original->email);
        });

        it('creates new instance with updated website', function () {
            $original = ContactInfo::minimal('test@example.com');
            $updated = $original->withWebsite('https://example.com');

            expect($original->website)->toBeNull()
                ->and($updated->website)->toBe('https://example.com')
                ->and($original)->not->toBe($updated);
        });

        it('creates new instance with updated contact person', function () {
            $original = ContactInfo::minimal('test@example.com');
            $updated = $original->withContactPerson('John Doe');

            expect($original->contactPerson)->toBeNull()
                ->and($updated->contactPerson)->toBe('John Doe')
                ->and($original)->not->toBe($updated);
        });

        it('validates phone in withPhone method', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect(fn () => $contactInfo->withPhone('invalid-phone'))
                ->toThrow(InvalidArgumentException::class, 'Invalid phone number format');
        });

        it('validates website in withWebsite method', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect(fn () => $contactInfo->withWebsite('invalid-url'))
                ->toThrow(InvalidArgumentException::class, 'Invalid website URL format');
        });

        it('validates contact person in withContactPerson method', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect(fn () => $contactInfo->withContactPerson(''))
                ->toThrow(InvalidArgumentException::class, 'Contact person name cannot be empty');
        });

        it('chains with methods correctly', function () {
            $original = ContactInfo::minimal('test@example.com');
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');

            $updated = $original
                ->withPhone('555-1234')
                ->withAddress($address)
                ->withWebsite('https://example.com')
                ->withContactPerson('John Doe');

            expect($updated->phone)->toBe('555-1234')
                ->and($updated->address)->toBe($address)
                ->and($updated->website)->toBe('https://example.com')
                ->and($updated->contactPerson)->toBe('John Doe')
                ->and($updated->email)->toBe($original->email);
        });
    });

    describe('toString method', function () {
        it('converts to string with only email', function () {
            $contactInfo = ContactInfo::minimal('test@example.com');

            expect((string) $contactInfo)->toBe('test@example.com')
                ->and($contactInfo->__toString())->toBe('test@example.com');
        });

        it('converts to string with email and phone', function () {
            $email = new EmailAddress('test@example.com');
            $contactInfo = new ContactInfo($email, '555-1234');

            expect((string) $contactInfo)->toBe('test@example.com | 555-1234');
        });

        it('converts to string with email, phone, and address', function () {
            $email = new EmailAddress('test@example.com');
            $address = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $contactInfo = new ContactInfo($email, '555-1234', $address);

            $expected = 'test@example.com | 555-1234 | 123 Main St, New York, NY 10001, USA';
            expect((string) $contactInfo)->toBe($expected);
        });

        it('converts to string with all fields', function () {
            $email = new EmailAddress('contact@acme.com');
            $address = new Address('456 Oak Ave', 'LA', 'CA', '90210', 'USA');
            $contactInfo = new ContactInfo($email, '555-9876', $address, 'https://acme.com', 'Jane Doe');

            $expected = 'contact@acme.com | 555-9876 | 456 Oak Ave, LA, CA 90210, USA';
            expect((string) $contactInfo)->toBe($expected);
        });

        it('omits null fields in string representation', function () {
            $email = new EmailAddress('info@company.org');
            $contactInfo = new ContactInfo($email, null, null, 'https://company.org', 'John Smith');

            expect((string) $contactInfo)->toBe('info@company.org');
        });

        it('handles various combinations correctly', function () {
            $email = new EmailAddress('test@example.com');

            // Email only
            $contactInfo1 = new ContactInfo($email);
            expect((string) $contactInfo1)->toBe('test@example.com');

            // Email and website only
            $contactInfo2 = new ContactInfo($email, null, null, 'https://example.com');
            expect((string) $contactInfo2)->toBe('test@example.com');

            // Email and contact person only
            $contactInfo3 = new ContactInfo($email, null, null, null, 'John Doe');
            expect((string) $contactInfo3)->toBe('test@example.com');
        });
    });

    describe('validation edge cases', function () {
        it('handles international phone numbers', function () {
            $email = new EmailAddress('test@example.com');

            $internationalPhones = [
                '+33 1 42 86 83 26',      // France
                '+81-3-1234-5678',        // Japan
                '+86 138 0013 8000',      // China
                '+55 11 99999-9999',      // Brazil
                '+49 30 12345678',        // Germany
            ];

            foreach ($internationalPhones as $phone) {
                $contactInfo = new ContactInfo($email, $phone);
                expect($contactInfo->phone)->toBe($phone);
            }
        });

        it('handles various website protocols', function () {
            $email = new EmailAddress('test@example.com');

            $websites = [
                'https://example.com',
                'http://example.com',
                'ftp://files.example.com',
                'ftps://secure.example.com',
            ];

            foreach ($websites as $website) {
                $contactInfo = new ContactInfo($email, null, null, $website);
                expect($contactInfo->website)->toBe($website);
            }
        });

        it('handles long contact person names', function () {
            $email = new EmailAddress('test@example.com');
            $longName = 'Dr. María José García-López de Mendoza y Fernández-Castro';

            $contactInfo = new ContactInfo($email, null, null, null, $longName);

            expect($contactInfo->contactPerson)->toBe($longName);
        });

        it('handles special characters in contact person names', function () {
            $email = new EmailAddress('test@example.com');

            $specialNames = [
                "O'Connor",
                'Smith-Jones',
                'José María',
                'François',
                'Müller',
                'Øström',
                '李小明',
                'محمد الأحمد',
            ];

            foreach ($specialNames as $name) {
                $contactInfo = new ContactInfo($email, null, null, null, $name);
                expect($contactInfo->contactPerson)->toBe($name);
            }
        });
    });

    describe('immutability and value object behavior', function () {
        it('maintains immutable properties', function () {
            $email = new EmailAddress('test@example.com');
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $contactInfo = new ContactInfo($email, '555-1234', $address, 'https://example.com', 'John Doe');

            // Properties should remain unchanged
            expect($contactInfo->email)->toBe($email)
                ->and($contactInfo->phone)->toBe('555-1234')
                ->and($contactInfo->address)->toBe($address)
                ->and($contactInfo->website)->toBe('https://example.com')
                ->and($contactInfo->contactPerson)->toBe('John Doe');
        });

        it('creates separate instances correctly', function () {
            $email1 = new EmailAddress('test1@example.com');
            $email2 = new EmailAddress('test2@example.com');
            $contactInfo1 = new ContactInfo($email1, '555-1111');
            $contactInfo2 = new ContactInfo($email2, '555-2222');

            expect($contactInfo1->email)->not->toBe($contactInfo2->email)
                ->and($contactInfo1->phone)->not->toBe($contactInfo2->phone);
        });

        it('behaves correctly in collections', function () {
            $contactInfo1 = ContactInfo::minimal('test1@example.com');
            $contactInfo2 = ContactInfo::minimal('test2@example.com');
            $contactInfo3 = ContactInfo::minimal('test1@example.com');

            $contacts = [$contactInfo1, $contactInfo2, $contactInfo3];

            expect($contacts)->toHaveCount(3)
                ->and($contactInfo1->equals($contactInfo3))->toBeTrue()
                ->and($contactInfo1->equals($contactInfo2))->toBeFalse();
        });
    });
});
