<?php

declare(strict_types=1);

use Modules\Shared\Domain\Model\Page;

beforeEach(function (): void {
    $this->page = Page::factory()->make([
        'id' => 1,
        'slug' => 'about-us',
        'status' => 'published',
        'order' => 10,
        'title' => [
            'en' => 'About Us',
            'fr' => 'À propos de nous',
            'de' => 'Über uns',
        ],
        'content' => [
            'en' => '<h1>Welcome</h1><p>This is our <strong>story</strong>.</p>',
            'fr' => '<h1>Bienvenue</h1><p>Voici notre <strong>histoire</strong>.</p>',
        ],
        'url' => '/about-us',
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDay(),
    ]);
});

it('implements Laravel Scout Searchable trait', function (): void {
    expect($this->page)
        ->toBeInstanceOf(Page::class)
        ->and(class_uses($this->page))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('returns correct searchable array with all required fields', function (): void {
    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray)
        ->toBeArray()
        ->toHaveKeys([
            'id', 'slug', 'status', 'order',
            'title', 'title_en', 'title_fr', 'title_de',
            'content', 'content_en', 'content_fr', 'content_de',
            'content_plain', 'content_plain_en', 'content_plain_fr', 'content_plain_de',
            'is_published', 'is_draft', 'url',
            'created_at', 'updated_at',
        ])
        ->and($searchableArray['id'])->toBe(1)
        ->and($searchableArray['slug'])->toBe('about-us')
        ->and($searchableArray['status'])->toBe('published')
        ->and($searchableArray['order'])->toBe(10)
        ->and($searchableArray['title'])->toBe('About Us')
        ->and($searchableArray['title_en'])->toBe('About Us')
        ->and($searchableArray['title_fr'])->toBe('À propos de nous')
        ->and($searchableArray['title_de'])->toBe('Über uns')
        ->and($searchableArray['content'])->toBe('<h1>Welcome</h1><p>This is our <strong>story</strong>.</p>')
        ->and($searchableArray['content_en'])->toBe('<h1>Welcome</h1><p>This is our <strong>story</strong>.</p>')
        ->and($searchableArray['content_fr'])->toBe('<h1>Bienvenue</h1><p>Voici notre <strong>histoire</strong>.</p>')
        ->and($searchableArray['content_de'])->toBeNull()
        ->and($searchableArray['content_plain'])->toBe('WelcomeThis is our story.')
        ->and($searchableArray['content_plain_en'])->toBe('WelcomeThis is our story.')
        ->and($searchableArray['content_plain_fr'])->toBe('BienvenueVoici notre histoire.')
        ->and($searchableArray['content_plain_de'])->toBe('')
        ->and($searchableArray['url'])->toContain('/about-us')
        ->and($searchableArray['created_at'])->toBeString()
        ->and($searchableArray['updated_at'])->toBeString();
});

it('handles null and empty translations in searchable array', function (): void {
    $this->page->title = ['en' => 'Contact'];
    $this->page->content = null;

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['title'])->toBe('Contact')
        ->and($searchableArray['title_en'])->toBe('Contact')
        ->and($searchableArray['title_fr'])->toBeNull()
        ->and($searchableArray['title_de'])->toBeNull()
        ->and($searchableArray['content'])->toBeNull()
        ->and($searchableArray['content_en'])->toBeNull()
        ->and($searchableArray['content_fr'])->toBeNull()
        ->and($searchableArray['content_de'])->toBeNull()
        ->and($searchableArray['content_plain'])->toBe('')
        ->and($searchableArray['content_plain_en'])->toBe('')
        ->and($searchableArray['content_plain_fr'])->toBe('')
        ->and($searchableArray['content_plain_de'])->toBe('');
});

it('handles missing title translation with fallback', function (): void {
    $this->page->title = null;

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['title'])->toBe('Untitled Page');
});

it('strips HTML tags correctly from content in searchable array', function (): void {
    $this->page->content = [
        'en' => '<div><h1>Title</h1><p>Paragraph with <a href="#">link</a> and <em>emphasis</em>.</p><ul><li>List item</li></ul></div>',
        'fr' => '<p>Contenu avec <strong>gras</strong> et <span class="highlight">surligné</span>.</p>',
    ];

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['content_plain_en'])
        ->toBe('TitleParagraph with link and emphasis.List item')
        ->and($searchableArray['content_plain_fr'])
        ->toBe('Contenu avec gras et surligné.');
});

