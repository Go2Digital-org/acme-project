<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Audit\Domain\Model\Audit;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

class AuditActivityWidget extends BaseWidget
{
    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()
                    ->with(['auditable', 'user'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Campaign::class => 'info',
                        Donation::class => 'success',
                        Organization::class => 'warning',
                        User::class => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('auditable_id')
                    ->label('ID'),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System'),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->paginated(false)
            ->heading('Recent Activity')
            ->poll('10s');
    }
}
