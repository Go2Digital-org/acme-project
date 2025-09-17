<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\RelationManagers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Domain\Model\Audit;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Audit Trail';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Audit Details')
                    ->schema([
                        TextInput::make('event')
                            ->disabled(),
                        TextInput::make('user.name')
                            ->label('User')
                            ->disabled(),
                        DateTimePicker::make('created_at')
                            ->label('Date & Time')
                            ->disabled(),
                        TextInput::make('ip_address')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Changes')
                    ->schema([
                        KeyValue::make('old_values')
                            ->label('Old Values')
                            ->disabled(),
                        KeyValue::make('new_values')
                            ->label('New Values')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
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

                TextColumn::make('change_count')
                    ->label('Changes')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Audit $record): int => $record->change_count),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return method_exists($ownerRecord, 'audits');
    }
}
