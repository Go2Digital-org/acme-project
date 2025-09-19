<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\BookmarkResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<BookmarkResource, BookmarkResource>
 */
final readonly class RemoveBookmarkProcessor implements ProcessorInterface
{
    public function __construct(
        private BookmarkService $bookmarkService,
        private Request $request,
    ) {}

    /**
     * @param  BookmarkResource  $data
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): BookmarkResource {
        $user = $this->request->user();

        if (! $user) {
            throw new UnauthorizedHttpException('', 'Authentication required');
        }

        $campaignId = (int) $uriVariables['id'];

        // Check if campaign exists
        $campaign = Campaign::find($campaignId);

        if (! $campaign) {
            throw new NotFoundHttpException('Campaign not found');
        }

        // Remove bookmark (idempotent operation - succeeds even if bookmark doesn't exist)
        $this->bookmarkService->removeBookmark($user->id, $campaignId);

        $bookmarkCount = $this->bookmarkService->getBookmarkCount($campaignId);

        return BookmarkResource::bookmarkAction(
            campaignId: $campaignId,
            wasBookmarked: false,
            bookmarkCount: $bookmarkCount,
            message: 'Bookmark removed successfully',
        );
    }
}
