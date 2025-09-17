<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'View Organization';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loginAsTenant')
                ->label('Login as Tenant Admin')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('info')
                ->visible(fn ($record): bool => $record->provisioning_status === 'active' && $record->subdomain)
                ->requiresConfirmation()
                ->modalHeading('Login to Tenant Admin Panel')
                ->modalDescription('You will be logged in as a super admin on the tenant domain. The session will be temporary.')
                ->modalSubmitActionLabel('Login to Tenant')
                ->action(function ($record) {
                    try {
                        // We'll use a default user ID (1) for super admin
                        // The impersonation route will handle finding/creating the actual user
                        $userId = '1'; // Default super admin ID

                        // Generate impersonation token using Laravel Tenancy's feature
                        // This doesn't require tenancy to be initialized
                        // Redirect to the admin panel (no locale prefix for admin routes)
                        $token = tenancy()->impersonate($record, $userId, '/admin', 'web'); // @phpstan-ignore-line

                        // Build URL with locale prefix and redirect
                        $tenantUrl = 'https://' . $record->subdomain . '.acme-corp-optimy.test';
                        $impersonateUrl = $tenantUrl . '/en/impersonate/' . $token->token;

                        return redirect()->away($impersonateUrl);
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Login Failed')
                            ->body('Could not generate impersonation token: ' . $e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }
                }),
            EditAction::make()
                ->icon('heroicon-o-pencil'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
