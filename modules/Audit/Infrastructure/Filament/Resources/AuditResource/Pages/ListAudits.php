<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets\AuditActivityWidget;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets\AuditStatsWidget;
use RuntimeException;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_all')
                ->label('Export All')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => response()->streamDownload(function (): void {
                    $csv = fopen('php://output', 'w');
                    if ($csv === false) {
                        throw new RuntimeException('Unable to open output stream for CSV export.');
                    }
                    fputcsv($csv, ['Model', 'ID', 'Event', 'User', 'IP', 'Date', 'Old Values', 'New Values']);

                    $query = $this->getFilteredTableQuery();
                    if (! $query instanceof Builder) {
                        throw new RuntimeException('Unable to retrieve filtered table query.');
                    }
                    $audits = $query->get();

                    /** @var Audit $audit */
                    foreach ($audits as $audit) {
                        fputcsv($csv, [
                            class_basename($audit->auditable_type),
                            $audit->auditable_id,
                            $audit->event,
                            $audit->user->name ?? 'System',
                            $audit->ip_address,
                            $audit->created_at->format('Y-m-d H:i:s'),
                            json_encode($audit->old_values),
                            json_encode($audit->new_values),
                        ]);
                    }

                    fclose($csv);
                }, 'audit-export-' . now()->format('Y-m-d-His') . '.csv')),

            Action::make('cleanup')
                ->label('Cleanup Old Audits')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cleanup Old Audits')
                ->modalDescription('This will permanently delete all audit records older than 365 days. This action cannot be undone.')
                ->action(function (): void {
                    $deleted = Audit::where('created_at', '<', now()->subDays(365))->delete();

                    Notification::make()
                        ->title('Success')
                        ->body("Deleted {$deleted} old audit records.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(fn () => $this->getModel()::count()),

            'today' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count())
                ->badgeColor('success'),

            'this_week' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfWeek()))
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->startOfWeek())->count()),

            'created' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('event', 'created'))
                ->badge(fn () => $this->getModel()::where('event', 'created')->count())
                ->badgeColor('success'),

            'updated' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('event', 'updated'))
                ->badge(fn () => $this->getModel()::where('event', 'updated')->count())
                ->badgeColor('info'),

            'deleted' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('event', 'deleted'))
                ->badge(fn () => $this->getModel()::where('event', 'deleted')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AuditStatsWidget::class,
            AuditActivityWidget::class,
        ];
    }
}
