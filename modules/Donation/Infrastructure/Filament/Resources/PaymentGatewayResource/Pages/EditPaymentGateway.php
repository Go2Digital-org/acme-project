<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource;

class EditPaymentGateway extends EditRecord
{
    public Model|int|string|null $record = null;

    protected static string $resource = PaymentGatewayResource::class;

    public function getTitle(): string
    {
        return "Edit {$this->getPaymentGateway()->name}";
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('View')
                ->icon('heroicon-o-eye'),

            Action::make('toggle_status')
                ->label(fn (): string => $this->getPaymentGateway()->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn (): string => $this->getPaymentGateway()->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->getPaymentGateway()->is_active ? 'gray' : 'success')
                ->action(function (): void {
                    $this->toggleGatewayStatus();
                })
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->getPaymentGateway()->is_active ? 'Deactivate Gateway' : 'Activate Gateway')
                ->modalDescription(function (): string {
                    $gateway = $this->getPaymentGateway();

                    if ($gateway->is_active) {
                        return 'This will deactivate the gateway and prevent it from processing new payments. Existing payments will not be affected.';
                    }

                    if (! $gateway->isConfigured()) {
                        return 'This gateway is not fully configured. Please complete the configuration before activating.';
                    }

                    return 'This will activate the gateway and allow it to process payments.';
                }),

            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Payment Gateway')
                ->modalDescription('Are you sure you want to delete this payment gateway? This action cannot be undone and may affect existing payment records.')
                ->modalSubmitActionLabel('Delete Gateway'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Save Changes')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel')
            ->icon('heroicon-o-x-mark');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate and normalize form data before saving
        $data = $this->validateAndNormalizeFormData($data);

        // Validate provider-specific settings
        $this->validateProviderSettings($data);

        // If activating the gateway, ensure it's properly configured
        if (($data['is_active'] ?? false) && ! $this->isGatewayConfigured($data)) {
            throw ValidationException::withMessages([
                'is_active' => 'Gateway cannot be activated until it is fully configured.',
            ]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->update($data);

            return $record;
        } catch (Exception $e) {
            Log::error('Failed to update payment gateway', [
                'gateway_id' => $record->getKey(),
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'general' => 'Failed to update payment gateway. Please check your configuration and try again.',
            ]);
        }
    }

    private function toggleGatewayStatus(): void
    {
        $gateway = $this->getPaymentGateway();
        $newStatus = ! $gateway->is_active;

        // Check if we can activate
        if ($newStatus && ! $gateway->isConfigured()) {
            $this->dangerNotification('Cannot activate: Gateway configuration is incomplete.');

            return;
        }

        $gateway->update(['is_active' => $newStatus]);

        $status = $newStatus ? 'activated' : 'deactivated';
        $this->successNotification("Gateway {$status} successfully!");
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
        $provider = $data['provider'] ?? $this->getPaymentGateway()->provider;
        $settings = $data['settings'] ?? [];

        if ($provider === PaymentGateway::PROVIDER_STRIPE) {
            $this->validateStripeSettings();

            return;
        }

        if ($provider === PaymentGateway::PROVIDER_MOLLIE) {
            $this->validateMollieSettings();

            return;
        }

        if ($provider === PaymentGateway::PROVIDER_PAYPAL) {
            $this->validatePayPalSettings($settings);
        }
    }

    private function validateStripeSettings(): void
    {
        // Stripe doesn't require any specific settings in the settings field
        // The api_key and webhook_secret are separate fields
        // publishable_key and webhook_url are optional
    }

    private function validateMollieSettings(): void
    {
        // Mollie doesn't require any specific settings
        // webhook_url and description_prefix are optional
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validatePayPalSettings(array $settings): void
    {
        // PayPal requires client_id in settings
        if (! isset($settings['client_id']) || empty($settings['client_id'])) {
            throw ValidationException::withMessages([
                'settings.client_id' => 'PayPal requires a Client ID.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isGatewayConfigured(array $data): bool
    {
        // Check if the gateway would be configured with the new data
        $tempGateway = clone $this->getPaymentGateway();
        $tempGateway->fill($data);

        return $tempGateway->isConfigured();
    }

    private function successNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    private function dangerNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    /**
     * Get the payment gateway record with proper typing.
     */
    private function getPaymentGateway(): PaymentGateway
    {
        /** @var PaymentGateway $record */
        $record = $this->record;

        return $record;
    }
}
