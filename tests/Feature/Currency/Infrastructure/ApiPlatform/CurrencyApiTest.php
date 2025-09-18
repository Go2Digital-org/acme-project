<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Currency API Platform', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    describe('GET /api/currencies', function (): void {
        it('returns available currencies list for authenticated user', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies');

            $response->assertOk();
            $json = $response->json();

            expect($json)->toHaveKeys(['data', 'default', 'current']);
            expect($json['data'])->toBeArray();
            expect($json['default'])->toBeString();
            expect($json['current'])->toBeString();
        });

        it('requires authentication', function (): void {
            $response = $this->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies');

            $response->assertStatus(401);
        });

        it('returns proper currency data structure', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies');

            $response->assertOk();
            $json = $response->json();

            expect($json['data'])->toBeArray();
            if (! empty($json['data'])) {
                $currency = reset($json['data']);
                expect($currency)->toHaveKeys([
                    'code', 'symbol', 'name', 'decimal_places',
                    'decimal_separator', 'thousands_separator', 'symbol_position',
                ]);
            }
        });
    });

    describe('GET /api/currencies/current', function (): void {
        it('requires authentication', function (): void {
            $response = $this->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertStatus(401);
        });

        it('returns user currency preference for authenticated user', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();

            expect($json)->toHaveKey('data');
            expect($json['data'])->toHaveKey('currency');
            expect($json['data']['currency'])->toHaveKeys(['code', 'symbol', 'name']);
        });
    });

    describe('POST /api/currencies/set', function (): void {
        it('requires authentication', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->post('/api/currencies/set', [
                    'currency' => 'USD',
                ]);

            $response->assertStatus(401);
        });

        it('sets currency preference with valid currency code', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'USD',
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json)->toHaveKeys(['message', 'data']);
            expect($json['message'])->toBe('Currency preference updated successfully');
            expect($json['data'])->toHaveKey('currency');
            expect($json['data']['currency']['code'])->toBe('USD');
        });

        it('sets currency preference for authenticated user', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'EUR',
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json['data']['currency']['code'])->toBe('EUR');
        });

        it('validates currency code is required', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', []);

            $response->assertStatus(422);
        });

        it('validates currency code format', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'INVALID',
                ]);

            $response->assertStatus(422);
        });

        it('rejects invalid currency codes', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'XXX',
                ]);

            $response->assertStatus(422);
        });

        it('persists currency preference for authenticated users', function (): void {
            // Set currency preference
            $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'EUR',
                ]);

            // Verify it's persisted by checking current currency
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();
            expect($json['data']['currency']['code'])->toBe('EUR');
        });

        it('stores currency preference in user account', function (): void {
            // Set currency preference
            $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'GBP',
                ]);

            // Verify it's persisted by checking current currency in another session
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();
            expect($json['data']['currency']['code'])->toBe('GBP');
        });
    });

    describe('POST /api/currencies/format', function (): void {
        it('requires authentication', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->post('/api/currencies/format', [
                    'amount' => 123.45,
                ]);

            $response->assertStatus(401);
        });

        it('formats amount with default currency', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 123.45,
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json)->toHaveKey('data');
            expect($json['data'])->toHaveKeys(['amount', 'formatted', 'currency']);
            expect($json['data']['amount'])->toBe(123.45);
            expect($json['data']['formatted'])->toBeString();
            expect($json['data']['currency'])->toHaveKeys(['code', 'symbol', 'name']);
        });

        it('formats amount with specified currency', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 100.50,
                    'currency' => 'USD',
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json['data']['amount'])->toBe(100.50);
            expect($json['data']['currency']['code'])->toBe('USD');
            expect($json['data']['formatted'])->toBeString();
        });

        it('validates amount is required', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', []);

            $response->assertStatus(422);
        });

        it('validates amount is numeric', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 'not-a-number',
                ]);

            $response->assertStatus(422);
        });

        it('validates currency code format when provided', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 100,
                    'currency' => 'INVALID',
                ]);

            $response->assertStatus(422);
        });

        it('rejects invalid currency codes', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 100,
                    'currency' => 'XXX',
                ]);

            $response->assertStatus(422);
        });

        it('handles negative amounts', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => -50.25,
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json['data']['amount'])->toBe(-50.25);
            expect($json['data']['formatted'])->toBeString();
        });

        it('handles zero amount', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 0,
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json['data']['amount'])->toBe(0.0);
            expect($json['data']['formatted'])->toBeString();
        });

        it('handles large amounts', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/format', [
                    'amount' => 1234567.89,
                ]);

            $response->assertOk();
            $json = $response->json();

            expect($json['data']['amount'])->toBe(1234567.89);
            expect($json['data']['formatted'])->toBeString();
        });
    });

    describe('Currency preference persistence', function (): void {
        it('database storage persists user preferences', function (): void {
            // Set user currency preference (authenticated)
            $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'EUR',
                ]);

            // Verify authenticated user gets database preference
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();
            expect($json['data']['currency']['code'])->toBe('EUR');
        });

        it('authenticated users without preference get default currency', function (): void {
            // Get current currency for authenticated user (no preference set)
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();

            // Should return default currency
            expect($json['data']['currency']['code'])->toBeString();
        });

        it('user preferences persist across different sessions', function (): void {
            // Set currency preference in first session
            $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/api/currencies/set', [
                    'currency' => 'GBP',
                ]);

            // Verify preference persists in a new test context
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies/current');

            $response->assertOk();
            $json = $response->json();
            expect($json['data']['currency']['code'])->toBe('GBP');
        });
    });

    describe('API Platform format compatibility', function (): void {
        it('returns JSON-LD format when requested', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json'])
                ->get('/api/currencies');

            $response->assertOk();
            $response->assertHeaderContains('Content-Type', 'application/ld+json');
        });

        it('returns JSON format by default', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/currencies');

            $response->assertOk();
            // Fixed: Content-Type header includes charset
            $response->assertHeaderContains('Content-Type', 'application/json');
        });

        it('handles unsupported media types gracefully', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'text/xml'])
                ->get('/api/currencies');

            // Should fall back to JSON or return 406
            expect($response->status())->toBeIn([200, 406]);
        });
    });

    describe('Error handling', function (): void {
        it('handles malformed JSON in requests', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->postJson('/api/currencies/set', 'invalid-json');

            $response->assertStatus(400);
        });

        it('handles missing Content-Type header', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->post('/api/currencies/set', [
                    'currency' => 'USD',
                ]);

            $response->assertOk();
        });
    });
});
