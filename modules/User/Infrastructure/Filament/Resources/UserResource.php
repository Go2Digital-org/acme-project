<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Filament\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use DB;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Currency\Domain\ValueObject\Currency;
use Modules\User\Infrastructure\Filament\Pages\CreateUser;
use Modules\User\Infrastructure\Filament\Pages\EditUser;
use Modules\User\Infrastructure\Filament\Pages\ListUsers;
use Modules\User\Infrastructure\Laravel\Models\User;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'User Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('User Information')
                    ->description('Basic user account information')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),

                Section::make('Preferences')
                    ->description('User preferences and settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('preferences.currency')
                            ->label('Preferred Currency')
                            ->options(function () {
                                $currencies = Currency::getAvailableCurrenciesData();

                                return collect($currencies)->mapWithKeys(function (array $currency): array {
                                    $code = isset($currency['code']) ? (string) $currency['code'] : 'USD';
                                    $flag = isset($currency['flag']) ? (string) $currency['flag'] : '💰';
                                    $name = isset($currency['name']) ? (string) $currency['name'] : 'Unknown';

                                    return [$code => $flag . ' ' . $code . ' - ' . $name];
                                })->toArray();
                            })
                            ->default('EUR')
                            ->searchable()
                            ->helperText('Select the preferred currency for display'),
                        Select::make('preferences.language')
                            ->label('Preferred Language')
                            ->options([
                                'en' => '🇬🇧 English',
                                'nl' => '🇳🇱 Nederlands',
                                'fr' => '🇫🇷 Français',
                            ])
                            ->default('en')
                            ->searchable()
                            ->helperText('Select the preferred language for the interface'),
                    ])
                    ->columns(2),

                Section::make('Security')
                    ->description('Account security and authentication settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state): ?string => filled($state) ? bcrypt((string) $state) : null)
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->label(fn (string $context): string => $context === 'create' ? 'Password' : 'New Password')
                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Leave blank to keep current password' : ''),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied'),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', ')
                    ->searchable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                Impersonate::make()
                    ->label(''),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                BulkAction::make('verify_email')
                    ->label('Verify Email')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, User> $records */
                        $records->each(function (Model $user, int|string $key): void {
                            /** @var User $user */
                            if (! $user->email_verified_at) {
                                $user->update(['email_verified_at' => now()]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Verify Email Addresses')
                    ->modalDescription('Are you sure you want to verify the email addresses for the selected users?')
                    ->modalSubmitActionLabel('Verify Emails')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('unverify_email')
                    ->label('Unverify Email')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, User> $records */
                        $records->each(function (Model $user, int|string $key): void {
                            /** @var User $user */
                            if ($user->email_verified_at) {
                                $user->update(['email_verified_at' => null]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Unverify Email Addresses')
                    ->modalDescription('Are you sure you want to unverify the email addresses for the selected users? This will require them to verify their email again.')
                    ->modalSubmitActionLabel('Unverify Emails')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DB::table('users')->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the permissions required to access this resource.
     */
    /**
     * @return array<int, string>
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',
        ];
    }
}
