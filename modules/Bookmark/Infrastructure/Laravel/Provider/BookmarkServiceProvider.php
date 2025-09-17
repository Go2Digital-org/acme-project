<?php

declare(strict_types=1);

namespace Modules\Bookmark\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Bookmark\Application\Command\CreateBookmarkCommandHandler;
use Modules\Bookmark\Application\Command\OrganizeBookmarksCommandHandler;
use Modules\Bookmark\Application\Command\RemoveBookmarkCommandHandler;
use Modules\Bookmark\Application\Command\ToggleBookmarkCommandHandler;
use Modules\Bookmark\Application\Query\CheckBookmarkStatusQueryHandler;
use Modules\Bookmark\Application\Query\GetBookmarksByEntityQueryHandler;
use Modules\Bookmark\Application\Query\GetBookmarkStatsQueryHandler;
use Modules\Bookmark\Application\Query\GetPopularCampaignsQueryHandler;
use Modules\Bookmark\Application\Query\GetUserBookmarksQueryHandler;
use Modules\Bookmark\Application\Service\BookmarkService;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;
use Modules\Bookmark\Infrastructure\Laravel\Repository\BookmarkEloquentRepository;

/**
 * Service provider for the Bookmark module.
 */
final class BookmarkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BookmarkRepositoryInterface::class,
            BookmarkEloquentRepository::class
        );

        $this->app->singleton(CreateBookmarkCommandHandler::class);
        $this->app->singleton(RemoveBookmarkCommandHandler::class);
        $this->app->singleton(ToggleBookmarkCommandHandler::class);
        $this->app->singleton(OrganizeBookmarksCommandHandler::class);

        $this->app->singleton(GetUserBookmarksQueryHandler::class);
        $this->app->singleton(CheckBookmarkStatusQueryHandler::class);
        $this->app->singleton(GetBookmarksByEntityQueryHandler::class);
        $this->app->singleton(GetBookmarkStatsQueryHandler::class);
        $this->app->singleton(GetPopularCampaignsQueryHandler::class);

        $this->app->singleton(BookmarkService::class);
    }
}
