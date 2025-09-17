<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Category\Domain\Model\Category;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign Web Controllers', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->category = Category::factory()->create([
            'name' => 'Environmental',
            'slug' => 'environmental',
        ]);

        // Set default locale for consistent testing
        app()->setLocale('en');
    });

    describe('Campaign Detail Page', function (): void {

        it('returns 404 for non-existent campaigns', function (): void {
            $response = $this->get('/en/campaigns/999999');

            $response->assertNotFound();
        });
    });

    describe('Campaign Creation', function (): void {
        it('requires authentication for store', function (): void {
            $response = $this->post(route('campaigns.store'), []);

            $response->assertRedirect();
        });
    });

    describe('Campaign Update', function (): void {
        it('prevents unauthorized users from editing', function (): void {
            $otherUser = User::factory()->create();
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($otherUser, 'employee')
                ->create();

            $response = $this->actingAs($this->user)
                ->get(route('campaigns.edit', $campaign));

            // Accept various authorization responses
            expect($response->status())->toBeIn([403, 302, 404]);
        });

        it('updates campaign successfully', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Original Title'],
                ]);

            $updateData = [
                'title' => [
                    'en' => 'Updated Campaign Title',
                    'nl' => 'Bijgewerkte Campagne Titel',
                    'fr' => 'Titre de Campagne Mis à Jour',
                ],
                'description' => [
                    'en' => is_array($campaign->description) ? ($campaign->description['en'] ?? 'Test description') : $campaign->description,
                    'nl' => 'Test beschrijving',
                    'fr' => 'Description du test',
                ],
                'goal_amount' => $campaign->goal_amount,
                'start_date' => $campaign->start_date->format('Y-m-d'),
                'end_date' => $campaign->end_date->format('Y-m-d'),
                'organization_id' => $this->organization->id,
            ];

            $response = $this->actingAs($this->user)
                ->put(route('campaigns.update', $campaign), $updateData);

            $response->assertRedirect()
                ->assertSessionHas('success');

            // Refresh the campaign to check updated data
            $campaign->refresh();
            expect($campaign->getTitle())->toBe('Updated Campaign Title');
        });

        it('requires authentication for update', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $response = $this->put(route('campaigns.update', $campaign), []);

            $response->assertRedirect();
        });
    });

    describe('Campaign Deletion', function (): void {
        it('soft deletes campaign successfully for owner', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $response = $this->actingAs($this->user)
                ->delete(route('campaigns.destroy', $campaign));

            $response->assertRedirect()
                ->assertSessionHas('success');

            // Campaign should be soft deleted, not permanently removed
            $this->assertSoftDeleted('campaigns', [
                'id' => $campaign->id,
            ]);
        });

        it('prevents unauthorized users from deleting', function (): void {
            $otherUser = User::factory()->create();
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($otherUser, 'employee')
                ->create();

            $response = $this->actingAs($this->user)
                ->delete(route('campaigns.destroy', $campaign));

            // Accept either 403 Forbidden or redirect as valid unauthorized responses
            expect($response->status())->toBeIn([403, 302]);

            $this->assertDatabaseHas('campaigns', [
                'id' => $campaign->id,
                'deleted_at' => null,
            ]);
        });

        it('requires authentication for deletion', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $response = $this->delete(route('campaigns.destroy', $campaign));

            $response->assertRedirect();
        });
    });

});
