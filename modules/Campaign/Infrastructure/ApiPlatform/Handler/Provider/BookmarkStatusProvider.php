<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\BookmarkResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProviderInterface<BookmarkResource>
 */
final readonly class BookmarkStatusProvider implements ProviderInterface
{
    public function __construct(
        private BookmarkService $bookmarkService,
        private Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function provide(
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

        $isBookmarked = $this->bookmarkService->isBookmarked($user->id, $campaignId);
        $bookmarkCount = $this->bookmarkService->getBookmarkCount($campaignId);

        return BookmarkResource::bookmarkStatus(
            campaignId: $campaignId,
            isBookmarked: $isBookmarked,
            bookmarkCount: $bookmarkCount,
        );
    }
}
