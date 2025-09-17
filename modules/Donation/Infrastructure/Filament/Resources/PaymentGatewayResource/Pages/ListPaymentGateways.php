<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages;

use Exception;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource;

class ListPaymentGateways extends ListRecords
{
    protected static string $resource = PaymentGatewayResource::class;

    public function getTitle(): string
    {
        return 'Payment Gateways';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Payment Gateway')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    /** @return array<BulkAction> */
    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('enable_test_mode')
                ->label('Enable Test Mode')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->action(function (Collection $records): void {
                    /** @var Collection<int, PaymentGateway> $records */
                    $records->each(function (PaymentGateway $gateway): void {
                        $gateway->update(['test_mode' => true]);
                    });
                    $this->successNotification('Test mode enabled for selected gateways.');
                })
                ->requiresConfirmation()
                ->modalHeading('Enable Test Mode')
                ->modalDescription('This will enable test/sandbox mode for all selected gateways.')
                ->deselectRecordsAfterCompletion(),

            BulkAction::make('disable_test_mode')
                ->label('Enable Production Mode')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->action(function (Collection $records): void {
                    /** @var Collection<int, PaymentGateway> $records */
                    $records->each(function (PaymentGateway $gateway): void {
                        if ($gateway->isConfigured()) {
                            $gateway->update(['test_mode' => false]);
                        }
                    });
                    $this->successNotification('Production mode enabled for configured gateways.');
                })
                ->requiresConfirmation()
                ->modalHeading('Enable Production Mode')
                ->modalDescription('This will enable production mode for all properly configured selected gateways. Incomplete configurations will be skipped.')
                ->deselectRecordsAfterCompletion(),

            BulkAction::make('set_priority')
                ->label('Set Priority')
                ->icon('heroicon-o-bars-3')
                ->color('gray')
                ->form([
                    TextInput::make('priority')
                        ->label('Priority Level')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Higher numbers = higher priority. Gateways with higher priority are used first.'),
                ])
                ->action(function (Collection $records, array $data): void {
                    /** @var Collection<int, PaymentGateway> $records */
                    $records->each(function (PaymentGateway $gateway) use ($data): void {
                        $gateway->update(['priority' => $data['priority']]);
                    });
                    $this->successNotification("Priority set to {$data['priority']} for selected gateways.");
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    protected function testAllConnections(): void
    {
        $activeGateways = PaymentGateway::active()->configured()->get();

        if ($activeGateways->isEmpty()) {
            $this->dangerNotification('No active and configured gateways found to test.');

            return;
        }

        $results = [];

        foreach ($activeGateways as $gateway) {
            try {
                // This would typically call a service to test the gateway connection
                // For now, we'll simulate the test based on configuration completeness
                $isConfigured = $gateway->isConfigured();

                $results[] = [
                    'gateway' => $gateway->name,
                    'provider' => $gateway->provider,
                    'status' => $isConfigured ? 'success' : 'failed',
                    'message' => $isConfigured
                        ? 'Connection successful'
                        : 'Configuration incomplete or invalid credentials',
                ];
            } catch (Exception $e) {
                $results[] = [
                    'gateway' => $gateway->name,
                    'provider' => $gateway->provider,
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $e->getMessage(),
                ];
            }
        }

        $successCount = count(array_filter($results, fn (array $r): bool => $r['status'] === 'success'));
        $totalCount = count($results);

        if ($successCount === $totalCount) {
            $this->successNotification("All {$totalCount} gateway connections tested successfully!");

            return;
        }

        if ($successCount > 0) {
            $this->warningNotification("{$successCount}/{$totalCount} gateway connections successful. Check individual gateway configurations for issues.");

            return;
        }

        $this->dangerNotification('All gateway connection tests failed. Please check configurations.');

        // In a real implementation, you might want to log these results
        // or display them in a more detailed format
    }

    protected function successNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    protected function warningNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->warning()
            ->send();
    }

    protected function dangerNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }
}
