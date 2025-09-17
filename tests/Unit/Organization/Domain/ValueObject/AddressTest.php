<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObject\Address;

describe('Address', function () {
    describe('construction', function () {
        it('creates valid address with all required fields', function () {
            $address = new Address(
                street: '123 Main St',
                city: 'New York',
                state: 'NY',
                postalCode: '10001',
                country: 'USA'
            );

            expect($address->street)->toBe('123 Main St')
                ->and($address->city)->toBe('New York')
                ->and($address->state)->toBe('NY')
                ->and($address->postalCode)->toBe('10001')
                ->and($address->country)->toBe('USA')
                ->and($address->unit)->toBeNull()
                ->and($address)->toBeInstanceOf(Address::class)
                ->and($address)->toBeInstanceOf(Stringable::class);
        });

        it('creates valid address with unit', function () {
            $address = new Address(
                street: '456 Oak Ave',
                city: 'Los Angeles',
                state: 'CA',
                postalCode: '90210',
                country: 'USA',
                unit: 'Apt 5B'
            );

            expect($address->street)->toBe('456 Oak Ave')
                ->and($address->city)->toBe('Los Angeles')
                ->and($address->state)->toBe('CA')
                ->and($address->postalCode)->toBe('90210')
                ->and($address->country)->toBe('USA')
                ->and($address->unit)->toBe('Apt 5B');
        });

        it('creates address with various international formats', function () {
            $ukAddress = new Address(
                street: '10 Downing Street',
                city: 'London',
                state: 'England',
                postalCode: 'SW1A 2AA',
                country: 'United Kingdom'
            );

            expect($ukAddress->street)->toBe('10 Downing Street')
                ->and($ukAddress->city)->toBe('London')
                ->and($ukAddress->state)->toBe('England')
                ->and($ukAddress->postalCode)->toBe('SW1A 2AA')
                ->and($ukAddress->country)->toBe('United Kingdom');
        });

        it('throws exception for empty street', function () {
            expect(fn () => new Address('', 'City', 'State', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'Street address cannot be empty');

            expect(fn () => new Address('   ', 'City', 'State', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'Street address cannot be empty');
        });

        it('throws exception for empty city', function () {
            expect(fn () => new Address('123 Street', '', 'State', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'City cannot be empty');

            expect(fn () => new Address('123 Street', '   ', 'State', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'City cannot be empty');
        });

        it('throws exception for empty state', function () {
            expect(fn () => new Address('123 Street', 'City', '', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'State cannot be empty');

            expect(fn () => new Address('123 Street', 'City', '   ', '12345', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'State cannot be empty');
        });

        it('throws exception for empty postal code', function () {
            expect(fn () => new Address('123 Street', 'City', 'State', '', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'Postal code cannot be empty');

            expect(fn () => new Address('123 Street', 'City', 'State', '   ', 'Country'))
                ->toThrow(InvalidArgumentException::class, 'Postal code cannot be empty');
        });

        it('throws exception for empty country', function () {
            expect(fn () => new Address('123 Street', 'City', 'State', '12345', ''))
                ->toThrow(InvalidArgumentException::class, 'Country cannot be empty');

            expect(fn () => new Address('123 Street', 'City', 'State', '12345', '   '))
                ->toThrow(InvalidArgumentException::class, 'Country cannot be empty');
        });

        it('throws exception for empty unit string', function () {
            expect(fn () => new Address('123 Street', 'City', 'State', '12345', 'Country', ''))
                ->toThrow(InvalidArgumentException::class, 'Unit cannot be empty string');

            expect(fn () => new Address('123 Street', 'City', 'State', '12345', 'Country', '   '))
                ->toThrow(InvalidArgumentException::class, 'Unit cannot be empty string');
        });

        it('accepts null unit', function () {
            $address = new Address('123 Street', 'City', 'State', '12345', 'Country', null);

            expect($address->unit)->toBeNull();
        });
    });

    describe('create factory method', function () {
        it('creates address without unit', function () {
            $address = Address::create(
                street: '789 Pine St',
                city: 'Chicago',
                state: 'IL',
                postalCode: '60601',
                country: 'USA'
            );

            expect($address)->toBeInstanceOf(Address::class)
                ->and($address->street)->toBe('789 Pine St')
                ->and($address->city)->toBe('Chicago')
                ->and($address->state)->toBe('IL')
                ->and($address->postalCode)->toBe('60601')
                ->and($address->country)->toBe('USA')
                ->and($address->unit)->toBeNull();
        });

        it('creates address with unit', function () {
            $address = Address::create(
                street: '321 Elm St',
                city: 'Miami',
                state: 'FL',
                postalCode: '33101',
                country: 'USA',
                unit: 'Suite 200'
            );

            expect($address->street)->toBe('321 Elm St')
                ->and($address->city)->toBe('Miami')
                ->and($address->state)->toBe('FL')
                ->and($address->postalCode)->toBe('33101')
                ->and($address->country)->toBe('USA')
                ->and($address->unit)->toBe('Suite 200');
        });

        it('creates address with complex international data', function () {
            $address = Address::create(
                street: 'Champs-Élysées',
                city: 'Paris',
                state: 'Île-de-France',
                postalCode: '75008',
                country: 'France',
                unit: '8ème étage'
            );

            expect($address->street)->toBe('Champs-Élysées')
                ->and($address->city)->toBe('Paris')
                ->and($address->state)->toBe('Île-de-France')
                ->and($address->postalCode)->toBe('75008')
                ->and($address->country)->toBe('France')
                ->and($address->unit)->toBe('8ème étage');
        });
    });

    describe('hasUnit method', function () {
        it('returns false when unit is null', function () {
            $address = new Address('123 St', 'City', 'State', '12345', 'Country');

            expect($address->hasUnit())->toBeFalse();
        });

        it('returns true when unit is provided', function () {
            $address = new Address('123 St', 'City', 'State', '12345', 'Country', 'Unit 1');

            expect($address->hasUnit())->toBeTrue();
        });

        it('returns true for various unit formats', function () {
            $addressApt = new Address('123 St', 'City', 'State', '12345', 'Country', 'Apt 5');
            $addressSuite = new Address('123 St', 'City', 'State', '12345', 'Country', 'Suite 100');
            $addressFloor = new Address('123 St', 'City', 'State', '12345', 'Country', '2nd Floor');

            expect($addressApt->hasUnit())->toBeTrue()
                ->and($addressSuite->hasUnit())->toBeTrue()
                ->and($addressFloor->hasUnit())->toBeTrue();
        });
    });

    describe('getFullStreetAddress method', function () {
        it('returns street when unit is null', function () {
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country');

            expect($address->getFullStreetAddress())->toBe('123 Main St');
        });

        it('returns unit and street when unit is provided', function () {
            $address = new Address('456 Oak Ave', 'City', 'State', '12345', 'Country', 'Apt 2B');

            expect($address->getFullStreetAddress())->toBe('Apt 2B 456 Oak Ave');
        });

        it('formats various unit types correctly', function () {
            $addressSuite = new Address('789 Pine St', 'City', 'State', '12345', 'Country', 'Suite 300');
            $addressFloor = new Address('321 Elm St', 'City', 'State', '12345', 'Country', '5th Floor');
            $addressUnit = new Address('654 Maple Ave', 'City', 'State', '12345', 'Country', 'Unit B');

            expect($addressSuite->getFullStreetAddress())->toBe('Suite 300 789 Pine St')
                ->and($addressFloor->getFullStreetAddress())->toBe('5th Floor 321 Elm St')
                ->and($addressUnit->getFullStreetAddress())->toBe('Unit B 654 Maple Ave');
        });

        it('handles long addresses correctly', function () {
            $address = new Address(
                'The Very Long Street Name With Many Words Avenue',
                'City',
                'State',
                '12345',
                'Country',
                'Penthouse Suite 9876'
            );

            expect($address->getFullStreetAddress())
                ->toBe('Penthouse Suite 9876 The Very Long Street Name With Many Words Avenue');
        });
    });

    describe('getFormattedAddress method', function () {
        it('formats address without unit correctly', function () {
            $address = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $expected = "123 Main St\nNew York, NY 10001\nUSA";

            expect($address->getFormattedAddress())->toBe($expected);
        });

        it('formats address with unit correctly', function () {
            $address = new Address('456 Oak Ave', 'Los Angeles', 'CA', '90210', 'USA', 'Apt 5B');
            $expected = "Apt 5B 456 Oak Ave\nLos Angeles, CA 90210\nUSA";

            expect($address->getFormattedAddress())->toBe($expected);
        });

        it('formats international addresses correctly', function () {
            $address = new Address('10 Downing Street', 'London', 'England', 'SW1A 2AA', 'United Kingdom');
            $expected = "10 Downing Street\nLondon, England SW1A 2AA\nUnited Kingdom";

            expect($address->getFormattedAddress())->toBe($expected);
        });

        it('handles addresses with special characters', function () {
            $address = new Address(
                'Champs-Élysées 123',
                'Paris',
                'Île-de-France',
                '75008',
                'France',
                '8ème étage'
            );
            $expected = "8ème étage Champs-Élysées 123\nParis, Île-de-France 75008\nFrance";

            expect($address->getFormattedAddress())->toBe($expected);
        });

        it('maintains consistent formatting structure', function () {
            $addresses = [
                new Address('Street 1', 'City 1', 'State 1', 'Code 1', 'Country 1'),
                new Address('Street 2', 'City 2', 'State 2', 'Code 2', 'Country 2', 'Unit 2'),
                new Address('Street 3', 'City 3', 'State 3', 'Code 3', 'Country 3'),
            ];

            foreach ($addresses as $address) {
                $formatted = $address->getFormattedAddress();
                $lines = explode("\n", $formatted);

                expect($lines)->toHaveCount(3)
                    ->and($lines[0])->toBeString()
                    ->and($lines[1])->toContain(',')
                    ->and($lines[2])->toBeString();
            }
        });
    });

    describe('equals method', function () {
        it('returns true for identical addresses without unit', function () {
            $address1 = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $address2 = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');

            expect($address1->equals($address2))->toBeTrue()
                ->and($address2->equals($address1))->toBeTrue();
        });

        it('returns true for identical addresses with unit', function () {
            $address1 = new Address('456 Oak Ave', 'LA', 'CA', '90210', 'USA', 'Apt 5B');
            $address2 = new Address('456 Oak Ave', 'LA', 'CA', '90210', 'USA', 'Apt 5B');

            expect($address1->equals($address2))->toBeTrue();
        });

        it('returns true for same instance', function () {
            $address = new Address('789 Pine St', 'Chicago', 'IL', '60601', 'USA');

            expect($address->equals($address))->toBeTrue();
        });

        it('returns false for different streets', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $address2 = new Address('456 Oak Ave', 'City', 'State', '12345', 'Country');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false for different cities', function () {
            $address1 = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $address2 = new Address('123 Main St', 'Boston', 'NY', '10001', 'USA');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false for different states', function () {
            $address1 = new Address('123 Main St', 'City', 'NY', '12345', 'USA');
            $address2 = new Address('123 Main St', 'City', 'CA', '12345', 'USA');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false for different postal codes', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'USA');
            $address2 = new Address('123 Main St', 'City', 'State', '67890', 'USA');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false for different countries', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'USA');
            $address2 = new Address('123 Main St', 'City', 'State', '12345', 'Canada');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false for different units', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'USA', 'Apt 1');
            $address2 = new Address('123 Main St', 'City', 'State', '12345', 'USA', 'Apt 2');

            expect($address1->equals($address2))->toBeFalse();
        });

        it('returns false when one has unit and other does not', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'USA');
            $address2 = new Address('123 Main St', 'City', 'State', '12345', 'USA', 'Apt 1');

            expect($address1->equals($address2))->toBeFalse()
                ->and($address2->equals($address1))->toBeFalse();
        });

        it('returns true when both have null units', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'USA', null);
            $address2 = new Address('123 Main St', 'City', 'State', '12345', 'USA', null);

            expect($address1->equals($address2))->toBeTrue();
        });
    });

    describe('toString method', function () {
        it('converts to string without unit', function () {
            $address = new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
            $expected = '123 Main St, New York, NY 10001, USA';

            expect((string) $address)->toBe($expected)
                ->and($address->__toString())->toBe($expected);
        });

        it('converts to string with unit', function () {
            $address = new Address('456 Oak Ave', 'Los Angeles', 'CA', '90210', 'USA', 'Apt 5B');
            $expected = 'Apt 5B 456 Oak Ave, Los Angeles, CA 90210, USA';

            expect((string) $address)->toBe($expected);
        });

        it('replaces newlines with commas', function () {
            $address = new Address('789 Pine St', 'Chicago', 'IL', '60601', 'USA');
            $formatted = $address->getFormattedAddress();
            $toString = (string) $address;

            expect($formatted)->toContain("\n")
                ->and($toString)->not->toContain("\n")
                ->and($toString)->toContain(', ');
        });

        it('maintains consistency with formatted address', function () {
            $address = new Address('321 Elm St', 'Miami', 'FL', '33101', 'USA', 'Suite 200');
            $formatted = $address->getFormattedAddress();
            $toString = (string) $address;
            $expectedString = str_replace("\n", ', ', $formatted);

            expect($toString)->toBe($expectedString);
        });

        it('handles international addresses correctly', function () {
            $address = new Address('10 Downing Street', 'London', 'England', 'SW1A 2AA', 'United Kingdom');
            $expected = '10 Downing Street, London, England SW1A 2AA, United Kingdom';

            expect((string) $address)->toBe($expected);
        });
    });

    describe('edge cases and validation', function () {
        it('handles addresses with leading/trailing spaces', function () {
            $address = new Address(
                '  123 Main St  ',
                '  New York  ',
                '  NY  ',
                '  10001  ',
                '  USA  ',
                '  Apt 5B  '
            );

            expect($address->street)->toBe('  123 Main St  ')
                ->and($address->city)->toBe('  New York  ')
                ->and($address->state)->toBe('  NY  ')
                ->and($address->postalCode)->toBe('  10001  ')
                ->and($address->country)->toBe('  USA  ')
                ->and($address->unit)->toBe('  Apt 5B  ');
        });

        it('handles very long address components', function () {
            $longStreet = str_repeat('Very Long Street Name ', 10);
            $longCity = str_repeat('Very Long City Name ', 5);
            $longState = str_repeat('Very Long State Name ', 3);
            $longCountry = str_repeat('Very Long Country Name ', 2);
            $longUnit = str_repeat('Very Long Unit Name ', 4);

            $address = new Address($longStreet, $longCity, $longState, '12345', $longCountry, $longUnit);

            expect(strlen($address->street))->toBeGreaterThan(100)
                ->and(strlen($address->city))->toBeGreaterThan(50)
                ->and(strlen($address->state))->toBeGreaterThan(30)
                ->and(strlen($address->country))->toBeGreaterThan(20)
                ->and(strlen($address->unit))->toBeGreaterThan(40);
        });

        it('handles special characters in all fields', function () {
            $address = new Address(
                'Rüdesheimer Straße 123',
                'Düsseldorf',
                'Nordrhein-Westfalen',
                '40212',
                'Deutschland',
                'Büro 4a'
            );

            expect($address->street)->toBe('Rüdesheimer Straße 123')
                ->and($address->city)->toBe('Düsseldorf')
                ->and($address->state)->toBe('Nordrhein-Westfalen')
                ->and($address->country)->toBe('Deutschland')
                ->and($address->unit)->toBe('Büro 4a');
        });

        it('handles numeric postal codes correctly', function () {
            $address1 = new Address('123 St', 'City', 'State', '12345', 'Country');
            $address2 = new Address('123 St', 'City', 'State', '00001', 'Country');
            $address3 = new Address('123 St', 'City', 'State', '99999', 'Country');

            expect($address1->postalCode)->toBe('12345')
                ->and($address2->postalCode)->toBe('00001')
                ->and($address3->postalCode)->toBe('99999');
        });

        it('handles alphanumeric postal codes', function () {
            $ukAddress = new Address('123 St', 'London', 'England', 'SW1A 2AA', 'UK');
            $canadaAddress = new Address('123 St', 'Toronto', 'ON', 'M5V 3A8', 'Canada');

            expect($ukAddress->postalCode)->toBe('SW1A 2AA')
                ->and($canadaAddress->postalCode)->toBe('M5V 3A8');
        });
    });

    describe('immutability and value object behavior', function () {
        it('maintains immutable properties', function () {
            $address = new Address('123 Main St', 'City', 'State', '12345', 'Country', 'Unit 1');

            expect($address->street)->toBe('123 Main St')
                ->and($address->city)->toBe('City')
                ->and($address->state)->toBe('State')
                ->and($address->postalCode)->toBe('12345')
                ->and($address->country)->toBe('Country')
                ->and($address->unit)->toBe('Unit 1');

            // Properties should remain unchanged
            expect($address->street)->toBe('123 Main St');
        });

        it('creates separate instances correctly', function () {
            $address1 = new Address('123 Main St', 'City1', 'State1', '12345', 'Country1');
            $address2 = new Address('456 Oak Ave', 'City2', 'State2', '67890', 'Country2');

            expect($address1->street)->not->toBe($address2->street)
                ->and($address1->city)->not->toBe($address2->city)
                ->and($address1->state)->not->toBe($address2->state)
                ->and($address1->postalCode)->not->toBe($address2->postalCode)
                ->and($address1->country)->not->toBe($address2->country);
        });

        it('behaves as value object in collections', function () {
            $address1 = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $address2 = new Address('123 Main St', 'City', 'State', '12345', 'Country');
            $address3 = new Address('456 Oak Ave', 'City', 'State', '12345', 'Country');

            $addresses = [$address1, $address2, $address3];

            expect($addresses)->toHaveCount(3)
                ->and($addresses[0]->equals($addresses[1]))->toBeTrue()
                ->and($addresses[0]->equals($addresses[2]))->toBeFalse();
        });
    });
});
