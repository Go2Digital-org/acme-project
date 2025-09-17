<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource;

class ViewPaymentGateway extends ViewRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Gateway')
                ->icon('heroicon-o-pencil'),

            Action::make('toggle_status')
                ->label(fn (PaymentGateway $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn (PaymentGateway $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (PaymentGateway $record): string => $record->is_active ? 'danger' : 'success')
                ->action(function (PaymentGateway $record): void {
                    $record->is_active ? $record->deactivate() : $record->activate();
                    $record->save();

                    Notification::make()
                        ->title($record->is_active ? 'Gateway activated' : 'Gateway deactivated')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (PaymentGateway $record): bool => $record->isConfigured()),
        ];
    }

    protected function testGatewayConnection(): void
    {
        /** @var PaymentGateway $record */
        $record = $this->record;

        if (! $record->isConfigured()) {
            Notification::make()
                ->title('Gateway is not properly configured')
                ->danger()
                ->send();

            return;
        }

        try {
            // Here you would call the actual gateway API to test the connection
            // For now, we'll simulate it
            Log::info('Testing gateway connection', [
                'gateway' => $record->name,
                'provider' => $record->provider,
            ]);

            // Simulate API call delay
            sleep(1);

            // Randomly succeed or fail for demonstration
            if (random_int(0, 1) === 0) {
                throw new Exception('Connection timeout');
            }

            Notification::make()
                ->title('Connection successful!')
                ->body('The gateway API responded correctly.')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Connection failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
            Log::error('Gateway connection test failed', [
                'gateway' => $record->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function viewGatewayLogs(): void
    {
        // This would fetch and display actual gateway logs
        Notification::make()
            ->title('Logs feature coming soon')
            ->body('This will display payment gateway transaction logs.')
            ->info()
            ->send();
    }
}
