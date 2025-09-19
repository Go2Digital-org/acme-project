<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Special;

use Illuminate\View\Component;
use Illuminate\View\View;
use Modules\Shared\Domain\Contract\CampaignInterface;

final class ShareModal extends Component
{
    public string $campaignUrl;

    public string $campaignTitle;

    public string $shareText;

    /** @var array<string, mixed> */
    public array $platformUrls;

    public ?string $qrCodeUrl;

    /**
     * @param  array<string, mixed>  $sharingData
     */
    public function __construct(
        public ?CampaignInterface $campaign = null,
        /** @var array<string, mixed>|null */
        public ?array $sharingData = null,
        public bool $show = false,
        public string $id = 'share-modal',
    ) {
        $this->prepareSharingData();
    }

    public function render(): View
    {
        return view('components.share-modal');
    }

    private function prepareSharingData(): void
    {
        // Use service data if available, fallback to basic values
        $sharing = $this->sharingData ?? [];

        $this->campaignUrl = $sharing['url'] ?? url()->current();
        $this->campaignTitle = $sharing['title'] ?? ($this->campaign instanceof CampaignInterface ? $this->campaign->getTitle() : 'Campaign');
        $this->shareText = $sharing['text'] ?? __('common.share_text', ['title' => $this->campaignTitle]);

        // Prepare platform URLs
        $this->platformUrls = [
            'facebook' => $sharing['platforms']['facebook']['url'] ?? $this->generateFacebookUrl(),
            'twitter' => $sharing['platforms']['twitter']['url'] ?? $this->generateTwitterUrl(),
            'linkedin' => $sharing['platforms']['linkedin']['url'] ?? $this->generateLinkedinUrl(),
            'whatsapp' => $sharing['platforms']['whatsapp']['url'] ?? $this->generateWhatsappUrl(),
            'email' => $sharing['platforms']['email']['url'] ?? $this->generateEmailUrl(),
        ];

        $this->qrCodeUrl = $sharing['qr_code']['url'] ?? $this->generateQrCodeUrl();
    }

    private function generateFacebookUrl(): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?' . http_build_query([
            'u' => $this->campaignUrl,
            'quote' => $this->shareText,
        ]);
    }

    private function generateTwitterUrl(): string
    {
        return 'https://twitter.com/intent/tweet?' . http_build_query([
            'url' => $this->campaignUrl,
            'text' => $this->shareText,
        ]);
    }

    private function generateLinkedinUrl(): string
    {
        return 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query([
            'url' => $this->campaignUrl,
        ]);
    }

    private function generateWhatsappUrl(): string
    {
        return 'https://wa.me/?' . http_build_query([
            'text' => $this->shareText . ' ' . $this->campaignUrl,
        ]);
    }

    private function generateEmailUrl(): string
    {
        return 'mailto:?' . http_build_query([
            'subject' => $this->campaignTitle,
            'body' => $this->shareText . "\n\n" . $this->campaignUrl,
        ]);
    }

    private function generateQrCodeUrl(): string
    {
        // Using a public QR code service - in production, you might want to use your own service
        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => '128x128',
            'data' => $this->campaignUrl,
            'format' => 'png',
            'margin' => 10,
        ]);
    }
}
