<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Filament\DisabledWidgets;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

class PendingCampaignApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Campaigns Pending Approval';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Campaign::query()
                    ->where('status', CampaignStatus::PENDING_APPROVAL->value)
                    ->with(['employee', 'organization', 'categoryModel'])
                    ->orderBy('updated_at', 'asc')
            )
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('employee.name')
                    ->label('Submitted By')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable(),
                TextColumn::make('goal_amount')
                    ->label('Goal')
                    ->money('EUR'),
                TextColumn::make('updated_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                BadgeColumn::make('days_waiting')
                    ->label('Days Waiting')
                    ->state(fn (Campaign $record): string => (string) now()->diffInDays($record->updated_at)
                    )
                    ->color(fn (Campaign $record): string => now()->diffInDays($record->updated_at) > 3 ? 'danger' : 'warning'
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Campaign $record): string => route('filament.admin.resources.campaigns.view', $record)),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to approve this campaign?')
                    ->action(function (Campaign $record): void {
                        $record->update([
                            'status' => CampaignStatus::ACTIVE->value,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->minLength(10)
                            ->helperText('Please provide a clear reason for rejection'),
                    ])
                    ->action(function (Campaign $record, array $data): void {
                        $record->update([
                            'status' => CampaignStatus::REJECTED->value,
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No Pending Approvals')
            ->emptyStateDescription('There are no campaigns waiting for approval.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
