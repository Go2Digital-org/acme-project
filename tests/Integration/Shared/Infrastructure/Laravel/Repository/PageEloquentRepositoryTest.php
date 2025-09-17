<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Infrastructure\Laravel\Repository\PageEloquentRepository;

beforeEach(function (): void {
    // Manually run only the pages migration to avoid dependency issues
    if (! Schema::hasTable('pages')) {
        Schema::create('pages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('slug')->unique();
            $table->json('title')->nullable();
            $table->json('content')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->integer('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['status', 'order']);
        });
    }

    // Clean up any existing test data
    Page::query()->forceDelete();

    $this->repository = new PageEloquentRepository(new Page);
});

describe('PageEloquentRepository - Basic CRUD Operations', function (): void {
    it('creates a page successfully', function (): void {
        $data = [
            'slug' => 'about-us',
            'status' => 'published',
            'order' => 1,
        ];

        $page = $this->repository->create($data);

        expect($page)->toBeInstanceOf(Page::class)
            ->and($page->slug)->toBe('about-us')
            ->and($page->status)->toBe('published')
            ->and($page->exists)->toBeTrue();

        $this->assertDatabaseHas('pages', [
            'slug' => 'about-us',
            'status' => 'published',
        ]);
    });

    it('finds page by id', function (): void {
        $page = Page::factory()->create();

        $found = $this->repository->findById($page->id);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($page->id);
    });

    it('returns null for non-existent page', function (): void {
        $found = $this->repository->findById(999999);

        expect($found)->toBeNull();
    });

    it('finds page by slug', function (): void {
        $page = Page::factory()->create(['slug' => 'unique-page-slug']);

        $found = $this->repository->findBySlug('unique-page-slug');

        expect($found)->not->toBeNull()
            ->and($found->slug)->toBe('unique-page-slug');
    });

    it('finds multiple pages by slugs', function (): void {
        $page1 = Page::factory()->create(['slug' => 'first-page']);
        $page2 = Page::factory()->create(['slug' => 'second-page']);
        $page3 = Page::factory()->create(['slug' => 'third-page']);

        $found = $this->repository->findBySlugs(['first-page', 'third-page', 'non-existent']);

        expect($found)->toBeInstanceOf(Collection::class)
            ->and($found)->toHaveCount(2);

        expect($found->get('first-page'))->not->toBeNull()
            ->and($found->get('first-page')->id)->toBe($page1->id);

        expect($found->get('third-page'))->not->toBeNull()
            ->and($found->get('third-page')->id)->toBe($page3->id);

        expect($found->get('non-existent'))->toBeNull();
        expect($found->get('second-page'))->toBeNull();
    });

    it('returns empty collection when finding pages by empty slug array', function (): void {
        $found = $this->repository->findBySlugs([]);

        expect($found)->toBeInstanceOf(Collection::class)
            ->and($found)->toHaveCount(0);
    });

    it('updates page by id', function (): void {
        $page = Page::factory()->create(['status' => 'draft']);

        $updateData = ['status' => 'published'];
        $result = $this->repository->updateById($page->id, $updateData);

        expect($result)->toBeTrue();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => 'published',
        ]);
    });

    it('deletes page by id', function (): void {
        $page = Page::factory()->create();

        $result = $this->repository->deleteById($page->id);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    });
});

describe('PageEloquentRepository - Status-based Queries', function (): void {
    beforeEach(function (): void {
        Page::factory()->count(2)->create(['status' => 'published']);
        Page::factory()->count(1)->create(['status' => 'draft']);
    });

    it('gets published pages', function (): void {
        $result = $this->repository->getPublishedPages();

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toHaveCount(2);

        foreach ($result as $page) {
            expect($page->status)->toBe('published');
        }
    });

    it('gets draft pages', function (): void {
        $result = $this->repository->getDraftPages();

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toHaveCount(1);

        foreach ($result as $page) {
            expect($page->status)->toBe('draft');
        }
    });
});

describe('PageEloquentRepository - Simple Utilities', function (): void {
    it('checks if slug exists', function (): void {
        Page::factory()->create(['slug' => 'existing-slug']);

        $exists = $this->repository->slugExists('existing-slug');
        $notExists = $this->repository->slugExists('non-existent-slug');

        expect($exists)->toBeTrue()
            ->and($notExists)->toBeFalse();
    });

    it('gets next order value', function (): void {
        Page::factory()->create(['order' => 1]);
        Page::factory()->create(['order' => 2]);

        $nextOrder = $this->repository->getNextOrder();

        expect($nextOrder)->toBe(3);
    });
});
