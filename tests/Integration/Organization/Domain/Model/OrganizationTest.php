<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;

describe('Organization', function (): void {
    beforeEach(function (): void {
        Carbon::setTestNow('2025-01-15 12:00:00');
    });

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    describe('basic properties and getters', function (): void {
        it('provides access to basic organization properties', function (): void {
            $org = Organization::factory()->make();
            $org->id = 1;
            $org->name = ['en' => 'ACME Corp', 'fr' => 'ACME Société'];
            $org->email = 'contact@acme.com';
            $org->website = 'https://acme.com';
            $org->phone = '+1-555-123-4567';
            $org->is_active = true;
            $org->is_verified = true;
            $org->category = 'technology';
            $org->type = 'corporation';
            $org->registration_number = 'REG123456';
            $org->tax_id = 'TAX789012';
            $org->slug = 'acme-corp';
            $org->verification_date = Carbon::parse('2025-01-10');
            $org->founded_date = Carbon::parse('2020-01-01');

            expect($org->getId())->toBe(1)
                ->and($org->getName())->toBe('ACME Corp')
                ->and($org->getEmail())->toBe('contact@acme.com')
                ->and($org->getWebsite())->toBe('https://acme.com')
                ->and($org->getPhone())->toBe('+1-555-123-4567')
                ->and($org->getIsActive())->toBeTrue()
                ->and($org->getIsVerified())->toBeTrue()
                ->and($org->getCategory())->toBe('technology')
                ->and($org->getType())->toBe('corporation')
                ->and($org->getRegistrationNumber())->toBe('REG123456')
                ->and($org->getTaxId())->toBe('TAX789012')
                ->and($org->getSlug())->toBe('acme-corp')
                ->and($org->getVerificationDate())->toBeInstanceOf(Carbon::class)
                ->and($org->getFoundedDate())->toBeInstanceOf(Carbon::class);
        });

        it('handles null values correctly', function (): void {
            $org = Organization::factory()->withNullValues()->make();

            expect($org->getEmail())->toBeNull()
                ->and($org->getWebsite())->toBeNull()
                ->and($org->getPhone())->toBeNull()
                ->and($org->getCategory())->toBeNull()
                ->and($org->getType())->toBeNull()
                ->and($org->getRegistrationNumber())->toBeNull()
                ->and($org->getTaxId())->toBeNull()
                ->and($org->getSlug())->toBeNull()
                ->and($org->getVerificationDate())->toBeNull()
                ->and($org->getFoundedDate())->toBeNull()
                ->and($org->getLogoUrl())->toBeNull();
        });

        it('handles boolean properties correctly', function (): void {
            $org = Organization::factory()->make();
            $org->is_active = false;
            $org->is_verified = false;

            expect($org->getIsActive())->toBeFalse()
                ->and($org->getIsVerified())->toBeFalse();

            $org->is_active = 1;
            $org->is_verified = 1;

            expect($org->getIsActive())->toBeTrue()
                ->and($org->getIsVerified())->toBeTrue();

            $org->is_active = 0;
            $org->is_verified = 0;

            expect($org->getIsActive())->toBeFalse()
                ->and($org->getIsVerified())->toBeFalse();
        });

        it('provides default name for unnamed organization', function (): void {
            // Test with empty array instead of null to respect NOT NULL constraint
            $org = Organization::factory()->create(['name' => []]);
            expect($org->getName())->toBe('Unnamed Organization');

            // Test with translations that don't include current locale (en)
            $org->update(['name' => ['fr' => 'Société', 'de' => 'Unternehmen']]);
            expect($org->fresh()->getName())->toBe('Unnamed Organization'); // Fallback since no 'en' translation

            // Test with English translation
            $org->update(['name' => ['en' => 'Test Organization']]);
            expect($org->fresh()->getName())->toBe('Test Organization');

            // Test getName() on empty name array
            $org->update(['name' => []]);
            expect($org->fresh()->getName())->toBe('Unnamed Organization');
        });

        it('handles translatable fields correctly', function (): void {
            $org = Organization::factory()->make();
            $org->name = ['en' => 'English Name', 'fr' => 'Nom Français'];
            $org->description = ['en' => 'English Description', 'fr' => 'Description Française'];
            $org->mission = ['en' => 'English Mission', 'fr' => 'Mission Française'];

            expect($org->getDescription())->toBeString()
                ->and($org->getMission())->toBeString();
        });
    });

    describe('business logic methods', function (): void {
        it('determines if organization can create campaigns', function (): void {
            $org = Organization::factory()->make();

            // Inactive and unverified
            $org->is_active = false;
            $org->is_verified = false;
            expect($org->canCreateCampaigns())->toBeFalse();

            // Active but unverified
            $org->is_active = true;
            $org->is_verified = false;
            expect($org->canCreateCampaigns())->toBeFalse();

            // Inactive but verified
            $org->is_active = false;
            $org->is_verified = true;
            expect($org->canCreateCampaigns())->toBeFalse();

            // Active and verified
            $org->is_active = true;
            $org->is_verified = true;
            expect($org->canCreateCampaigns())->toBeTrue();
        });

        it('checks if organization is active', function (): void {
            $org = Organization::factory()->make();

            $org->is_active = true;
            expect($org->isActive())->toBeTrue();

            $org->is_active = false;
            expect($org->isActive())->toBeFalse();

            $org->is_active = 1;
            expect($org->isActive())->toBeTrue();

            $org->is_active = 0;
            expect($org->isActive())->toBeFalse();

            $org->is_active = null;
            expect($org->isActive())->toBeFalse();
        });

        it('checks if provisioning has failed', function (): void {
            $org = Organization::factory()->make();

            $org->provisioning_status = 'failed';
            expect($org->hasFailed())->toBeTrue();

            $org->provisioning_status = 'active';
            expect($org->hasFailed())->toBeFalse();

            $org->provisioning_status = 'provisioning';
            expect($org->hasFailed())->toBeFalse();

            $org->provisioning_status = null;
            expect($org->hasFailed())->toBeFalse();
        });

        it('manages admin data in tenant data', function (): void {
            $org = Organization::factory()->create();

            // Initially no admin data
            expect($org->getAdminData())->toBeNull();

            // Set admin data
            $adminData = [
                'user_id' => 123,
                'email' => 'admin@acme.com',
                'name' => 'Admin User',
            ];

            $org->setAdminData($adminData);
            expect($org->getAdminData())->toBe($adminData)
                ->and($org->tenant_data['admin'])->toBe($adminData);

            // Update admin data
            $newAdminData = [
                'user_id' => 456,
                'email' => 'newadmin@acme.com',
                'name' => 'New Admin',
            ];

            $org->setAdminData($newAdminData);
            expect($org->getAdminData())->toBe($newAdminData);
        });

        it('preserves other tenant data when setting admin data', function (): void {
            $org = Organization::factory()->create([
                'tenant_data' => [
                    'settings' => ['theme' => 'dark'],
                    'features' => ['advanced' => true],
                ],
            ]);

            $adminData = ['user_id' => 123];
            $org->setAdminData($adminData);

            expect($org->tenant_data['admin'])->toBe($adminData)
                ->and($org->tenant_data['settings'])->toBe(['theme' => 'dark'])
                ->and($org->tenant_data['features'])->toBe(['advanced' => true]);
        });
    });

    describe('organization status management', function (): void {
        it('activates organization correctly', function (): void {
            $org = Organization::factory()->make();
            $org->is_active = false;

            $org->activate();

            expect($org->is_active)->toBeTrue();
        });

        it('throws exception when activating already active organization', function (): void {
            $org = Organization::factory()->make();
            $org->id = 1;
            $org->is_active = true;

            expect(fn () => $org->activate())
                ->toThrow(OrganizationException::class);
        });

        it('deactivates organization correctly', function (): void {
            $org = Organization::factory()->make();
            $org->is_active = true;

            $org->deactivate();

            expect($org->is_active)->toBeFalse();
        });

        it('throws exception when deactivating already inactive organization', function (): void {
            $org = Organization::factory()->make();
            $org->id = 1;
            $org->is_active = false;

            expect(fn () => $org->deactivate())
                ->toThrow(OrganizationException::class);
        });

        it('gets correct status based on is_active and is_verified', function (): void {
            $org = Organization::factory()->make();

            // Inactive
            $org->is_active = false;
            $org->is_verified = false;
            expect($org->getStatus())->toBe('inactive');

            $org->is_active = false;
            $org->is_verified = true;
            expect($org->getStatus())->toBe('inactive');

            // Unverified
            $org->is_active = true;
            $org->is_verified = false;
            expect($org->getStatus())->toBe('unverified');

            // Active
            $org->is_active = true;
            $org->is_verified = true;
            expect($org->getStatus())->toBe('active');
        });

        it('provides correct status colors', function (): void {
            $org = Organization::factory()->make();

            $org->is_active = true;
            $org->is_verified = true;
            expect($org->getStatusColor())->toBe('success');

            $org->is_active = true;
            $org->is_verified = false;
            expect($org->getStatusColor())->toBe('warning');

            $org->is_active = false;
            expect($org->getStatusColor())->toBe('danger');
        });

        it('provides correct status labels', function (): void {
            $org = Organization::factory()->make();

            $org->is_active = true;
            $org->is_verified = true;
            expect($org->getStatusLabel())->toBe('Active');

            $org->is_active = true;
            $org->is_verified = false;
            expect($org->getStatusLabel())->toBe('Pending Verification');

            $org->is_active = false;
            expect($org->getStatusLabel())->toBe('Inactive');
        });
    });

    describe('verification management', function (): void {
        it('verifies organization correctly', function (): void {
            $org = Organization::factory()->make();
            $org->is_verified = false;

            $org->verify();

            expect($org->is_verified)->toBeTrue()
                ->and($org->verification_date)->toBeInstanceOf(Carbon::class)
                ->and($org->verification_date->toDateString())->toBe('2025-01-15');
        });

        it('throws exception when verifying already verified organization', function (): void {
            $org = Organization::factory()->verified()->make([
                'id' => 1,
                'is_verified' => true,
            ]);

            expect(fn () => $org->verify())
                ->toThrow(OrganizationException::class);
        });

        it('unverifies organization correctly', function (): void {
            $org = Organization::factory()->verified()->make([
                'is_verified' => true,
                'verification_date' => Carbon::now(),
            ]);

            $org->unverify();

            expect($org->is_verified)->toBeFalse()
                ->and($org->verification_date)->toBeNull();
        });

        it('throws exception when unverifying not verified organization', function (): void {
            $org = Organization::factory()->unverified()->make([
                'id' => 1,
                'is_verified' => false,
            ]);

            expect(fn () => $org->unverify())
                ->toThrow(OrganizationException::class);
        });

        it('checks eligibility for verification correctly', function (): void {
            $org = Organization::factory()->make();

            // Not eligible - inactive
            $org->is_active = false;
            expect($org->isEligibleForVerification())->toBeFalse();

            // Not eligible - no name
            $org->is_active = true;
            $org->name = null;
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->name = [];
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->name = ['en' => ''];
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->name = ['en' => 'Unnamed Organization'];
            expect($org->isEligibleForVerification())->toBeFalse();

            // Not eligible - no registration number
            $org->name = ['en' => 'Valid Name'];
            $org->registration_number = null;
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->registration_number = '';
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->registration_number = '   ';
            expect($org->isEligibleForVerification())->toBeFalse();

            // Not eligible - no tax ID
            $org->registration_number = 'REG123';
            $org->tax_id = null;
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->tax_id = '';
            expect($org->isEligibleForVerification())->toBeFalse();

            // Not eligible - no email
            $org->tax_id = 'TAX456';
            $org->email = null;
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->email = '';
            expect($org->isEligibleForVerification())->toBeFalse();

            // Not eligible - no category
            $org->email = 'test@example.com';
            $org->category = null;
            expect($org->isEligibleForVerification())->toBeFalse();

            $org->category = '';
            expect($org->isEligibleForVerification())->toBeFalse();

            // Eligible - all requirements met
            $org->category = 'technology';
            expect($org->isEligibleForVerification())->toBeTrue();
        });
    });

    describe('provisioning management', function (): void {
        it('starts provisioning correctly', function (): void {
            $org = Organization::factory()->create([
                'provisioning_error' => 'Previous error',
            ]);

            $org->startProvisioning();

            expect($org->fresh()->provisioning_status)->toBe('provisioning')
                ->and($org->fresh()->provisioning_error)->toBeNull();
        });

        it('marks as provisioned correctly', function (): void {
            $org = Organization::factory()->create([
                'provisioning_status' => 'provisioning',
                'provisioned_at' => null,
            ]);

            $org->markAsProvisioned();

            expect($org->fresh()->provisioning_status)->toBe('active')
                ->and($org->fresh()->provisioned_at)->toBeInstanceOf(Carbon::class)
                ->and($org->fresh()->provisioning_error)->toBeNull();
        });

        it('marks as failed correctly', function (): void {
            $org = Organization::factory()->create([
                'provisioning_status' => 'provisioning',
            ]);
            $errorMessage = 'Database creation failed';

            $org->markAsFailed($errorMessage);

            expect($org->fresh()->provisioning_status)->toBe('failed')
                ->and($org->fresh()->provisioning_error)->toBe($errorMessage);
        });
    });

    describe('tenant-related functionality', function (): void {
        it('provides tenant key correctly', function (): void {
            $org = Organization::factory()->make();
            $org->id = 123;

            expect($org->getTenantKey())->toBe('123')
                ->and($org->getTenantKeyName())->toBe('id');
        });

        it('gets and sets internal tenant data', function (): void {
            $org = Organization::factory()->make();

            // Initially null
            expect($org->getInternal('config'))->toBeNull();

            // Set and get data
            $org->setInternal('config', ['theme' => 'dark']);
            expect($org->getInternal('config'))->toBe(['theme' => 'dark']);

            // Set multiple keys
            $org->setInternal('features', ['advanced' => true]);
            expect($org->getInternal('config'))->toBe(['theme' => 'dark'])
                ->and($org->getInternal('features'))->toBe(['advanced' => true]);

            // Returns self for chaining
            $result = $org->setInternal('new_key', 'value');
            expect($result)->toBe($org);
        });

        it('generates database name correctly', function (): void {
            $org = Organization::factory()->make();
            $org->subdomain = 'acme-corp';

            $expected = 'tenant_acme_corp';
            expect($org->getDatabaseName())->toBe($expected);

            // With existing database attribute
            $org->setAttribute('database', 'custom_db_name');
            expect($org->getDatabaseName())->toBe('custom_db_name');

            // Fallback to ID when no subdomain
            $org->subdomain = null;
            $org->setAttribute('database', null);
            $org->id = 456;
            expect($org->getDatabaseName())->toBe('tenant_456');
        });

        it('provides internal prefix correctly', function (): void {
            $org = Organization::factory()->make();
            $org->subdomain = 'acme-corp';

            expect($org->internalPrefix())->toBe('tenant_acme_corp_');

            $org->subdomain = null;
            $org->id = 789;
            expect($org->internalPrefix())->toBe('tenant_789_');
        });

        it('handles subdomain key conversion correctly', function (): void {
            $org = Organization::factory()->make();

            // Converts hyphens to underscores
            $org->subdomain = 'acme-corp-test';
            expect($org->getDatabaseName())->toBe('tenant_acme_corp_test');

            // Uses ID as fallback
            $org->subdomain = null;
            $org->id = 123;
            expect($org->getDatabaseName())->toBe('tenant_123');
        });
    });

    describe('searchable functionality', function (): void {
        it('determines if should be searchable', function (): void {
            $org = Organization::factory()->make();

            $org->is_active = false;
            expect($org->shouldBeSearchable())->toBeFalse();

            $org->is_active = true;
            expect($org->shouldBeSearchable())->toBeTrue();

            $org->is_active = 1;
            expect($org->shouldBeSearchable())->toBeTrue();

            $org->is_active = 0;
            expect($org->shouldBeSearchable())->toBeFalse();
        });

        it('generates searchable array correctly', function (): void {
            $org = Organization::factory()->make();
            $org->id = 1;
            $org->name = ['en' => 'ACME Corp', 'fr' => 'ACME Société'];
            $org->description = ['en' => 'Technology company'];
            $org->mission = ['en' => 'Innovation'];
            $org->website = 'https://acme.com';
            $org->email = 'contact@acme.com';
            $org->phone = '555-1234';
            $org->address = '123 Main St';
            $org->city = 'New York';
            $org->postal_code = '10001';
            $org->country = 'USA';
            $org->category = 'technology';
            $org->type = 'corporation';
            $org->is_active = true;
            $org->is_verified = true;
            $org->verification_date = Carbon::parse('2025-01-10');
            $org->created_at = Carbon::parse('2024-01-01');
            $org->updated_at = Carbon::parse('2025-01-01');

            $searchableArray = $org->toSearchableArray();

            expect($searchableArray)->toHaveKey('id')
                ->and($searchableArray)->toHaveKey('name')
                ->and($searchableArray)->toHaveKey('name_en')
                ->and($searchableArray)->toHaveKey('name_fr')
                ->and($searchableArray)->toHaveKey('description')
                ->and($searchableArray)->toHaveKey('website')
                ->and($searchableArray)->toHaveKey('email')
                ->and($searchableArray)->toHaveKey('status')
                ->and($searchableArray)->toHaveKey('location')
                ->and($searchableArray)->toHaveKey('full_address')
                ->and($searchableArray['id'])->toBe(1)
                ->and($searchableArray['name'])->toBe('ACME Corp')
                ->and($searchableArray['name_en'])->toBe('ACME Corp')
                ->and($searchableArray['name_fr'])->toBe('ACME Société')
                ->and($searchableArray['status'])->toBe('active')
                ->and($searchableArray['location'])->toBe('New York, USA')
                ->and($searchableArray['full_address'])->toContain('123 Main St');
        });
    });

    describe('cast and attribute handling', function (): void {
        it('casts attributes correctly', function (): void {
            $org = Organization::factory()->create([
                'is_active' => true,
                'is_verified' => false,
            ]);

            expect($org->name)->toBeArray()
                ->and($org->is_active)->toBeBool()
                ->and($org->is_verified)->toBeBool()
                ->and($org->created_at)->toBeInstanceOf(\Carbon\Carbon::class)
                ->and($org->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('handles fillable attributes correctly', function (): void {
            $org = Organization::factory()->make();
            $fillable = $org->getFillable();

            expect($fillable)->toContain('name')
                ->and($fillable)->toContain('email')
                ->and($fillable)->toContain('website')
                ->and($fillable)->toContain('phone')
                ->and($fillable)->toContain('address')
                ->and($fillable)->toContain('category')
                ->and($fillable)->toContain('is_active')
                ->and($fillable)->toContain('is_verified')
                ->and($fillable)->toContain('subdomain')
                ->and($fillable)->toContain('tenant_data');
        });

        it('handles translatable attributes correctly', function (): void {
            $org = Organization::factory()->make();

            // Access protected property through reflection
            $reflection = new ReflectionClass($org);
            $translatableProperty = $reflection->getProperty('translatable');
            $translatableProperty->setAccessible(true);
            $translatable = $translatableProperty->getValue($org);

            expect($translatable)->toContain('name')
                ->and($translatable)->toContain('description')
                ->and($translatable)->toContain('mission');
        });
    });

    describe('date and time handling', function (): void {
        it('handles created_at and updated_at correctly', function (): void {
            $org = Organization::factory()->create();

            expect($org->getCreatedAt())->toBeInstanceOf(Carbon::class)
                ->and($org->getUpdatedAt())->toBeInstanceOf(Carbon::class)
                ->and($org->getCreatedAt())->not->toBeNull()
                ->and($org->getUpdatedAt())->not->toBeNull();
        });

        it('handles null dates correctly', function (): void {
            $org = Organization::factory()->withNullDates()->make();

            expect($org->getCreatedAt())->toBeNull()
                ->and($org->getUpdatedAt())->toBeNull()
                ->and($org->getVerificationDate())->toBeNull()
                ->and($org->getFoundedDate())->toBeNull();
        });

        it('formats dates correctly for searchable array', function (): void {
            $org = Organization::factory()->make();
            $org->verification_date = Carbon::parse('2025-01-10 15:30:00');
            $org->created_at = Carbon::parse('2024-01-01 10:00:00');
            $org->updated_at = Carbon::parse('2025-01-01 12:00:00');

            $searchableArray = $org->toSearchableArray();

            expect($searchableArray['verification_date'])->toBeString()
                ->and($searchableArray['created_at'])->toBeString()
                ->and($searchableArray['updated_at'])->toBeString()
                ->and($searchableArray['verification_date'])->toContain('2025-01-10')
                ->and($searchableArray['created_at'])->toContain('2024-01-01')
                ->and($searchableArray['updated_at'])->toContain('2025-01-01');
        });
    });

    describe('edge cases and validation', function (): void {
        it('handles empty translations correctly', function (): void {
            $org = Organization::factory()->make();

            $org->name = [];
            expect($org->getName())->toBe('Unnamed Organization');

            $org->description = [];
            expect($org->getDescription())->toBeNull();

            $org->mission = [];
            expect($org->getMission())->toBeNull();
        });

        it('handles whitespace in verification eligibility', function (): void {
            $org = Organization::factory()->make();
            $org->is_active = true;
            $org->name = ['en' => '   '];
            $org->registration_number = '   ';
            $org->tax_id = '   ';
            $org->email = '   ';
            $org->category = '   ';

            expect($org->isEligibleForVerification())->toBeFalse();

            $org->name = ['en' => 'Valid Name'];
            $org->registration_number = 'REG123';
            $org->tax_id = 'TAX456';
            $org->email = 'test@example.com';
            $org->category = 'technology';

            expect($org->isEligibleForVerification())->toBeTrue();
        });

        it('handles various status scenarios', function (): void {
            $org = Organization::factory()->create();

            // Test with null values
            $org->update(['is_active' => false, 'is_verified' => false]);
            expect($org->fresh()->getStatus())->toBe('inactive');

            // Test with truthy/falsy values
            $org->update(['is_active' => true, 'is_verified' => false]);
            expect($org->fresh()->getStatus())->toBe('unverified');

            $org->update(['is_active' => true, 'is_verified' => true]);
            expect($org->fresh()->getStatus())->toBe('active');
        });

        it('handles complex tenant data scenarios', function (): void {
            $org = Organization::factory()->create([
                'name' => ['en' => 'Test Organization'],
            ]);

            // Set complex nested data
            $complexData = [
                'admin' => ['user_id' => 123],
                'settings' => [
                    'theme' => 'dark',
                    'features' => ['advanced' => true],
                ],
                'metadata' => [
                    'created_by' => 'system',
                    'version' => '1.0',
                ],
            ];

            $org->tenant_data = $complexData;

            expect($org->getAdminData())->toBe(['user_id' => 123])
                ->and($org->getInternal('settings'))->toBe(['theme' => 'dark', 'features' => ['advanced' => true]])
                ->and($org->getInternal('metadata'))->toBe(['created_by' => 'system', 'version' => '1.0']);

            // Update admin data should preserve others
            $org->setAdminData(['user_id' => 456, 'role' => 'super_admin']);

            expect($org->getAdminData())->toBe(['user_id' => 456, 'role' => 'super_admin'])
                ->and($org->getInternal('settings'))->toBe(['theme' => 'dark', 'features' => ['advanced' => true]])
                ->and($org->getInternal('metadata'))->toBe(['created_by' => 'system', 'version' => '1.0']);
        });
    });

    describe('model relationships and factory', function (): void {
        it('defines campaigns relationship correctly', function (): void {
            $org = Organization::factory()->make();
            $campaignsRelation = $org->campaigns();

            expect($campaignsRelation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('defines employees relationship correctly', function (): void {
            $org = Organization::factory()->make();
            $employeesRelation = $org->employees();

            expect($employeesRelation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('defines domains relationship correctly', function (): void {
            $org = Organization::factory()->make();
            $domainsRelation = $org->domains();

            expect($domainsRelation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('has correct model configuration', function (): void {
            $org = Organization::factory()->make();

            expect($org->getIncrementing())->toBeTrue()
                ->and($org->getKeyType())->toBe('int')
                ->and($org->getKeyName())->toBe('id');
        });
    });
});
