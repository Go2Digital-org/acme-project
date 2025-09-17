<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\View\View;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;

final readonly class ShowPageController
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
    ) {}

    public function __invoke(string $slug): View
    {
        $page = $this->pageRepository->findBySlug($slug);

        if (! $page instanceof Page || ! $page->isPublished()) {
            abort(404);
        }

        return view('page', ['page' => $page]);
    }
}
