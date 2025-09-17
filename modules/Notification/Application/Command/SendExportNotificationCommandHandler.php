<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\LoggerInterface;

final readonly class SendExportNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof SendExportNotificationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = User::find($command->userId);

        if (! $user) {
            $this->logger->warning('User not found for export notification', [
                'user_id' => $command->userId,
                'export_id' => $command->exportId,
            ]);

            return null;
        }

        $notificationData = $this->buildNotificationData($command);

        $notification = new DatabaseNotification;
        $notification->id = Str::uuid()->toString();
        $notification->type = 'App\Notifications\ExportNotification';
        $notification->notifiable_type = $user::class;
        $notification->notifiable_id = (string) $user->id;
        $notification->data = $notificationData;
        $notification->created_at = now();
        $notification->save();

        $this->logger->info('Export notification sent', [
            'user_id' => $command->userId,
            'export_id' => $command->exportId,
            'status' => $command->status,
        ]);

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNotificationData(SendExportNotificationCommand $command): array
    {
        return match ($command->status) {
            SendExportNotificationCommand::STATUS_STARTED => [
                'title' => 'Export Started',
                'message' => sprintf(
                    'Your %s export has started processing. We\'ll notify you when it\'s ready.',
                    $command->exportType
                ),
                'type' => 'info',
                'export_id' => $command->exportId,
                'export_type' => $command->exportType,
                'status' => $command->status,
            ],
            SendExportNotificationCommand::STATUS_COMPLETED => [
                'title' => 'Export Ready',
                'message' => sprintf(
                    'Your %s export is ready! %s records have been exported.',
                    $command->exportType,
                    $command->recordCount ?? 0
                ),
                'type' => 'success',
                'action_url' => $command->downloadUrl,
                'export_id' => $command->exportId,
                'export_type' => $command->exportType,
                'file_name' => $command->fileName,
                'record_count' => $command->recordCount,
                'status' => $command->status,
            ],
            SendExportNotificationCommand::STATUS_FAILED => [
                'title' => 'Export Failed',
                'message' => sprintf(
                    'Your %s export failed. %s',
                    $command->exportType,
                    $command->errorMessage ?? 'Please try again later.'
                ),
                'type' => 'error',
                'export_id' => $command->exportId,
                'export_type' => $command->exportType,
                'error_message' => $command->errorMessage,
                'status' => $command->status,
            ],
            default => [
                'title' => 'Export Update',
                'message' => sprintf('Export status: %s', $command->status),
                'type' => 'info',
                'export_id' => $command->exportId,
                'status' => $command->status,
            ],
        };
    }
}
