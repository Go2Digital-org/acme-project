<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Campaign\Domain\Model\Campaign;

final class CampaignCard extends Component
{
    public string $cardClasses;

    public function __construct(
        public Campaign $campaign,
        /** @var array<string, mixed>|null */
        public ?array $campaignData = null,
        public bool $showActions = true,
        public string $size = 'default',
        public ?string $href = null,
    ) {
        $this->cardClasses = $this->getCardClasses();
    }

    public function render(): View
    {
        return view('components.campaign-card');
    }

    private function getCardClasses(): string
    {
        return match ($this->size) {
            'featured' => 'p-8 bg-primary/5 dark:bg-primary/10',
            'compact' => 'p-4',
            default => 'p-6',
        };
    }
}