it('formats dates correctly in searchable array', function (): void {
    $createdAt = now()->subDays(30);
    $updatedAt = now()->subDay();

    $this->page->created_at = $createdAt;
    $this->page->updated_at = $updatedAt;

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['created_at'])
        ->toBe($createdAt->toIso8601String())
        ->and($searchableArray['updated_at'])
        ->toBe($updatedAt->toIso8601String());
});

it('handles null dates in searchable array', function (): void {
    $this->page->created_at = null;
    $this->page->updated_at = null;

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['created_at'])->toBeNull()
        ->and($searchableArray['updated_at'])->toBeNull();
});

describe('shouldBeSearchable method', function (): void {
    it('returns true for published pages', function (): void {
        $this->page->status = 'published';

        // Mock isPublished method
        $spy = Mockery::spy($this->page);
        $spy->shouldReceive('isPublished')->andReturn(true);

        expect($spy->shouldBeSearchable())->toBeTrue();
    });

    it('returns false for draft pages', function (): void {
        $this->page->status = 'draft';

        // Mock isPublished method
        $spy = Mockery::spy($this->page);
        $spy->shouldReceive('isPublished')->andReturn(false);

        expect($spy->shouldBeSearchable())->toBeFalse();
    });

    it('returns false for archived pages', function (): void {
        $this->page->status = 'archived';

        // Mock isPublished method
        $spy = Mockery::spy($this->page);
        $spy->shouldReceive('isPublished')->andReturn(false);

        expect($spy->shouldBeSearchable())->toBeFalse();
    });
});

it('includes status flags in searchable array', function (): void {
    // Mock isPublished and isDraft methods
    $spy = Mockery::spy($this->page);
    $spy->shouldReceive('isPublished')->andReturn(true);
    $spy->shouldReceive('isDraft')->andReturn(false);
    $spy->shouldReceive('getAttribute')->andReturnUsing(fn ($key) => $this->page->$key);

    $searchableArray = $spy->toSearchableArray();

    expect($searchableArray['is_published'])->toBeTrue()
        ->and($searchableArray['is_draft'])->toBeFalse();
});

it('handles different status combinations in searchable array', function (): void {
    // Test draft status
    $this->page->status = 'draft';

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['is_published'])->toBeFalse()
        ->and($searchableArray['is_draft'])->toBeTrue();
});

it('generates URL from slug even when url attribute is null', function (): void {
    $this->page->url = null;

    $searchableArray = $this->page->toSearchableArray();

    // URL is generated from slug via route() accessor
    expect($searchableArray['url'])->toContain($this->page->slug);
});

it('handles complex HTML content stripping', function (): void {
    $this->page->content = [
        'en' => '
            <div class="container">
                <header>
                    <h1>Main Title</h1>
                    <nav><a href="#section1">Section 1</a></nav>
                </header>
                <main>
                    <article>
                        <h2>Article Title</h2>
                        <p>This is a paragraph with <strong>bold text</strong> and <em>italic text</em>.</p>
                        <blockquote>This is a quote.</blockquote>
                        <ul>
                            <li>First item</li>
                            <li>Second item with <code>inline code</code></li>
                        </ul>
                    </article>
                </main>
                <footer>
                    <p>Footer content</p>
                </footer>
            </div>
        ',
    ];

    $searchableArray = $this->page->toSearchableArray();

    // Should strip all HTML tags but preserve text content
    expect($searchableArray['content_plain_en'])
        ->toContain('Main Title')
        ->toContain('Section 1')
        ->toContain('Article Title')
        ->toContain('This is a paragraph with bold text and italic text.')
        ->toContain('This is a quote.')
        ->toContain('First item')
        ->toContain('Second item with inline code')
        ->toContain('Footer content')
        ->not->toContain('<')
        ->not->toContain('>');
});

it('handles empty content fields gracefully', function (): void {
    $this->page->content = ['en' => '', 'fr' => null];

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['content_en'])->toBe('')
        ->and($searchableArray['content_fr'])->toBeNull()
        ->and($searchableArray['content_plain_en'])->toBe('')
        ->and($searchableArray['content_plain_fr'])->toBe('');
});

it('preserves order field as integer in searchable array', function (): void {
    $this->page->order = 42;

    $searchableArray = $this->page->toSearchableArray();

    expect($searchableArray['order'])->toBe(42)
        ->and($searchableArray['order'])->toBeInt();
});
