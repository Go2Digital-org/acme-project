<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\ViewComposer;

use Illuminate\View\View;
use Modules\Shared\Application\Service\SocialSharingService;

final readonly class SocialSharingViewComposer
{
    public function __construct(
        private SocialSharingService $socialSharingService,
    ) {}

    public function compose(View $view): void
    {
        $viewData = $view->getData();
        $shareData = $this->resolveShareData($viewData);
        $sharingUrls = $this->generateSharingUrls($shareData);

        $view->with([
            'shareData' => $shareData,
            'sharingUrls' => $sharingUrls,
        ]);
    }

    /**
     * @param  array<string, mixed>  $viewData
     */

    /**
     * @param  array<string, mixed>  $viewData
     * @return array<string, mixed>
     */
    private function resolveShareData(array $viewData): array
    {
        return match (true) {
            isset($viewData['campaign']) => $this->socialSharingService->generateCampaignShareData($viewData['campaign']),
            isset($viewData['page']) => $this->socialSharingService->generatePageShareData($viewData['page']),
            default => $this->socialSharingService->generateCurrentPageShareData(),
        };
    }

    /**
     * @param  array<string, mixed>  $shareData
     * @return array<string, mixed>
     */
    private function generateSharingUrls(array $shareData): array
    {
        return [
            'facebook' => $this->socialSharingService->generateFacebookShareUrl($shareData['url'], $shareData['title']),
            'twitter' => $this->socialSharingService->generateTwitterShareUrl($shareData['url'], $shareData['title'], $shareData['description']),
            'linkedin' => $this->socialSharingService->generateLinkedInShareUrl($shareData['url'], $shareData['title'], $shareData['description']),
            'whatsapp' => $this->socialSharingService->generateWhatsAppShareUrl($shareData['url'], $shareData['title']),
            'email' => $this->socialSharingService->generateEmailShareUrl($shareData['title'], $shareData['description'], $shareData['url']),
            'copy' => $shareData['url'],
        ];
    }
}
