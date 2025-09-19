<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Pages\ListAudits;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Pages\ViewAudit;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?int $navigationSort = 100;

    protected static ?string $recordTitleAttribute = 'event';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'Audit';

    protected static ?string $pluralModelLabel = 'Audits';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Audit> $model */
        $model = static::getModel();
        $count = $model::query()->recent(1)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        /** @var class-string<Audit> $model */
        $model = static::getModel();
        $count = $model::query()->recent(1)->count();

        return $count > 10 ? 'warning' : 'success';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'Campaign' => 'info',
                        'Donation' => 'success',
                        'Organization' => 'warning',
                        'User' => 'primary',
                        'PaymentGateway' => 'danger',
                        'Category' => 'secondary',
                        'Currency' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('auditable_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copied'),

                TextColumn::make('event')
                    ->label('Event')
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
                    ->searchable()
                    ->default('System'),

                TextColumn::make('change_count')
                    ->label('Changes')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Audit $record): int => $record->change_count),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('auditable_type')
                    ->label('Model Type')
                    ->options(fn () => Audit::query()
                        ->distinct()
                        ->pluck('auditable_type')
                        ->mapWithKeys(fn ($type): array => [$type => class_basename($type)])
                        ->toArray())
                    ->searchable(),

                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('10s');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Audit>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['auditable', 'user']);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['auditable_type', 'auditable_id', 'event', 'user.name'];
    }
}
