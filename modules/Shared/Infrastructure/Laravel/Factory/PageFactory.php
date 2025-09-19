<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Factory;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Domain\Model\Page;

/**
 * @property Generator $faker
 *
 * @method static static new(array<string, mixed> $attributes = [])
 *
 * @extends Factory<Page>
 */
final class PageFactory extends Factory
{
    /** @var class-string<Page> */
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->slug,
            'status' => fake()->randomElement(['draft', 'published']),
            'order' => fake()->numberBetween(1, 100),
            'title' => [
                'en' => fake()->sentence(3, true),
                'nl' => fake()->sentence(3, true),
                'fr' => fake()->sentence(3, true),
            ],
            'content' => [
                'en' => $this->generateHtmlContent(),
                'nl' => $this->generateHtmlContent(),
                'fr' => $this->generateHtmlContent(),
            ],
        ];
    }

    /**
     * Indicate that the page should be published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }

    /**
     * Indicate that the page should be a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'draft',
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($page): void {
            // Additional configuration after making
            /* @var Page $page */
        })->afterCreating(function ($page): void {
            // Additional configuration after creating
            /* @var Page $page */
        });
    }

    /**
     * Generate HTML content with proper structure.
     */
    private function generateHtmlContent(): string
    {
        $paragraphs = (array) fake()->paragraphs(3);
        $content = '<div class="prose max-w-none">';

        foreach ($paragraphs as $paragraph) {
            $content .= '<p>' . $paragraph . '</p>';
        }

        // Add a list occasionally
        if (fake()->boolean(40)) {
            $content .= '<h3>' . fake()->sentence(3, true) . '</h3>';
            $content .= '<ul>';

            for ($i = 0; $i < fake()->numberBetween(3, 5); $i++) {
                $content .= '<li>' . fake()->sentence() . '</li>';
            }
            $content .= '</ul>';
        }

        return $content . '</div>';
    }
}
