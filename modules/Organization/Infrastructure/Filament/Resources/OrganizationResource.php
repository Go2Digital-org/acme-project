<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Resources;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Service\AdminUserResolver;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages\CreateOrganization;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages\EditOrganization;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages\ListOrganizations;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages\ViewOrganization;
use Modules\Organization\Infrastructure\Laravel\Job\ProvisionOrganizationTenantJob;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;
use Str;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationLabel = 'Organizations';

    protected static ?string $modelLabel = 'Organization';

    protected static ?string $pluralModelLabel = 'Organizations';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CSR Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Organization Information')
                    ->description('Basic organization details and contact information')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Translate::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Brief description of the organization'),
                                Textarea::make('mission')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Organization\'s mission statement'),
                            ])
                            ->locales(['en', 'nl', 'fr'])
                            ->prefixLocaleLabel()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Organization Status')
                    ->description('Organization status and settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'pending' => 'Pending',
                            ])
                            ->required()
                            ->default('pending')
                            ->helperText('Organization operational status'),
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Unique identifier'),
                    ])
                    ->columns(2),

                Section::make('Contact Information')
                    ->description('Contact details and physical location')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Organization website URL'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText('Primary contact email'),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50)
                            ->helperText('Primary contact phone number'),
                        Textarea::make('address')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Street address'),
                        TextInput::make('city')
                            ->maxLength(100)
                            ->helperText('City'),
                        TextInput::make('state')
                            ->maxLength(100)
                            ->helperText('State/Province'),
                        TextInput::make('postal_code')
                            ->label('Postal Code')
                            ->maxLength(20)
                            ->helperText('Postal/ZIP code'),
                        Select::make('country')
                            ->options([
                                'BE' => 'Belgium',
                                'NL' => 'Netherlands',
                                'FR' => 'France',
                                'DE' => 'Germany',
                                'LU' => 'Luxembourg',
                                'UK' => 'United Kingdom',
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'AU' => 'Australia',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Media & Branding')
                    ->description('Organization logo and visual identity')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Organization Logo')
                            ->image()
                            ->directory('organizations/logos')
                            ->maxSize(2048) // 2MB
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '16:9',
                                '4:3',
                            ])
                            ->helperText('Recommended size: 400x400px (square) or 800x200px (wide)'),
                    ])
                    ->collapsible(),

                Section::make('Tenant Configuration')
                    ->description('Multi-tenant platform settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('subdomain')
                            ->label('Subdomain')
                            ->prefix('https://')
                            ->suffix('.acme-corp-optimy.test')
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(ignoreRecord: true)
                            ->maxLength(63)
                            ->helperText('Lowercase letters, numbers, and hyphens only. Cannot start or end with hyphen.')
                            ->required()
                            ->disabled(fn ($operation, $record): bool => $operation === 'edit' && $record?->provisioning_status === 'active'),
                        Select::make('provisioning_status')
                            ->label('Provisioning Status')
                            ->options([
                                'pending' => 'Pending',
                                'provisioning' => 'Provisioning',
                                'active' => 'Active',
                                'failed' => 'Failed',
                                'suspended' => 'Suspended',
                            ])
                            ->disabled()
                            ->helperText('Current tenant provisioning status'),
                        TextInput::make('database')
                            ->label('Database Name')
                            ->placeholder(fn ($get) => $get('subdomain') ? 'tenant_' . str_replace('-', '_', $get('subdomain')) : 'Auto-generated from subdomain')
                            ->disabled(fn ($operation, $record): bool => $operation === 'edit')
                            ->helperText('Leave empty to auto-generate from subdomain. Only lowercase letters, numbers and underscores.')
                            ->regex('/^[a-z0-9_]*$/')
                            ->maxLength(64),
                        DateTimePicker::make('provisioned_at')
                            ->label('Provisioned At')
                            ->disabled()
                            ->helperText('When the tenant was successfully provisioned'),
                        Textarea::make('provisioning_error')
                            ->label('Provisioning Error')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => $record && $record->provisioning_status === 'failed'),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->helperText('Whether the organization can operate as a tenant')
                            ->default(false),
                        Toggle::make('is_verified')
                            ->label('Is Verified')
                            ->helperText('Whether the organization has been verified')
                            ->default(false),
                        DateTimePicker::make('verification_date')
                            ->label('Verification Date')
                            ->disabled(fn ($record): bool => ! $record || ! $record->is_verified)
                            ->helperText('When the organization was verified'),
                        TextInput::make('registration_number')
                            ->label('Registration Number')
                            ->maxLength(100)
                            ->helperText('Official organization registration number'),
                        TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->maxLength(50)
                            ->helperText('Tax identification number'),
                        Select::make('category')
                            ->options([
                                'ngo' => 'NGO',
                                'charity' => 'Charity',
                                'foundation' => 'Foundation',
                                'non_profit' => 'Non-Profit',
                                'social_enterprise' => 'Social Enterprise',
                                'corporate_csr' => 'Corporate CSR',
                                'government' => 'Government',
                                'educational' => 'Educational',
                                'religious' => 'Religious',
                                'healthcare' => 'Healthcare',
                                'environmental' => 'Environmental',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->helperText('Organization category'),
                        Select::make('type')
                            ->options([
                                '501c3' => '501(c)(3) - Charitable',
                                '501c4' => '501(c)(4) - Social Welfare',
                                '501c6' => '501(c)(6) - Business League',
                                'private_foundation' => 'Private Foundation',
                                'public_charity' => 'Public Charity',
                                'community_foundation' => 'Community Foundation',
                                'corporate_foundation' => 'Corporate Foundation',
                                'government_entity' => 'Government Entity',
                                'international_ngo' => 'International NGO',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->helperText('Legal organization type'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record): bool => ! $record || ! $record->subdomain),

                Section::make('Admin Account')
                    ->description('Super admin account for this organization')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('admin_name')
                            ->label('Admin Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Full name of the super admin')
                            ->visible(fn ($operation): bool => $operation === 'create'),
                        TextInput::make('admin_email')
                            ->label('Admin Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->default(fn ($get) => $get('email'))
                            ->helperText('Email address for the super admin account')
                            ->visible(fn ($operation): bool => $operation === 'create'),
                        TextInput::make('admin_password')
                            ->label('Admin Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Password for the super admin account (min. 8 characters)')
                            ->dehydrated(true)
                            ->visible(fn ($operation): bool => $operation === 'create'),
                    ])
                    ->columns(1)
                    ->visible(fn ($get): bool => $get('subdomain') !== null)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/placeholder.png'),
                TextColumn::make('name')
                    ->state(fn (Organization $record): string => $record->getName())
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('subdomain')
                    ->label('Subdomain')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => $state ? $state . '.acme-corp-optimy.test' : '-')
                    ->copyable()
                    ->copyMessage('Subdomain copied'),
                BadgeColumn::make('provisioning_status')
                    ->label('Tenant Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'info' => 'provisioning',
                        'danger' => 'failed',
                        'secondary' => 'suspended',
                    ])
                    ->icon(fn ($state): ?string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'provisioning' => 'heroicon-o-arrow-path',
                        'failed' => 'heroicon-o-x-circle',
                        'suspended' => 'heroicon-o-pause-circle',
                        default => null,
                    }),
                BadgeColumn::make('status')
                    ->label('Org Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'inactive',
                    ]),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->toggleable(),
                BooleanColumn::make('is_verified')
                    ->label('Verified')
                    ->toggleable(),
                TextColumn::make('campaigns_count')
                    ->label('Campaigns')
                    ->counts('campaigns')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Email copied'),
                TextColumn::make('category')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provisioned_at')
                    ->label('Provisioned')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('provisioning_status')
                    ->label('Tenant Status')
                    ->options([
                        'pending' => 'Pending',
                        'provisioning' => 'Provisioning',
                        'active' => 'Active',
                        'failed' => 'Failed',
                        'suspended' => 'Suspended',
                    ])
                    ->multiple(),
                SelectFilter::make('status')
                    ->label('Organization Status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'inactive' => 'Inactive',
                    ]),
                SelectFilter::make('is_verified')
                    ->label('Verification')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Not Verified',
                    ]),
                SelectFilter::make('category')
                    ->options([
                        'ngo' => 'NGO',
                        'charity' => 'Charity',
                        'foundation' => 'Foundation',
                        'non_profit' => 'Non-Profit',
                        'social_enterprise' => 'Social Enterprise',
                        'corporate_csr' => 'Corporate CSR',
                        'government' => 'Government',
                        'educational' => 'Educational',
                        'religious' => 'Religious',
                        'healthcare' => 'Healthcare',
                        'environmental' => 'Environmental',
                    ])
                    ->multiple(),
                SelectFilter::make('country')
                    ->options([
                        'BE' => 'Belgium',
                        'NL' => 'Netherlands',
                        'FR' => 'France',
                        'DE' => 'Germany',
                        'LU' => 'Luxembourg',
                        'UK' => 'United Kingdom',
                        'US' => 'United States',
                        'CA' => 'Canada',
                        'AU' => 'Australia',
                    ])
                    ->multiple(),
            ])
            ->actions([
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
                Action::make('loginAsTenant')
                    ->label('Login')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->provisioning_status === 'active' && $record->subdomain)
                    ->url(function ($record): ?string {
                        try {
                            // Initialize tenancy temporarily to resolve the correct admin user
                            tenancy()->initialize($record);

                            // Use AdminUserResolver to find or create the appropriate admin
                            $adminResolver = app(AdminUserResolver::class);
                            $adminUser = $adminResolver->resolveAdminUser($record);
                            $userId = (string) $adminUser->id;

                            // Log the resolved user for debugging
                            Log::info('Resolved admin user for impersonation', [
                                'organization_id' => $record->id,
                                'user_id' => $userId,
                                'user_email' => $adminUser->email,
                                'has_spatie_role' => $adminUser->hasRole('super_admin'),
                            ]);

                            // End tenancy before generating token
                            tenancy()->end();

                            // Generate impersonation token using Laravel Tenancy's feature
                            // Redirect to the admin panel (Filament will handle the dashboard route)
                            $token = tenancy()->impersonate($record, $userId, '/admin', 'web'); // @phpstan-ignore-line

                            // Build URL without locale prefix (impersonation route is outside localized routes)
                            $tenantUrl = 'https://' . $record->subdomain . '.acme-corp-optimy.test';

                            return $tenantUrl . '/impersonate/' . $token->token;
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Login Failed')
                                ->body('Could not generate impersonation token: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                BulkAction::make('provision_tenants')
                    ->label('Provision Tenants')
                    ->icon('heroicon-o-server-stack')
                    ->color('primary')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Organization> $records */
                        $records->each(function (Organization $organization): void {
                            if ($organization->subdomain && $organization->provisioning_status === 'pending') {
                                // Dispatch provisioning job
                                ProvisionOrganizationTenantJob::dispatch(
                                    $organization,
                                    [
                                        'name' => 'Admin',
                                        'email' => $organization->email ?? (is_string($organization->name) ? $organization->name : $organization->getName()) . '@example.com',
                                        'password' => Str::random(16),
                                    ]
                                );
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Provision Tenants')
                    ->modalDescription('This will provision tenant databases for organizations with pending status. Continue?')
                    ->modalSubmitActionLabel('Provision')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('activate')
                    ->label('Activate Organizations')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Organization> $records */
                        $records->each(function (Organization $organization): void {
                            $organization->update([
                                'is_active' => true,
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Activate Organizations')
                    ->modalDescription('Are you sure you want to activate the selected organizations?')
                    ->modalSubmitActionLabel('Activate')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Deactivate Organizations')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Organization> $records */
                        $records->each(function (Organization $organization): void {
                            $organization->update([
                                'is_active' => false,
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Organizations')
                    ->modalDescription('Are you sure you want to deactivate the selected organizations? This will prevent them from accessing their tenant.')
                    ->modalSubmitActionLabel('Deactivate')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('verify')
                    ->label('Verify Organizations')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Organization> $records */
                        $records->each(function (Organization $organization): void {
                            $organization->update([
                                'is_verified' => true,
                                'verification_date' => now(),
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Verify Organizations')
                    ->modalDescription('Mark selected organizations as verified?')
                    ->modalSubmitActionLabel('Verify')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRelations(): array
    {
        return [
            // Relations will be added later if needed
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
            'view' => ViewOrganization::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Organization>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('campaigns');
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = Organization::query()
            ->where('provisioning_status', 'pending')
            ->orWhere('provisioning_status', 'failed')
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = Organization::query()
            ->where('provisioning_status', 'failed')
            ->count();

        return $failedCount > 0 ? 'danger' : 'warning';
    }
}
