<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource;

class CreatePaymentGateway extends CreateRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    public function getTitle(): string
    {
        return 'Create Payment Gateway';
    }

    public function getSubheading(): ?string
    {
        return 'Add a new payment gateway for processing donations. Make sure to test the configuration before activating it.';
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the edit page after creation for further configuration
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create Gateway')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel')
            ->icon('heroicon-o-x-mark');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configuration_guide')
                ->label('Configuration Guide')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->action(function (): void {
                    $this->showConfigurationGuide();
                })
                ->modalHeading('Payment Gateway Configuration Guide')
                ->modalContent(view('filament.payment-gateways.configuration-guide'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate and normalize form data before creating the record
        $data = $this->validateAndNormalizeFormData($data);

        // Ensure new gateways start inactive by default for safety
        $data['is_active'] = false;

        // Set default priority if not provided
        if (! isset($data['priority']) || empty($data['priority'])) {
            $data['priority'] = 0;
        }

        // Validate provider-specific settings
        $this->validateProviderSettings($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Send success notification with next steps
        Notification::make()
            ->title('Payment Gateway Created')
            ->body('Gateway created successfully. Please test the connection before activating it.')
            ->success()
            ->actions([
                Action::make('test_connection')
                    ->label('Test Connection')
                    ->button()
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record])),
            ])
            ->send();
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Failed to create payment gateway', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'general' => 'Failed to create payment gateway. Please check your configuration and try again.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateAndNormalizeFormData(array $data): array
    {
        // Currency support is now handled by the many-to-many relationship

        // Validate amount ranges
        if (isset($data['min_amount'], $data['max_amount']) && (float) $data['min_amount'] >= (float) $data['max_amount']) {
            throw ValidationException::withMessages([
                'max_amount' => 'Maximum amount must be greater than minimum amount.',
            ]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateProviderSettings(array $data): void
    {
        $provider = $data['provider'] ?? null;
        $settings = $data['settings'] ?? [];

        if ($provider === PaymentGateway::PROVIDER_STRIPE) {
            $this->validateStripeSettings($settings);

            return;
        }

        if ($provider === PaymentGateway::PROVIDER_MOLLIE) {
            $this->validateMollieSettings($settings);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateStripeSettings(array $settings): void
    {
        $requiredSettings = ['webhook_url', 'publishable_key'];
        $missing = [];

        foreach ($requiredSettings as $setting) {
            if (! isset($settings[$setting]) || empty($settings[$setting])) {
                $missing[] = $setting;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'settings' => 'Stripe configuration missing required settings: ' . implode(', ', $missing),
            ]);
        }

        // Validate webhook URL format
        if (isset($settings['webhook_url']) && ! filter_var($settings['webhook_url'], FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'settings' => 'Webhook URL must be a valid URL.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateMollieSettings(array $settings): void
    {
        $requiredSettings = ['webhook_url'];
        $missing = [];

        foreach ($requiredSettings as $setting) {
            if (! isset($settings[$setting]) || empty($settings[$setting])) {
                $missing[] = $setting;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'settings' => 'Mollie configuration missing required settings: ' . implode(', ', $missing),
            ]);
        }

        // Validate webhook URL format
        if (isset($settings['webhook_url']) && ! filter_var($settings['webhook_url'], FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'settings' => 'Webhook URL must be a valid URL.',
            ]);
        }
    }

    private function showConfigurationGuide(): void
    {
        // This method could be expanded to show provider-specific guidance
        // For now, it's handled by the modal content view
    }
}
