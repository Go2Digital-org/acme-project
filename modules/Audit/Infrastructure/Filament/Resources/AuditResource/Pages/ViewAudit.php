<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Audit Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('auditable_type')
                                    ->label('Model Type')
                                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                                    ->badge()
                                    ->color(fn (string $state): string => match (class_basename($state)) {
                                        'Campaign' => 'info',
                                        'Donation' => 'success',
                                        'Organization' => 'warning',
                                        'User' => 'primary',
                                        'PaymentGateway' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('auditable_id')
                                    ->label('Record ID')
                                    ->copyable()
                                    ->copyMessage('ID copied'),

                                TextEntry::make('event')
                                    ->label('Event Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'info',
                                        'deleted' => 'danger',
                                        'restored' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Performed By')
                                    ->default('System'),

                                TextEntry::make('created_at')
                                    ->label('Date & Time')
                                    ->dateTime('Y-m-d H:i:s'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->default('N/A'),

                                TextEntry::make('url')
                                    ->label('URL')
                                    ->default('N/A')
                                    ->limit(50),
                            ]),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->default('N/A')
                            ->limit(100),
                    ]),

                Section::make('Changes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('old_values')
                                    ->label('Old Values'),

                                KeyValueEntry::make('new_values')
                                    ->label('New Values'),
                            ]),
                    ])
                    ->visible(fn ($record): bool => $record->old_values || $record->new_values),

                Section::make('Change Summary')
                    ->schema([
                        TextEntry::make('diff_summary')
                            ->label('')
                            ->formatStateUsing(function ($record): string|HtmlString {
                                if (! $record->old_values && ! $record->new_values) {
                                    return 'No changes recorded';
                                }

                                $changes = $record->diff ?? [];
                                if (empty($changes)) {
                                    return 'No changes to display';
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($changes as $field => $change) {
                                    $html .= '<div class="border dark:border-gray-700 rounded p-3">';
                                    $html .= '<div class="font-medium text-sm mb-2">' . htmlspecialchars($field) . '</div>';
                                    $html .= '<div class="grid grid-cols-2 gap-2 text-xs">';
                                    $oldValue = json_encode($change['old'] ?? null);
                                    $newValue = json_encode($change['new'] ?? null);
                                    $html .= '<div><span class="text-gray-500">Old:</span> <span class="text-red-600 dark:text-red-400">' .
                                             htmlspecialchars($oldValue !== false ? $oldValue : 'null') . '</span></div>';
                                    $html .= '<div><span class="text-gray-500">New:</span> <span class="text-green-600 dark:text-green-400">' .
                                             htmlspecialchars($newValue !== false ? $newValue : 'null') . '</span></div>';
                                    $html .= '</div></div>';
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->html(),
                    ])
                    ->visible(fn ($record): bool => count($record->diff ?? []) > 0),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),

            Action::make('view_model')
                ->label('View Record')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(function (): string {
                    /** @var Audit $audit */
                    $audit = $this->record;
                    $modelClass = $audit->auditable_type;
                    $modelId = $audit->auditable_id;

                    // Map models to their resource edit URLs
                    $resourceMap = [
                        Campaign::class => "/admin/campaigns/{$modelId}/edit",
                        Donation::class => "/admin/donations/{$modelId}/edit",
                        Organization::class => "/admin/organizations/{$modelId}/edit",
                        User::class => "/admin/users/{$modelId}/edit",
                        PaymentGateway::class => "/admin/payment-gateways/{$modelId}/edit",
                    ];

                    return $resourceMap[$modelClass] ?? '#';
                })
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    /** @var Audit $record */
                    $record = $this->record;

                    return $record->auditable !== null;
                }),

            Action::make('restore')
                ->label('Restore to This Version')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restore to Previous Version')
                ->modalDescription('Are you sure you want to restore the record to this version? This will create a new audit entry.')
                ->action(function (): void {
                    /** @var Audit $audit */
                    $audit = $this->record;

                    if ($audit->event === 'deleted') {
                        Notification::make()
                            ->title('Cannot restore from a deletion event.')
                            ->warning()
                            ->send();

                        return;
                    }

                    if ($audit->auditable && $audit->old_values) {
                        foreach ($audit->old_values as $key => $value) {
                            if (in_array($key, $audit->auditable->getFillable())) {
                                $audit->auditable->{$key} = $value;
                            }
                        }

                        $audit->auditable->save();

                        Notification::make()
                            ->title('Record restored to previous version.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Unable to restore record.')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(function (): bool {
                    /** @var Audit $record */
                    $record = $this->record;

                    return $record->auditable !== null && $record->old_values !== null;
                }),
        ];
    }
}
