<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing progress data for any measurable goal.
 * Following hexagonal architecture principles for clean domain modeling.
 */
final readonly class ProgressData
{
    private float $percentage;

    private string $status;

    private string $colorScheme;

    private bool $hasReachedMilestone;

    private ?int $currentMilestone;

    public function __construct(
        public float $current,
        public float $target,
        public ?string $label = null,
    ) {
        if ($target <= 0) {
            throw new InvalidArgumentException('Target must be greater than zero');
        }

        if ($current < 0) {
            throw new InvalidArgumentException('Current value cannot be negative');
        }

        $this->percentage = min(100, ($current / $target) * 100);
        $this->status = $this->calculateStatus();
        $this->colorScheme = $this->calculateColorScheme();
        $this->currentMilestone = $this->calculateCurrentMilestone();
        $this->hasReachedMilestone = $this->currentMilestone !== null;
    }

    public function getPercentage(): float
    {
        return round($this->percentage, 2);
    }

    public function getFormattedPercentage(): string
    {
        return round($this->percentage) . '%';
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getColorScheme(): string
    {
        return $this->colorScheme;
    }

    public function hasReachedGoal(): bool
    {
        return $this->percentage >= 100;
    }

    public function hasReachedMilestone(): bool
    {
        return $this->hasReachedMilestone;
    }

    public function getCurrentMilestone(): ?int
    {
        return $this->currentMilestone;
    }

    public function getNextMilestone(): ?int
    {
        $milestones = [25, 50, 75, 100];

        foreach ($milestones as $milestone) {
            if ($this->percentage < $milestone) {
                return $milestone;
            }
        }

        return null;
    }

    public function getProgressToNextMilestone(): float
    {
        $nextMilestone = $this->getNextMilestone();

        if ($nextMilestone === null) {
            return 100.0;
        }

        $previousMilestone = match ($nextMilestone) {
            25 => 0,
            50 => 25,
            75 => 50,
            100 => 75,
            default => 0,
        };

        $currentProgress = $this->percentage - $previousMilestone;
        $milestoneRange = $nextMilestone - $previousMilestone;

        return ($currentProgress / $milestoneRange) * 100;
    }

    public function getMomentumScore(): float
    {
        // Calculate momentum based on how close to goal and speed of progress
        if ($this->percentage >= 90) {
            return 1.0; // Maximum momentum when very close to goal
        }

        if ($this->percentage >= 75) {
            return 0.8;
        }

        if ($this->percentage >= 50) {
            return 0.6;
        }

        if ($this->percentage >= 25) {
            return 0.4;
        }

        return 0.2;
    }

    public function getAnimationIntensity(): string
    {
        $momentum = $this->getMomentumScore();

        if ($momentum >= 0.8) {
            return 'high';
        }

        if ($momentum >= 0.5) {
            return 'medium';
        }

        return 'low';
    }

    public function shouldShowCelebration(): bool
    {
        if ($this->hasReachedGoal()) {
            return true;
        }

        return $this->hasReachedMilestone && $this->currentMilestone >= 75;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVisualizationData(): array
    {
        return [
            'percentage' => $this->getPercentage(),
            'formatted_percentage' => $this->getFormattedPercentage(),
            'current' => $this->current,
            'target' => $this->target,
            'remaining' => max(0, $this->target - $this->current),
            'status' => $this->status,
            'color_scheme' => $this->colorScheme,
            'has_reached_goal' => $this->hasReachedGoal(),
            'has_reached_milestone' => $this->hasReachedMilestone,
            'current_milestone' => $this->currentMilestone,
            'next_milestone' => $this->getNextMilestone(),
            'progress_to_next_milestone' => $this->getProgressToNextMilestone(),
            'momentum_score' => $this->getMomentumScore(),
            'animation_intensity' => $this->getAnimationIntensity(),
            'should_show_celebration' => $this->shouldShowCelebration(),
            'label' => $this->label,
        ];
    }

    public static function fromPercentage(float $percentage, ?string $label = null): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }

        return new self($percentage, 100, $label);
    }

    public function equals(self $other): bool
    {
        return $this->current === $other->current
            && $this->target === $other->target
            && $this->label === $other->label;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getVisualizationData();
    }

    private function calculateStatus(): string
    {
        if ($this->percentage >= 100) {
            return 'completed';
        }

        if ($this->percentage >= 75) {
            return 'almost-there';
        }

        if ($this->percentage >= 50) {
            return 'halfway';
        }

        if ($this->percentage >= 25) {
            return 'started';
        }

        if ($this->percentage > 0) {
            return 'beginning';
        }

        return 'not-started';
    }

    private function calculateColorScheme(): string
    {
        if ($this->percentage >= 100) {
            return 'success'; // Green gradient
        }

        if ($this->percentage >= 75) {
            return 'vibrant'; // Blue to green gradient
        }

        if ($this->percentage >= 50) {
            return 'progress'; // Blue gradient
        }

        if ($this->percentage >= 25) {
            return 'active'; // Light blue gradient
        }

        return 'starting'; // Gray to blue gradient
    }

    private function calculateCurrentMilestone(): ?int
    {
        $milestones = [100, 75, 50, 25];

        foreach ($milestones as $milestone) {
            if ($this->percentage >= $milestone) {
                return $milestone;
            }
        }

        return null;
    }
}
