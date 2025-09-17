<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\UI;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Domain\Contract\DonationInterface;
use Modules\Shared\Domain\ValueObject\DonationStatus;

final class DonationCard extends Component
{
    public function __construct(public DonationInterface $donation, public bool $showReceipt = true, public string $size = 'default', public string $class = '') {}

    public function render(): View
    {
        return view('components.donation-card');
    }

    public function cardClasses(): string
    {
        return match ($this->size) {
            'compact' => 'p-4',
            default => 'p-6',
        };
    }

    public function statusColor(): string
    {
        return match (DonationStatus::from($this->donation->getStatus())) {
            DonationStatus::COMPLETED => 'text-secondary bg-secondary/10',
            DonationStatus::PENDING => 'text-accent bg-accent/10',
            DonationStatus::PROCESSING => 'text-blue-600 bg-blue-50 dark:bg-blue-900/20',
            DonationStatus::FAILED => 'text-red-600 bg-red-50 dark:bg-red-900/20',
            DonationStatus::CANCELLED => 'text-orange-600 bg-orange-50 dark:bg-orange-900/20',
            DonationStatus::REFUNDED => 'text-purple-600 bg-purple-50 dark:bg-purple-900/20',
        };
    }

    public function statusIcon(): string
    {
        return match (DonationStatus::from($this->donation->getStatus())) {
            DonationStatus::COMPLETED => 'fas fa-check-circle',
            DonationStatus::PENDING => 'fas fa-clock',
            DonationStatus::PROCESSING => 'fas fa-spinner',
            DonationStatus::FAILED => 'fas fa-exclamation-triangle',
            DonationStatus::CANCELLED => 'fas fa-times-circle',
            DonationStatus::REFUNDED => 'fas fa-undo',
        };
    }

    public function donationDate(): Carbon
    {
        return Carbon::parse($this->donation->getCreatedAt());
    }

    public function formattedAmount(): string
    {
        return '$' . number_format($this->donation->getAmount(), 2);
    }

    public function hasOrganizationName(): bool
    {
        return ! in_array($this->donation->getOrganizationName(), [null, '', '0'], true);
    }

    public function hasRecurringType(): bool
    {
        return ! in_array($this->donation->getRecurringFrequency(), [null, '', '0'], true);
    }

    public function hasPaymentMethod(): bool
    {
        return $this->donation->getPaymentMethod() !== null;
    }

    public function hasTransactionId(): bool
    {
        return ! in_array($this->donation->getTransactionId(), [null, '', '0'], true);
    }

    public function hasMessage(): bool
    {
        return ! in_array($this->donation->getNotes(), [null, '', '0'], true);
    }

    public function hasImpactDescription(): bool
    {
        // Since impact_description doesn't exist in the model, always return false
        return false;
    }

    public function paymentMethodIcon(): string
    {
        $paymentMethod = $this->donation->getPaymentMethod();

        if ($paymentMethod === null) {
            return 'fas fa-wallet';
        }

        $method = strtolower($paymentMethod);

        if (str_contains($method, 'card')) {
            return 'fas fa-credit-card';
        }

        if (str_contains($method, 'paypal')) {
            return 'fab fa-paypal';
        }

        return 'fas fa-wallet';
    }

    public function truncatedTransactionId(): string
    {
        return substr($this->donation->getTransactionId() ?? '', 0, 12);
    }

    public function canDownloadReceipt(): bool
    {
        return $this->showReceipt && DonationStatus::from($this->donation->getStatus()) === DonationStatus::COMPLETED;
    }

    public function canShareImpact(): bool
    {
        return DonationStatus::from($this->donation->getStatus()) === DonationStatus::COMPLETED &&
               $this->donation->isAnonymous() === false;
    }

    public function isTaxDeductible(): bool
    {
        return $this->donation->isEligibleForTaxReceipt();
    }

    public function nextPaymentDate(): string
    {
        return $this->donationDate()->addMonth()->format('M j, Y');
    }
}
