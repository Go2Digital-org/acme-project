<?php

declare(strict_types=1);

use Modules\Dashboard\Domain\ValueObject\ActivityType;

describe('ActivityType Enum', function (): void {
    describe('Enum Values', function (): void {
        it('has all expected enum cases', function (): void {
            $cases = ActivityType::cases();

            expect($cases)->toHaveCount(7)
                ->and(collect($cases)->pluck('value'))->toContain(
                    'donation',
                    'campaign_created',
                    'campaign_bookmarked',
                    'campaign_shared',
                    'milestone_reached',
                    'profile_updated',
                    'team_joined'
                );
        });

        it('creates enum instances from values', function (): void {
            expect(ActivityType::DONATION->value)->toBe('donation')
                ->and(ActivityType::CAMPAIGN_CREATED->value)->toBe('campaign_created')
                ->and(ActivityType::CAMPAIGN_BOOKMARKED->value)->toBe('campaign_bookmarked')
                ->and(ActivityType::CAMPAIGN_SHARED->value)->toBe('campaign_shared')
                ->and(ActivityType::MILESTONE_REACHED->value)->toBe('milestone_reached')
                ->and(ActivityType::PROFILE_UPDATED->value)->toBe('profile_updated')
                ->and(ActivityType::TEAM_JOINED->value)->toBe('team_joined');
        });
    });

    describe('Labels', function (): void {
        it('returns correct labels for all activity types', function (): void {
            expect(ActivityType::DONATION->getLabel())->toBe('Made a donation')
                ->and(ActivityType::CAMPAIGN_CREATED->getLabel())->toBe('Created a campaign')
                ->and(ActivityType::CAMPAIGN_BOOKMARKED->getLabel())->toBe('Bookmarked a campaign')
                ->and(ActivityType::CAMPAIGN_SHARED->getLabel())->toBe('Shared a campaign')
                ->and(ActivityType::MILESTONE_REACHED->getLabel())->toBe('Reached a milestone')
                ->and(ActivityType::PROFILE_UPDATED->getLabel())->toBe('Updated profile')
                ->and(ActivityType::TEAM_JOINED->getLabel())->toBe('Joined a team');
        });

        it('labels are descriptive action phrases', function (): void {
            foreach (ActivityType::cases() as $activityType) {
                $label = $activityType->getLabel();
                expect($label)->toBeString()
                    ->and(strlen($label))->toBeGreaterThan(5)
                    ->and($label)->toContain(' '); // Contains spaces (multi-word)
            }
        });

        it('all labels use past tense or action phrases', function (): void {
            $labels = [
                ActivityType::DONATION->getLabel(),
                ActivityType::CAMPAIGN_CREATED->getLabel(),
                ActivityType::CAMPAIGN_BOOKMARKED->getLabel(),
                ActivityType::CAMPAIGN_SHARED->getLabel(),
                ActivityType::MILESTONE_REACHED->getLabel(),
                ActivityType::PROFILE_UPDATED->getLabel(),
                ActivityType::TEAM_JOINED->getLabel(),
            ];

            foreach ($labels as $label) {
                expect($label)->toMatch('/(Made|Created|Bookmarked|Shared|Reached|Updated|Joined)/i');
            }
        });

        it('donation activity has specific label format', function (): void {
            expect(ActivityType::DONATION->getLabel())->toStartWith('Made')
                ->and(ActivityType::DONATION->getLabel())->toContain('donation');
        });

        it('campaign-related activities have consistent format', function (): void {
            $campaignActivities = [
                ActivityType::CAMPAIGN_CREATED,
                ActivityType::CAMPAIGN_BOOKMARKED,
                ActivityType::CAMPAIGN_SHARED,
            ];

            foreach ($campaignActivities as $activity) {
                expect($activity->getLabel())->toContain('campaign')
                    ->and($activity->getLabel())->toContain('a ');
            }
        });
    });

    describe('Activity Categories', function (): void {
        it('identifies donation activities', function (): void {
            expect(ActivityType::DONATION->value)->toBe('donation')
                ->and(ActivityType::DONATION->getLabel())->toContain('donation');
        });

        it('identifies campaign-related activities', function (): void {
            $campaignRelated = [
                ActivityType::CAMPAIGN_CREATED,
                ActivityType::CAMPAIGN_BOOKMARKED,
                ActivityType::CAMPAIGN_SHARED,
            ];

            foreach ($campaignRelated as $activity) {
                expect($activity->value)->toContain('campaign')
                    ->and($activity->getLabel())->toContain('campaign');
            }
        });

        it('identifies milestone activities', function (): void {
            expect(ActivityType::MILESTONE_REACHED->value)->toContain('milestone')
                ->and(ActivityType::MILESTONE_REACHED->getLabel())->toContain('milestone');
        });

        it('identifies profile activities', function (): void {
            expect(ActivityType::PROFILE_UPDATED->value)->toContain('profile')
                ->and(ActivityType::PROFILE_UPDATED->getLabel())->toContain('profile');
        });

        it('identifies team activities', function (): void {
            expect(ActivityType::TEAM_JOINED->value)->toContain('team')
                ->and(ActivityType::TEAM_JOINED->getLabel())->toContain('team');
        });
    });

    describe('Consistency', function (): void {
        it('all activity types have unique values', function (): void {
            $values = array_map(fn ($activity) => $activity->value, ActivityType::cases());
            $uniqueValues = array_unique($values);

            expect(count($values))->toBe(count($uniqueValues));
        });

        it('all activity types have unique labels', function (): void {
            $labels = array_map(fn ($activity) => $activity->getLabel(), ActivityType::cases());
            $uniqueLabels = array_unique($labels);

            expect(count($labels))->toBe(count($uniqueLabels));
        });

        it('values use snake_case naming convention', function (): void {
            foreach (ActivityType::cases() as $activity) {
                expect($activity->value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
            }
        });

        it('labels are properly formatted sentences', function (): void {
            foreach (ActivityType::cases() as $activity) {
                $label = $activity->getLabel();
                expect($label[0])->toBe(strtoupper($label[0])) // Starts with capital
                    ->and($label)->not->toEndWith('.'); // No ending period
            }
        });
    });
});
