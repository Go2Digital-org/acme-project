<?php

declare(strict_types=1);

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\ValueObject\CategoryStatus;

beforeEach(function (): void {
    $this->category = Category::factory()->make([
        'id' => 1,
        'name' => ['en' => 'Environment', 'fr' => 'Environnement', 'de' => 'Umwelt'],
        'description' => ['en' => 'Environmental protection and sustainability', 'fr' => 'Protection environnementale et durabilité'],
        'slug' => 'environment',
        'status' => CategoryStatus::ACTIVE,
        'color' => '#4CAF50',
        'icon' => 'leaf',
        'sort_order' => 10,
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDay(),
    ]);
});

it('implements Laravel Scout Searchable trait', function (): void {
    expect($this->category)
        ->toBeInstanceOf(Category::class)
        ->and(class_uses($this->category))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('returns correct searchable array with all required fields', function (): void {
    // Mock campaigns relationship
    $campaigns = collect([
        Campaign::factory()->make(['id' => 1]),
        Campaign::factory()->make(['id' => 2]),
    ]);

    $this->category->setRelation('campaigns', $campaigns);

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray)
        ->toBeArray()
        ->toHaveKeys([
            'id', 'name', 'name_en', 'name_fr', 'name_de',
            'description', 'description_en', 'description_fr', 'description_de',
            'slug', 'status', 'color', 'icon', 'sort_order',
            'is_active', 'campaigns_count', 'has_active_campaigns',
            'created_at', 'updated_at',
        ])
        ->and($searchableArray['id'])->toBe(1)
        ->and($searchableArray['name'])->toBe('Environment')
        ->and($searchableArray['name_en'])->toBe('Environment')
        ->and($searchableArray['name_fr'])->toBe('Environnement')
        ->and($searchableArray['name_de'])->toBe('Umwelt')
        ->and($searchableArray['description'])->toBe('Environmental protection and sustainability')
        ->and($searchableArray['description_en'])->toBe('Environmental protection and sustainability')
        ->and($searchableArray['description_fr'])->toBe('Protection environnementale et durabilité')
        ->and($searchableArray['description_de'])->toBeNull()
        ->and($searchableArray['slug'])->toBe('environment')
        ->and($searchableArray['status'])->toBe('active')
        ->and($searchableArray['color'])->toBe('#4CAF50')
        ->and($searchableArray['icon'])->toBe('leaf')
        ->and($searchableArray['sort_order'])->toBe(10)
        ->and($searchableArray['campaigns_count'])->toBe(2)
        ->and($searchableArray['created_at'])->toBeString()
        ->and($searchableArray['updated_at'])->toBeString();
});

it('handles null and empty translations in searchable array', function (): void {
    $this->category->name = ['en' => 'Health'];
    $this->category->description = null;

    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['name'])->toBe('Health')
        ->and($searchableArray['name_en'])->toBe('Health')
        ->and($searchableArray['name_fr'])->toBeNull()
        ->and($searchableArray['name_de'])->toBeNull()
        ->and($searchableArray['description'])->toBeNull()
        ->and($searchableArray['description_en'])->toBeNull()
        ->and($searchableArray['description_fr'])->toBeNull()
        ->and($searchableArray['description_de'])->toBeNull();
});

it('formats dates correctly in searchable array', function (): void {
    $createdAt = now()->subDays(30);
    $updatedAt = now()->subDay();

    $this->category->created_at = $createdAt;
    $this->category->updated_at = $updatedAt;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['created_at'])
        ->toBe($createdAt->toIso8601String())
        ->and($searchableArray['updated_at'])
        ->toBe($updatedAt->toIso8601String());
});

it('handles null dates in searchable array', function (): void {
    $this->category->created_at = null;
    $this->category->updated_at = null;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['created_at'])->toBeNull()
        ->and($searchableArray['updated_at'])->toBeNull();
});

it('loads campaigns relationship when converting to searchable array', function (): void {
    $category = Category::factory()->create();

    // Ensure campaigns are not loaded initially
    expect($category->relationLoaded('campaigns'))->toBeFalse();

    // Call toSearchableArray - should work without errors and handle campaigns
    $searchableArray = $category->toSearchableArray();

    // Verify it includes campaigns_count
    expect($searchableArray)->toHaveKey('campaigns_count')
        ->and($searchableArray['campaigns_count'])->toBeInt();
});

describe('shouldBeSearchable method', function (): void {
    it('returns true for active categories', function (): void {
        $this->category->status = CategoryStatus::ACTIVE;

        expect($this->category->shouldBeSearchable())->toBeTrue();
    });

    it('returns false for inactive categories', function (): void {
        $this->category->status = CategoryStatus::INACTIVE;

        expect($this->category->shouldBeSearchable())->toBeFalse();
    });
});

it('has makeAllSearchableUsing method that includes campaigns relationship', function (): void {
    $category = new Category;
    $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
    $query->shouldReceive('with')->with(['campaigns'])->once()->andReturnSelf();

    $reflection = new ReflectionClass($category);
    $method = $reflection->getMethod('makeAllSearchableUsing');
    $method->setAccessible(true);

    $result = $method->invoke($category, $query);
    expect($result)->toBe($query);
});

it('includes campaigns count in searchable array', function (): void {
    $campaigns = collect([
        Campaign::factory()->make(),
        Campaign::factory()->make(),
        Campaign::factory()->make(),
    ]);

    $this->category->setRelation('campaigns', $campaigns);

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['campaigns_count'])->toBe(3);
});

it('handles empty campaigns collection in searchable array', function (): void {
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['campaigns_count'])->toBe(0);
});

it('converts status enum to string value in searchable array', function (): void {
    $this->category->status = CategoryStatus::ACTIVE;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['status'])->toBe('active');
});

it('handles different status values in searchable array', function (): void {
    // Test inactive status
    $this->category->status = CategoryStatus::INACTIVE;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['status'])->toBe('inactive');
});

it('includes all visual attributes in searchable array', function (): void {
    $this->category->color = '#FF5722';
    $this->category->icon = 'heart';
    $this->category->sort_order = 5;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['color'])->toBe('#FF5722')
        ->and($searchableArray['icon'])->toBe('heart')
        ->and($searchableArray['sort_order'])->toBe(5);
});

it('handles null optional fields in searchable array', function (): void {
    $this->category->color = null;
    $this->category->icon = null;
    $this->category->description = null;
    $this->category->setRelation('campaigns', collect());

    $searchableArray = $this->category->toSearchableArray();

    expect($searchableArray['color'])->toBeNull()
        ->and($searchableArray['icon'])->toBeNull()
        ->and($searchableArray['description'])->toBeNull();
});
