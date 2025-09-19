<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

interface GetNotificationMetricsQueryHandlerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function handle(GetNotificationMetricsQuery $query): array;
}
