<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaigns = Campaign::factory()->count(5)->create(['organization_id' => $this->organization->id]);
});

describe('API Performance Basics', function (): void {
    it('campaigns collection uses reasonable query count', function (): void {
        $this->actingAs($this->user, 'sanctum');

        DB::enableQueryLog();
        $initialQueryCount = count(DB::getQueryLog());

        $response = $this->get('/api/campaigns', [
            'Accept' => 'application/json',
        ]);

        $finalQueryCount = count(DB::getQueryLog());
        $queryCount = $finalQueryCount - $initialQueryCount;

        $response->assertOk();
        expect($queryCount)->toBeLessThan(15);
    });

    it('single campaign retrieval is efficient', function (): void {
        $this->actingAs($this->user, 'sanctum');

        $campaign = $this->campaigns->first();

        DB::enableQueryLog();
        $initialQueryCount = count(DB::getQueryLog());

        $response = $this->get("/api/campaigns/{$campaign->id}", [
            'Accept' => 'application/json',
        ]);

        $finalQueryCount = count(DB::getQueryLog());
        $queryCount = $finalQueryCount - $initialQueryCount;

        $response->assertOk();
        expect($queryCount)->toBeLessThan(10);
    });

    it('responds within reasonable time', function (): void {
        $this->actingAs($this->user, 'sanctum');

        $startTime = microtime(true);

        $response = $this->get('/api/campaigns', [
            'Accept' => 'application/json',
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertOk();
        expect($responseTime)->toBeLessThan(5000); // 5 seconds - very generous
    });

    it('handles authentication errors quickly', function (): void {
        $startTime = microtime(true);

        $response = $this->get('/api/campaigns', [
            'Accept' => 'application/json',
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(401);
        expect($responseTime)->toBeLessThan(2000); // 2 seconds
    });
});
