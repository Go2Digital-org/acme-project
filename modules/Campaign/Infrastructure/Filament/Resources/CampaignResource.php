<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Filament\Resources;

use DB;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Log;
use Modules\Audit\Infrastructure\Filament\RelationManagers\AuditsRelationManager;
use Modules\Campaign\Application\Command\ApproveCampaignCommand;
use Modules\Campaign\Application\Command\ApproveCampaignCommandHandler;
use Modules\Campaign\Application\Command\RejectCampaignCommand;
use Modules\Campaign\Application\Command\RejectCampaignCommandHandler;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource\Pages\EditCampaign;
use Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource\Pages\ViewCampaign;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('campaigns.campaigns');
    }

    public static function getModelLabel(): string
    {
        return __('campaigns.campaign');
    }

    public static function getPluralModelLabel(): string
    {
        return __('campaigns.campaigns');
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.csr_management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('campaigns.campaign_information'))
                    ->description(__('campaigns.campaign_information_description'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Translate::make()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->required()
                                    ->rows(4),
                            ])
                            ->locales(['en', 'nl', 'fr'])
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('campaigns.slug_help')),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('categoryModel', 'name->en')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('campaigns.category_help')),
                        Select::make('visibility')
                            ->options([
                                'public' => __('campaigns.visibility_public'),
                                'internal' => __('campaigns.visibility_internal'),
                                'private' => __('campaigns.visibility_private'),
                            ])
                            ->default('public')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('campaigns.financial_details'))
                    ->description(__('campaigns.financial_details_description'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('goal_amount')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->minValue(100)
                            ->maxValue(1000000)
                            ->helperText(__('campaigns.goal_amount_help')),
                        TextInput::make('current_amount')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->readOnly()
                            ->helperText(__('campaigns.current_amount_help')),
                        Toggle::make('has_corporate_matching')
                            ->label(__('campaigns.enable_corporate_matching'))
                            ->live()
                            ->helperText(__('campaigns.corporate_matching_help')),
                        TextInput::make('corporate_matching_rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(200)
                            ->default(100)
                            ->visible(fn ($get) => $get('has_corporate_matching'))
                            ->helperText(__('campaigns.corporate_matching_rate_help')),
                        TextInput::make('max_corporate_matching')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->visible(fn ($get) => $get('has_corporate_matching'))
                            ->helperText(__('campaigns.max_corporate_matching_help')),
                    ])
                    ->columns(2),

                Section::make(__('campaigns.timeline_organization'))
                    ->description(__('campaigns.timeline_organization_description'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        DateTimePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->minDate(now()->subDays(1)),
                        DateTimePicker::make('end_date')
                            ->required()
                            ->after('start_date')
                            ->minDate(fn ($get) => $get('start_date') ?? now()),
                        Select::make('organization_id')
                            ->relationship(
                                'organization',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('status', 'active')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName())
                            ->searchable(['name->en', 'name->nl', 'name->fr'])
                            ->preload()
                            ->required()
                            ->helperText(__('campaigns.beneficiary_organization_help')),
                        Select::make('user_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('campaigns.campaign_creator_help')),
                        Select::make('status')
                            ->options([
                                'draft' => __('campaigns.status_draft'),
                                'active' => __('campaigns.status_active'),
                                'paused' => __('campaigns.status_paused'),
                                'completed' => __('campaigns.status_completed'),
                                'cancelled' => __('campaigns.status_cancelled'),
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('campaigns.media_assets'))
                    ->description(__('campaigns.media_assets_description'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        FileUpload::make('featured_image')
                            ->image()
                            ->directory('campaigns')
                            ->maxSize(5120) // 5MB
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->helperText(__('campaigns.featured_image_help')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('title')
                    ->getStateUsing(fn (Campaign $record): string => $record->getTitle())
                    ->searchable()
                    ->sortable()
                    ->size('lg')
                    ->color('success')
                    ->limit(40)
                    ->tooltip(fn (Campaign $record): string => $record->getTitle()),
                BadgeColumn::make('categoryModel.name')
                    ->label('Category')
                    ->getStateUsing(fn (Campaign $record): string => $record->categoryModel ? $record->categoryModel->getName() : 'Unknown')
                    ->color(fn (Campaign $record): string => $record->categoryModel?->color ? 'primary' : 'secondary')
                    ->tooltip(fn (Campaign $record): ?string => $record->categoryModel?->getDescription()),
                BadgeColumn::make('status')
                    ->getStateUsing(fn (Campaign $record): string => ucfirst(str_replace('_', ' ', $record->status->value)))
                    ->color(fn (Campaign $record): string => match ($record->status->value) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'rejected', 'cancelled' => 'danger',
                        'active' => 'success',
                        'paused' => 'info',
                        'completed' => 'primary',
                        default => 'secondary'
                    }),
                TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function (Campaign $record): string {
                        $current = number_format((float) $record->current_amount, 0, ',', '.');
                        $goal = number_format((float) $record->goal_amount, 0, ',', '.');
                        $percentage = number_format($record->getProgressPercentage(), 0);

                        return "€{$current} / €{$goal} ({$percentage}%)";
                    })
                    ->color(fn (Campaign $record): string => $record->getProgressPercentage() >= 100 ? 'success' : 'primary')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('current_amount', $direction)),
                TextColumn::make('donations_count')
                    ->label('Donations')
                    ->counts('donations')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_date')
                    ->label('Ends')
                    ->date('M j')
                    ->sortable()
                    ->color(
                        fn (Campaign $record): string => $record->end_date?->isPast() ? 'danger' :
                        ($record->end_date?->isBefore(now()->addDays(7)) ? 'warning' : 'primary'),
                    )
                    ->description(fn (Campaign $record): string => $record->end_date?->diffForHumans() ?? 'No end date'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'draft' => __('campaigns.status_draft'),
                        'pending_approval' => __('campaigns.status_pending_approval'),
                        'active' => __('campaigns.status_active'),
                        'paused' => __('campaigns.status_paused'),
                        'completed' => __('campaigns.status_completed'),
                        'cancelled' => __('campaigns.status_cancelled'),
                        'rejected' => __('campaigns.status_rejected'),
                    ])
                    ->multiple(),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('categoryModel', 'name->en')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('organization')
                    ->relationship(
                        'organization',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('status', 'active')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                    Action::make('approve')
                        ->label(__('campaigns.approve'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Campaign $record): void {
                            $commandHandler = app(ApproveCampaignCommandHandler::class);
                            $command = new ApproveCampaignCommand(
                                campaignId: $record->id,
                                approverId: (int) auth()->id()
                            );
                            $commandHandler->handle($command);
                        })
                        ->requiresConfirmation()
                        ->modalDescription(__('campaigns.approve_modal_description'))
                        ->visible(fn (Campaign $record): bool => $record->status === CampaignStatus::PENDING_APPROVAL &&
                            (auth()->user()?->hasRole('super_admin') ?? false)
                        ),
                    Action::make('reject')
                        ->label(__('campaigns.reject'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label(__('campaigns.rejection_reason'))
                                ->required()
                                ->minLength(10)
                                ->helperText(__('campaigns.rejection_reason_help')),
                        ])
                        ->action(function (Campaign $record, array $data): void {
                            $commandHandler = app(RejectCampaignCommandHandler::class);
                            $command = new RejectCampaignCommand(
                                campaignId: $record->id,
                                rejecterId: (int) auth()->id(),
                                rejectionReason: $data['rejection_reason']
                            );
                            $commandHandler->handle($command);
                        })
                        ->requiresConfirmation()
                        ->modalDescription(__('campaigns.reject_modal_description'))
                        ->visible(fn (Campaign $record): bool => $record->status === CampaignStatus::PENDING_APPROVAL &&
                            (auth()->user()?->hasRole('super_admin') ?? false)
                        ),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('secondary')
                    ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                BulkAction::make('submit_for_approval')
                    ->label(__('campaigns.submit_for_approval'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status === CampaignStatus::DRAFT || $campaign->status === CampaignStatus::REJECTED) {
                                $campaign->update([
                                    'status' => CampaignStatus::PENDING_APPROVAL,
                                    'submitted_for_approval_at' => now(),
                                ]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.submit_approval_modal_description'))
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => ! (auth()->user()?->hasRole('super_admin') ?? false)),
                BulkAction::make('approve')
                    ->label(__('campaigns.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status === CampaignStatus::PENDING_APPROVAL) {
                                $campaign->update([
                                    'status' => CampaignStatus::ACTIVE,
                                    'approved_by' => (int) auth()->id(),
                                    'approved_at' => now(),
                                    'rejected_by' => null,
                                    'rejected_at' => null,
                                    'rejection_reason' => null,
                                ]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.bulk_approve_modal_description'))
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => (auth()->user()?->hasRole('super_admin') ?? false)),
                BulkAction::make('reject')
                    ->label(__('campaigns.reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label(__('campaigns.rejection_reason'))
                            ->required()
                            ->minLength(10)
                            ->helperText(__('campaigns.rejection_reason_help')),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key) use ($data): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status === CampaignStatus::PENDING_APPROVAL) {
                                $campaign->update([
                                    'status' => CampaignStatus::REJECTED,
                                    'rejected_by' => (int) auth()->id(),
                                    'rejected_at' => now(),
                                    'rejection_reason' => $data['rejection_reason'],
                                    'approved_by' => null,
                                    'approved_at' => null,
                                ]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.bulk_reject_modal_description'))
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => (auth()->user()?->hasRole('super_admin') ?? false)),
                BulkAction::make('pause')
                    ->label(__('campaigns.pause'))
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status->canTransitionTo(CampaignStatus::PAUSED)) {
                                $campaign->update(['status' => CampaignStatus::PAUSED]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.pause_modal_description'))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('complete')
                    ->label(__('campaigns.complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status->canTransitionTo(CampaignStatus::COMPLETED)) {
                                $campaign->markAsCompleted();
                                $campaign->save();
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.complete_modal_description'))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('cancel')
                    ->label(__('campaigns.cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Campaign> $records */
                        $records->each(function (Model $campaign, int|string $key): void {
                            /** @var Campaign $campaign */
                            if ($campaign->status->canTransitionTo(CampaignStatus::CANCELLED)) {
                                $campaign->update(['status' => CampaignStatus::CANCELLED]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('campaigns.cancel_modal_description'))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->deferLoading();
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRelations(): array
    {
        return [
            'audits' => AuditsRelationManager::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
            'view' => ViewCampaign::route('/{record}'),
        ];
    }

    /** @return Builder<Campaign> */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['organization', 'employee', 'categoryModel']);
        // Note: donations_count is now a column, no need for withCount

        // Debug: Log which database connection is being used
        $connection = $query->getConnection();
        Log::info('CampaignResource query', [
            'database' => DB::getDatabaseName(),
            'connection' => method_exists($connection, 'getName') ? $connection->getName() : 'unknown',
            'tenant' => tenancy()->tenant && method_exists(tenancy()->tenant, 'getTenantKey')
                ? tenancy()->tenant->getTenantKey() : null,
        ]);

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $activeCount = DB::table('campaigns')->where('status', CampaignStatus::ACTIVE)->count();

        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
