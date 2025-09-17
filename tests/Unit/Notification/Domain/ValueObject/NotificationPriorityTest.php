<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\NotificationPriority;

describe('NotificationPriority Value Object', function (): void {
    describe('Constants', function (): void {
        it('defines all required priority constants', function (): void {
            expect(NotificationPriority::LOW)->toBe('low')
                ->and(NotificationPriority::NORMAL)->toBe('normal')
                ->and(NotificationPriority::MEDIUM)->toBe('medium')
                ->and(NotificationPriority::HIGH)->toBe('high')
                ->and(NotificationPriority::URGENT)->toBe('urgent')
                ->and(NotificationPriority::CRITICAL)->toBe('critical');
        });
    });

    describe('all() method', function (): void {
        it('returns all valid notification priorities', function (): void {
            $expected = [
                'low',
                'normal',
                'medium',
                'high',
                'urgent',
                'critical',
            ];

            expect(NotificationPriority::all())->toBe($expected);
        });

        it('returns array with correct count', function (): void {
            expect(NotificationPriority::all())->toHaveCount(6);
        });
    });

    describe('isValid() method', function (): void {
        it('validates valid priorities', function (): void {
            expect(NotificationPriority::isValid('low'))->toBeTrue()
                ->and(NotificationPriority::isValid('normal'))->toBeTrue()
                ->and(NotificationPriority::isValid('medium'))->toBeTrue()
                ->and(NotificationPriority::isValid('high'))->toBeTrue()
                ->and(NotificationPriority::isValid('urgent'))->toBeTrue()
                ->and(NotificationPriority::isValid('critical'))->toBeTrue();
        });

        it('rejects invalid priorities', function (): void {
            expect(NotificationPriority::isValid('invalid'))->toBeFalse()
                ->and(NotificationPriority::isValid('extreme'))->toBeFalse()
                ->and(NotificationPriority::isValid(''))->toBeFalse()
                ->and(NotificationPriority::isValid('LOW'))->toBeFalse(); // Case sensitive
        });
    });

    describe('urgentPriorities() method', function (): void {
        it('returns urgent level priorities', function (): void {
            $expected = ['urgent', 'critical'];

            expect(NotificationPriority::urgentPriorities())->toBe($expected);
        });

        it('includes urgent and critical only', function (): void {
            $urgentPriorities = NotificationPriority::urgentPriorities();

            expect($urgentPriorities)->toContain('urgent')
                ->and($urgentPriorities)->toContain('critical')
                ->and($urgentPriorities)->not->toContain('high')
                ->and($urgentPriorities)->not->toContain('medium')
                ->and($urgentPriorities)->not->toContain('normal')
                ->and($urgentPriorities)->not->toContain('low');
        });
    });

    describe('batchablePriorities() method', function (): void {
        it('returns priorities that can be batched', function (): void {
            $expected = ['low', 'normal', 'medium'];

            expect(NotificationPriority::batchablePriorities())->toBe($expected);
        });

        it('includes low, normal, and medium only', function (): void {
            $batchablePriorities = NotificationPriority::batchablePriorities();

            expect($batchablePriorities)->toContain('low')
                ->and($batchablePriorities)->toContain('normal')
                ->and($batchablePriorities)->toContain('medium')
                ->and($batchablePriorities)->not->toContain('high')
                ->and($batchablePriorities)->not->toContain('urgent')
                ->and($batchablePriorities)->not->toContain('critical');
        });
    });

    describe('label() method', function (): void {
        it('returns human-readable labels for valid priorities', function (): void {
            expect(NotificationPriority::label('low'))->toBe('Low Priority')
                ->and(NotificationPriority::label('normal'))->toBe('Normal Priority')
                ->and(NotificationPriority::label('medium'))->toBe('Medium Priority')
                ->and(NotificationPriority::label('high'))->toBe('High Priority')
                ->and(NotificationPriority::label('urgent'))->toBe('Urgent')
                ->and(NotificationPriority::label('critical'))->toBe('Critical');
        });

        it('returns Unknown for invalid priority', function (): void {
            expect(NotificationPriority::label('invalid'))->toBe('Unknown')
                ->and(NotificationPriority::label(''))->toBe('Unknown')
                ->and(NotificationPriority::label('LOW'))->toBe('Unknown');
        });

        it('uses proper capitalization', function (): void {
            foreach (NotificationPriority::all() as $priority) {
                $label = NotificationPriority::label($priority);
                expect($label)->toMatch('/^[A-Z]/');
            }
        });
    });

    describe('colorClass() method', function (): void {
        it('returns appropriate color classes for UI display', function (): void {
            expect(NotificationPriority::colorClass('low'))->toBe('gray')
                ->and(NotificationPriority::colorClass('normal'))->toBe('blue')
                ->and(NotificationPriority::colorClass('medium'))->toBe('green')
                ->and(NotificationPriority::colorClass('high'))->toBe('yellow')
                ->and(NotificationPriority::colorClass('urgent'))->toBe('orange')
                ->and(NotificationPriority::colorClass('critical'))->toBe('red');
        });

        it('returns gray for unknown priorities', function (): void {
            expect(NotificationPriority::colorClass('invalid'))->toBe('gray')
                ->and(NotificationPriority::colorClass(''))->toBe('gray')
                ->and(NotificationPriority::colorClass('unknown'))->toBe('gray');
        });

        it('uses different colors for each priority', function (): void {
            $priorities = NotificationPriority::all();
            $colors = [];

            foreach ($priorities as $priority) {
                $color = NotificationPriority::colorClass($priority);
                $colors[$priority] = $color;
            }

            expect(array_unique($colors))->toHaveCount(6);
        });
    });

    describe('icon() method', function (): void {
        it('returns appropriate icons for each priority', function (): void {
            expect(NotificationPriority::icon('low'))->toBe('heroicon-o-minus-circle')
                ->and(NotificationPriority::icon('normal'))->toBe('heroicon-o-bell')
                ->and(NotificationPriority::icon('medium'))->toBe('heroicon-o-information-circle')
                ->and(NotificationPriority::icon('high'))->toBe('heroicon-o-exclamation-circle')
                ->and(NotificationPriority::icon('urgent'))->toBe('heroicon-o-exclamation-triangle')
                ->and(NotificationPriority::icon('critical'))->toBe('heroicon-o-shield-exclamation');
        });

        it('returns default bell icon for unknown priority', function (): void {
            expect(NotificationPriority::icon('invalid'))->toBe('heroicon-o-bell')
                ->and(NotificationPriority::icon(''))->toBe('heroicon-o-bell');
        });

        it('uses heroicon prefix for all icons', function (): void {
            foreach (NotificationPriority::all() as $priority) {
                $icon = NotificationPriority::icon($priority);
                expect($icon)->toStartWith('heroicon-o-');
            }
        });
    });

    describe('weight() method', function (): void {
        it('returns correct weight for each priority', function (): void {
            expect(NotificationPriority::weight('low'))->toBe(1)
                ->and(NotificationPriority::weight('normal'))->toBe(2)
                ->and(NotificationPriority::weight('medium'))->toBe(3)
                ->and(NotificationPriority::weight('high'))->toBe(4)
                ->and(NotificationPriority::weight('urgent'))->toBe(5)
                ->and(NotificationPriority::weight('critical'))->toBe(6);
        });

        it('returns default weight for unknown priority', function (): void {
            expect(NotificationPriority::weight('invalid'))->toBe(2)
                ->and(NotificationPriority::weight(''))->toBe(2);
        });

        it('assigns increasing weights to higher priorities', function (): void {
            $priorities = ['low', 'normal', 'medium', 'high', 'urgent', 'critical'];
            $previousWeight = 0;

            foreach ($priorities as $priority) {
                $weight = NotificationPriority::weight($priority);
                expect($weight)->toBeGreaterThan($previousWeight);
                $previousWeight = $weight;
            }
        });
    });

    describe('fromWeight() method', function (): void {
        it('returns correct priority for each weight', function (): void {
            expect(NotificationPriority::fromWeight(1))->toBe('low')
                ->and(NotificationPriority::fromWeight(2))->toBe('normal')
                ->and(NotificationPriority::fromWeight(3))->toBe('medium')
                ->and(NotificationPriority::fromWeight(4))->toBe('high')
                ->and(NotificationPriority::fromWeight(5))->toBe('urgent')
                ->and(NotificationPriority::fromWeight(6))->toBe('critical');
        });

        it('returns normal for invalid weight', function (): void {
            expect(NotificationPriority::fromWeight(0))->toBe('normal')
                ->and(NotificationPriority::fromWeight(7))->toBe('normal')
                ->and(NotificationPriority::fromWeight(-1))->toBe('normal');
        });

        it('provides bidirectional weight conversion', function (): void {
            foreach (NotificationPriority::all() as $priority) {
                $weight = NotificationPriority::weight($priority);
                $convertedPriority = NotificationPriority::fromWeight($weight);
                expect($convertedPriority)->toBe($priority);
            }
        });
    });

    describe('requiresImmediateProcessing() method', function (): void {
        it('identifies urgent priorities that need immediate processing', function (): void {
            expect(NotificationPriority::requiresImmediateProcessing('urgent'))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing('critical'))->toBeTrue();
        });

        it('identifies non-urgent priorities that do not need immediate processing', function (): void {
            expect(NotificationPriority::requiresImmediateProcessing('low'))->toBeFalse()
                ->and(NotificationPriority::requiresImmediateProcessing('normal'))->toBeFalse()
                ->and(NotificationPriority::requiresImmediateProcessing('medium'))->toBeFalse()
                ->and(NotificationPriority::requiresImmediateProcessing('high'))->toBeFalse();
        });

        it('returns false for invalid priorities', function (): void {
            expect(NotificationPriority::requiresImmediateProcessing('invalid'))->toBeFalse()
                ->and(NotificationPriority::requiresImmediateProcessing(''))->toBeFalse();
        });
    });

    describe('shouldBePersistent() method', function (): void {
        it('identifies critical priority as persistent', function (): void {
            expect(NotificationPriority::shouldBePersistent('critical'))->toBeTrue();
        });

        it('identifies non-critical priorities as not persistent', function (): void {
            expect(NotificationPriority::shouldBePersistent('low'))->toBeFalse()
                ->and(NotificationPriority::shouldBePersistent('normal'))->toBeFalse()
                ->and(NotificationPriority::shouldBePersistent('medium'))->toBeFalse()
                ->and(NotificationPriority::shouldBePersistent('high'))->toBeFalse()
                ->and(NotificationPriority::shouldBePersistent('urgent'))->toBeFalse();
        });

        it('returns false for invalid priorities', function (): void {
            expect(NotificationPriority::shouldBePersistent('invalid'))->toBeFalse()
                ->and(NotificationPriority::shouldBePersistent(''))->toBeFalse();
        });
    });

    describe('sound() method', function (): void {
        it('returns sounds for high priority notifications', function (): void {
            expect(NotificationPriority::sound('critical'))->toBe('critical-alert')
                ->and(NotificationPriority::sound('urgent'))->toBe('urgent-notification')
                ->and(NotificationPriority::sound('high'))->toBe('high-priority');
        });

        it('returns null for low priority notifications', function (): void {
            expect(NotificationPriority::sound('low'))->toBeNull()
                ->and(NotificationPriority::sound('normal'))->toBeNull()
                ->and(NotificationPriority::sound('medium'))->toBeNull();
        });

        it('returns null for invalid priorities', function (): void {
            expect(NotificationPriority::sound('invalid'))->toBeNull()
                ->and(NotificationPriority::sound(''))->toBeNull();
        });
    });

    describe('maxRetryAttempts() method', function (): void {
        it('returns correct retry attempts for each priority', function (): void {
            expect(NotificationPriority::maxRetryAttempts('critical'))->toBe(5)
                ->and(NotificationPriority::maxRetryAttempts('urgent'))->toBe(3)
                ->and(NotificationPriority::maxRetryAttempts('high'))->toBe(2)
                ->and(NotificationPriority::maxRetryAttempts('medium'))->toBe(2)
                ->and(NotificationPriority::maxRetryAttempts('normal'))->toBe(1)
                ->and(NotificationPriority::maxRetryAttempts('low'))->toBe(1);
        });

        it('returns default retry attempts for invalid priority', function (): void {
            expect(NotificationPriority::maxRetryAttempts('invalid'))->toBe(1)
                ->and(NotificationPriority::maxRetryAttempts(''))->toBe(1);
        });

        it('assigns more retry attempts to higher priorities', function (): void {
            expect(NotificationPriority::maxRetryAttempts('critical'))
                ->toBeGreaterThan(NotificationPriority::maxRetryAttempts('urgent'))
                ->and(NotificationPriority::maxRetryAttempts('urgent'))
                ->toBeGreaterThan(NotificationPriority::maxRetryAttempts('normal'));
        });
    });

    describe('retryDelayMinutes() method', function (): void {
        it('returns correct retry delay for each priority', function (): void {
            expect(NotificationPriority::retryDelayMinutes('critical'))->toBe(1)
                ->and(NotificationPriority::retryDelayMinutes('urgent'))->toBe(5)
                ->and(NotificationPriority::retryDelayMinutes('high'))->toBe(15)
                ->and(NotificationPriority::retryDelayMinutes('medium'))->toBe(20)
                ->and(NotificationPriority::retryDelayMinutes('normal'))->toBe(30)
                ->and(NotificationPriority::retryDelayMinutes('low'))->toBe(60);
        });

        it('returns default retry delay for invalid priority', function (): void {
            expect(NotificationPriority::retryDelayMinutes('invalid'))->toBe(30)
                ->and(NotificationPriority::retryDelayMinutes(''))->toBe(30);
        });

        it('assigns shorter delays to higher priorities', function (): void {
            expect(NotificationPriority::retryDelayMinutes('critical'))
                ->toBeLessThan(NotificationPriority::retryDelayMinutes('urgent'))
                ->and(NotificationPriority::retryDelayMinutes('urgent'))
                ->toBeLessThan(NotificationPriority::retryDelayMinutes('normal'))
                ->and(NotificationPriority::retryDelayMinutes('normal'))
                ->toBeLessThan(NotificationPriority::retryDelayMinutes('low'));
        });
    });

    describe('Priority Categorization', function (): void {
        it('has no overlap between urgent and batchable priorities', function (): void {
            $urgentPriorities = NotificationPriority::urgentPriorities();
            $batchablePriorities = NotificationPriority::batchablePriorities();
            $overlap = array_intersect($urgentPriorities, $batchablePriorities);

            expect($overlap)->toBeEmpty();
        });

        it('accounts for all priorities in urgent or batchable categories plus high', function (): void {
            $allPriorities = NotificationPriority::all();
            $urgentPriorities = NotificationPriority::urgentPriorities();
            $batchablePriorities = NotificationPriority::batchablePriorities();
            $categorized = array_merge($urgentPriorities, $batchablePriorities, ['high']);

            sort($allPriorities);
            sort($categorized);

            expect($categorized)->toBe($allPriorities);
        });

        it('categorizes priorities logically by urgency', function (): void {
            // Urgent priorities should require immediate processing
            foreach (NotificationPriority::urgentPriorities() as $priority) {
                expect(NotificationPriority::requiresImmediateProcessing($priority))->toBeTrue();
            }

            // Batchable priorities should not require immediate processing
            foreach (NotificationPriority::batchablePriorities() as $priority) {
                expect(NotificationPriority::requiresImmediateProcessing($priority))->toBeFalse();
            }
        });

        it('has consistent weight ordering within categories', function (): void {
            $batchableWeights = array_map(
                fn ($priority) => NotificationPriority::weight($priority),
                NotificationPriority::batchablePriorities()
            );
            $urgentWeights = array_map(
                fn ($priority) => NotificationPriority::weight($priority),
                NotificationPriority::urgentPriorities()
            );

            // All urgent weights should be higher than all batchable weights
            expect(min($urgentWeights))->toBeGreaterThan(max($batchableWeights));
        });
    });

    describe('Method Consistency', function (): void {
        it('maintains consistency between weight and priority ordering', function (): void {
            $priorities = NotificationPriority::all();
            $weights = array_map(fn ($p) => NotificationPriority::weight($p), $priorities);

            // Check that weights increase with priority order
            for ($i = 0; $i < count($priorities) - 1; $i++) {
                expect($weights[$i])->toBeLessThan($weights[$i + 1]);
            }
        });

        it('ensures urgent priorities have more retry attempts', function (): void {
            $urgentPriorities = NotificationPriority::urgentPriorities();
            $batchablePriorities = NotificationPriority::batchablePriorities();

            foreach ($urgentPriorities as $urgent) {
                foreach ($batchablePriorities as $batchable) {
                    expect(NotificationPriority::maxRetryAttempts($urgent))
                        ->toBeGreaterThanOrEqual(NotificationPriority::maxRetryAttempts($batchable));
                }
            }
        });

        it('ensures urgent priorities have shorter retry delays', function (): void {
            $urgentPriorities = NotificationPriority::urgentPriorities();
            $batchablePriorities = NotificationPriority::batchablePriorities();

            foreach ($urgentPriorities as $urgent) {
                foreach ($batchablePriorities as $batchable) {
                    expect(NotificationPriority::retryDelayMinutes($urgent))
                        ->toBeLessThanOrEqual(NotificationPriority::retryDelayMinutes($batchable));
                }
            }
        });

        it('ensures only critical priority is persistent in UI', function (): void {
            foreach (NotificationPriority::all() as $priority) {
                if ($priority === 'critical') {
                    expect(NotificationPriority::shouldBePersistent($priority))->toBeTrue();
                } else {
                    expect(NotificationPriority::shouldBePersistent($priority))->toBeFalse();
                }
            }
        });

        it('ensures only high-level priorities have sounds', function (): void {
            $soundPriorities = ['critical', 'urgent', 'high'];
            $noSoundPriorities = ['medium', 'normal', 'low'];

            foreach ($soundPriorities as $priority) {
                expect(NotificationPriority::sound($priority))->not->toBeNull();
            }

            foreach ($noSoundPriorities as $priority) {
                expect(NotificationPriority::sound($priority))->toBeNull();
            }
        });
    });

    describe('Edge Cases and Validation', function (): void {
        it('handles null and non-string inputs gracefully', function (): void {
            // These would normally cause PHP errors, but we test string coercion
            expect(NotificationPriority::isValid('0'))->toBeFalse()
                ->and(NotificationPriority::isValid('1'))->toBeFalse()
                ->and(NotificationPriority::isValid('false'))->toBeFalse()
                ->and(NotificationPriority::isValid('true'))->toBeFalse();
        });

        it('handles case sensitivity strictly', function (): void {
            $validPriorities = NotificationPriority::all();

            foreach ($validPriorities as $priority) {
                expect(NotificationPriority::isValid(strtoupper($priority)))->toBeFalse()
                    ->and(NotificationPriority::isValid(ucfirst($priority)))->toBeFalse()
                    ->and(NotificationPriority::isValid(strtolower($priority)))->toBe(true); // Should be valid
            }
        });

        it('handles whitespace in priority strings', function (): void {
            expect(NotificationPriority::isValid(' low '))->toBeFalse()
                ->and(NotificationPriority::isValid("\tlow\t"))->toBeFalse()
                ->and(NotificationPriority::isValid("\nlow\n"))->toBeFalse()
                ->and(NotificationPriority::isValid('low '))->toBeFalse()
                ->and(NotificationPriority::isValid(' low'))->toBeFalse();
        });

        it('handles special characters and numbers', function (): void {
            expect(NotificationPriority::isValid('low1'))->toBeFalse()
                ->and(NotificationPriority::isValid('low-priority'))->toBeFalse()
                ->and(NotificationPriority::isValid('low_priority'))->toBeFalse()
                ->and(NotificationPriority::isValid('low.priority'))->toBeFalse()
                ->and(NotificationPriority::isValid('low priority'))->toBeFalse();
        });

        it('returns consistent defaults for invalid inputs across all methods', function (): void {
            $invalidInputs = ['invalid', '', 'CRITICAL', ' critical ', '123', null];

            foreach ($invalidInputs as $input) {
                expect(NotificationPriority::label((string) $input))->toBe('Unknown')
                    ->and(NotificationPriority::colorClass((string) $input))->toBe('gray')
                    ->and(NotificationPriority::icon((string) $input))->toBe('heroicon-o-bell')
                    ->and(NotificationPriority::weight((string) $input))->toBe(2)
                    ->and(NotificationPriority::sound((string) $input))->toBeNull()
                    ->and(NotificationPriority::maxRetryAttempts((string) $input))->toBe(1)
                    ->and(NotificationPriority::retryDelayMinutes((string) $input))->toBe(30)
                    ->and(NotificationPriority::requiresImmediateProcessing((string) $input))->toBeFalse()
                    ->and(NotificationPriority::shouldBePersistent((string) $input))->toBeFalse();
            }
        });
    });

    describe('Business Logic Validation', function (): void {
        it('ensures critical priority has maximum weight', function (): void {
            $criticalWeight = NotificationPriority::weight('critical');

            foreach (NotificationPriority::all() as $priority) {
                expect(NotificationPriority::weight($priority))->toBeLessThanOrEqual($criticalWeight);
            }
        });

        it('ensures low priority has minimum weight', function (): void {
            $lowWeight = NotificationPriority::weight('low');

            foreach (NotificationPriority::all() as $priority) {
                expect(NotificationPriority::weight($priority))->toBeGreaterThanOrEqual($lowWeight);
            }
        });

        it('ensures critical priority has maximum retry attempts', function (): void {
            $criticalRetries = NotificationPriority::maxRetryAttempts('critical');

            foreach (NotificationPriority::all() as $priority) {
                expect(NotificationPriority::maxRetryAttempts($priority))->toBeLessThanOrEqual($criticalRetries);
            }
        });

        it('ensures critical priority has minimum retry delay', function (): void {
            $criticalDelay = NotificationPriority::retryDelayMinutes('critical');

            foreach (NotificationPriority::all() as $priority) {
                expect(NotificationPriority::retryDelayMinutes($priority))->toBeGreaterThanOrEqual($criticalDelay);
            }
        });

        it('validates sound escalation makes sense', function (): void {
            expect(NotificationPriority::sound('critical'))->toBe('critical-alert')
                ->and(NotificationPriority::sound('urgent'))->toBe('urgent-notification')
                ->and(NotificationPriority::sound('high'))->toBe('high-priority');

            // Sound names should be descriptive
            expect(NotificationPriority::sound('critical'))->toContain('critical')
                ->and(NotificationPriority::sound('urgent'))->toContain('urgent')
                ->and(NotificationPriority::sound('high'))->toContain('high');
        });

        it('validates color progression makes visual sense', function (): void {
            // Colors should progress from calm to alarming
            expect(NotificationPriority::colorClass('low'))->toBe('gray') // Neutral
                ->and(NotificationPriority::colorClass('normal'))->toBe('blue') // Calm
                ->and(NotificationPriority::colorClass('medium'))->toBe('green') // Positive
                ->and(NotificationPriority::colorClass('high'))->toBe('yellow') // Warning
                ->and(NotificationPriority::colorClass('urgent'))->toBe('orange') // Alert
                ->and(NotificationPriority::colorClass('critical'))->toBe('red'); // Danger
        });

        it('validates icon progression makes sense', function (): void {
            // Icons should escalate in visual urgency
            expect(NotificationPriority::icon('low'))->toContain('minus-circle') // Minimal
                ->and(NotificationPriority::icon('normal'))->toContain('bell') // Standard
                ->and(NotificationPriority::icon('medium'))->toContain('information') // Informative
                ->and(NotificationPriority::icon('high'))->toContain('exclamation-circle') // Warning
                ->and(NotificationPriority::icon('urgent'))->toContain('exclamation-triangle') // Alert
                ->and(NotificationPriority::icon('critical'))->toContain('shield-exclamation'); // Critical
        });
    });

    describe('Real-world Scenarios', function (): void {
        it('handles campaign milestone notifications correctly', function (): void {
            $priority = 'high';

            expect(NotificationPriority::isValid($priority))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeFalse()
                ->and(NotificationPriority::maxRetryAttempts($priority))->toBe(2)
                ->and(NotificationPriority::retryDelayMinutes($priority))->toBe(15)
                ->and(NotificationPriority::sound($priority))->toBe('high-priority')
                ->and(NotificationPriority::colorClass($priority))->toBe('yellow');
        });

        it('handles security alert notifications correctly', function (): void {
            $priority = 'critical';

            expect(NotificationPriority::isValid($priority))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeTrue()
                ->and(NotificationPriority::shouldBePersistent($priority))->toBeTrue()
                ->and(NotificationPriority::maxRetryAttempts($priority))->toBe(5)
                ->and(NotificationPriority::retryDelayMinutes($priority))->toBe(1)
                ->and(NotificationPriority::sound($priority))->toBe('critical-alert')
                ->and(NotificationPriority::colorClass($priority))->toBe('red');
        });

        it('handles daily digest notifications correctly', function (): void {
            $priority = 'low';

            expect(NotificationPriority::isValid($priority))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeFalse()
                ->and(in_array($priority, NotificationPriority::batchablePriorities()))->toBeTrue()
                ->and(NotificationPriority::maxRetryAttempts($priority))->toBe(1)
                ->and(NotificationPriority::retryDelayMinutes($priority))->toBe(60)
                ->and(NotificationPriority::sound($priority))->toBeNull()
                ->and(NotificationPriority::colorClass($priority))->toBe('gray');
        });

        it('handles payment failure notifications correctly', function (): void {
            $priority = 'urgent';

            expect(NotificationPriority::isValid($priority))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeTrue()
                ->and(in_array($priority, NotificationPriority::urgentPriorities()))->toBeTrue()
                ->and(NotificationPriority::maxRetryAttempts($priority))->toBe(3)
                ->and(NotificationPriority::retryDelayMinutes($priority))->toBe(5)
                ->and(NotificationPriority::sound($priority))->toBe('urgent-notification')
                ->and(NotificationPriority::colorClass($priority))->toBe('orange');
        });

        it('handles routine user updates correctly', function (): void {
            $priority = 'normal';

            expect(NotificationPriority::isValid($priority))->toBeTrue()
                ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeFalse()
                ->and(in_array($priority, NotificationPriority::batchablePriorities()))->toBeTrue()
                ->and(NotificationPriority::weight($priority))->toBe(2) // Default weight
                ->and(NotificationPriority::fromWeight(2))->toBe($priority)
                ->and(NotificationPriority::maxRetryAttempts($priority))->toBe(1)
                ->and(NotificationPriority::retryDelayMinutes($priority))->toBe(30)
                ->and(NotificationPriority::colorClass($priority))->toBe('blue');
        });
    });

    describe('Performance and Optimization', function (): void {
        it('ensures all() method returns same array on multiple calls', function (): void {
            $first = NotificationPriority::all();
            $second = NotificationPriority::all();
            $third = NotificationPriority::all();

            expect($first)->toBe($second)
                ->and($second)->toBe($third);
        });

        it('ensures static method calls are consistent', function (): void {
            $priority = 'high';

            // Multiple calls should return same values
            expect(NotificationPriority::weight($priority))->toBe(NotificationPriority::weight($priority))
                ->and(NotificationPriority::label($priority))->toBe(NotificationPriority::label($priority))
                ->and(NotificationPriority::colorClass($priority))->toBe(NotificationPriority::colorClass($priority))
                ->and(NotificationPriority::icon($priority))->toBe(NotificationPriority::icon($priority));
        });

        it('validates method return types are correct', function (): void {
            foreach (NotificationPriority::all() as $priority) {
                expect(NotificationPriority::weight($priority))->toBeInt()
                    ->and(NotificationPriority::maxRetryAttempts($priority))->toBeInt()
                    ->and(NotificationPriority::retryDelayMinutes($priority))->toBeInt()
                    ->and(NotificationPriority::label($priority))->toBeString()
                    ->and(NotificationPriority::colorClass($priority))->toBeString()
                    ->and(NotificationPriority::icon($priority))->toBeString()
                    ->and(NotificationPriority::requiresImmediateProcessing($priority))->toBeBool()
                    ->and(NotificationPriority::shouldBePersistent($priority))->toBeBool();

                $sound = NotificationPriority::sound($priority);
                expect($sound === null || is_string($sound))->toBeTrue();
            }
        });
    });
});
