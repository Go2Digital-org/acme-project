<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class SocialSharingService
{
    /**
     * Generate complete sharing data for a campaign or content.
     *
     * @return array<string, mixed>
     */
    public function generateSharingData(
        string $url,
        string $title,
        string $description = '',
        ?string $imageUrl = null,
    ): array {
        $shareText = $this->generateShareText($title, $description);

        return [
            'urls' => $this->generateSharingUrls($url, $title, $shareText, $imageUrl),
            'qr_code' => $this->generateQrCodeData($url),
            'meta_data' => [
                'title' => $title,
                'description' => $this->sanitizeDescription($description),
                'url' => $url,
                'image_url' => $imageUrl,
                'share_text' => $shareText,
            ],
        ];
    }

    /**
     * Generate social media sharing URLs.
     *
     * @return array<string, string>
     */
    public function generateSharingUrls(
        string $url,
        string $title,
        string $shareText,
        ?string $imageUrl = null,
    ): array {
        $urls = [
            'facebook' => $this->generateFacebookUrl($url, $shareText),
            'twitter' => $this->generateTwitterUrl($url, $shareText),
            'linkedin' => $this->generateLinkedInUrl($url, $title, $shareText),
            'whatsapp' => $this->generateWhatsAppUrl($url, $shareText),
            'telegram' => $this->generateTelegramUrl($url, $shareText),
            'email' => $this->generateEmailUrl($title, $shareText, $url),
            'reddit' => $this->generateRedditUrl($url, $title),
        ];

        if ($imageUrl !== null) {
            $urls['pinterest'] = $this->generatePinterestUrl($url, $title, $imageUrl);
        }

        return $urls;
    }

    /**
     * Generate QR code data for sharing.
     *
     * @return array<string, mixed>
     */
    public function generateQrCodeData(
        string $url,
        int $size = 200,
        string $format = 'png',
        int $margin = 10,
    ): array {
        return [
            'url' => $this->generateQrCodeUrl($url, $size, $format, $margin),
            'download_url' => $this->generateQrCodeDownloadUrl($url, $size, $format, $margin),
            'data_url' => $url,
            'size' => $size,
            'format' => $format,
            'margin' => $margin,
        ];
    }

    /**
     * Generate campaign share data.
     *
     * @return array<string, mixed>
     */
    public function generateCampaignShareData(object $campaign): array
    {
        // Check if it's an Eloquent model
        $isEloquentModel = $campaign instanceof Model;

        // Get title with proper checks for Eloquent models
        $title = null;

        if ($isEloquentModel) {
            /** @var Model $campaign */
            // Use getTitle() method if available for translated fields
            if (method_exists($campaign, 'getTitle')) {
                $title = $campaign->getTitle();
            } else {
                $titleValue = $campaign->getAttribute('title');
                $title = is_array($titleValue) ? ($titleValue['en'] ?? null) : $titleValue;
            }
        } elseif (property_exists($campaign, 'title') && isset($campaign->title)) {
            $titleValue = $campaign->title;
            $title = is_array($titleValue) ? ($titleValue['en'] ?? null) : $titleValue;
        }

        if (! $title) {
            throw new InvalidArgumentException('Campaign object must have a title property');
        }

        $url = route('campaigns.show', $campaign);

        // Get description with proper checks
        $description = '';

        if ($isEloquentModel) {
            /** @var Model $campaign */
            // Use getDescription() method if available for translated fields
            if (method_exists($campaign, 'getDescription')) {
                $description = (string) $campaign->getDescription();
            } else {
                $descValue = $campaign->getAttribute('description');
                $description = is_array($descValue) ? (string) ($descValue['en'] ?? '') : (string) ($descValue ?? '');
            }
        } elseif (property_exists($campaign, 'description') && isset($campaign->description)) {
            $descValue = $campaign->description;
            $description = is_array($descValue) ? (string) ($descValue['en'] ?? '') : (string) $descValue;
        }

        // Get image URL with proper checks
        $imageUrl = null;

        if ($isEloquentModel) {
            /** @var Model $campaign */
            // Check for different possible image properties
            $imageUrl = $campaign->getAttribute('featured_image') ?? $campaign->getAttribute('image_url') ?? null;
        } elseif (property_exists($campaign, 'image_url') && isset($campaign->image_url)) {
            $imageUrl = $campaign->image_url ?? null;
        }

        return [
            'url' => $url,
            'title' => $title,
            'description' => $this->sanitizeDescription($description, 160),
            'image' => $imageUrl,
        ];
    }

    /**
     * Generate page share data.
     *
     * @return array<string, mixed>
     */
    public function generatePageShareData(object $page): array
    {
        // Check if it's an Eloquent model
        $isEloquentModel = $page instanceof Model;

        // Get slug and title with proper checks
        $slug = null;
        $title = null;

        if ($isEloquentModel) {
            /** @var Model $page */
            $slug = $page->getAttribute('slug') ?? null;
            // For Page models with translations, use getTranslation method
            if (method_exists($page, 'getTranslation')) {
                $title = $page->getTranslation('title') ?? null;
            } else {
                $title = $page->getAttribute('title') ?? null;
            }
        } else {
            if (property_exists($page, 'slug') && isset($page->slug)) {
                $slug = $page->slug;
            }

            if (property_exists($page, 'title') && isset($page->title)) {
                $title = $page->title;
            }
        }

        if (! $slug || ! $title) {
            throw new InvalidArgumentException('Page object must have slug and title properties');
        }

        $url = route('page', $slug);

        // Get meta description and content with proper checks
        $metaDesc = '';
        $content = '';

        if ($isEloquentModel) {
            /** @var Model $page */
            // For Page models with translations, use getTranslation method
            if (method_exists($page, 'getTranslation')) {
                $metaDesc = '';
                $content = (string) ($page->getTranslation('content') ?? '');
            } else {
                $metaDesc = (string) ($page->getAttribute('meta_description') ?? '');
                $content = (string) ($page->getAttribute('content') ?? '');
            }
        } else {
            if (property_exists($page, 'meta_description') && isset($page->meta_description)) {
                $metaDesc = (string) ($page->meta_description ?? '');
            }

            if (property_exists($page, 'content') && isset($page->content)) {
                $content = (string) ($page->content ?? '');
            }
        }

        $description = $metaDesc === '' || $metaDesc === '0' ? strip_tags($content) : $metaDesc;

        return [
            'url' => $url,
            'title' => $title,
            'description' => $this->sanitizeDescription($description, 160),
            'image' => null,
        ];
    }

    /**
     * Generate current page share data.
     *
     * @return array<string, mixed>
     */
    public function generateCurrentPageShareData(): array
    {
        $url = request()->url();
        $title = config('app.name');
        $description = __('common.default_share_description');

        return [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'image' => null,
        ];
    }

    /**
     * Generate individual social sharing URLs.
     */
    public function generateFacebookShareUrl(string $url, string $title): string
    {
        return $this->generateFacebookUrl($url, $title);
    }

    public function generateTwitterShareUrl(string $url, string $title, string $description = ''): string
    {
        $shareText = $title . ($description === '' || $description === '0' ? '' : ' - ' . $description);

        return $this->generateTwitterUrl($url, $shareText);
    }

    public function generateLinkedInShareUrl(string $url, string $title, string $description = ''): string
    {
        return $this->generateLinkedInUrl($url, $title, $description);
    }

    public function generateWhatsAppShareUrl(string $url, string $title): string
    {
        return $this->generateWhatsAppUrl($url, $title);
    }

    public function generateEmailShareUrl(string $title, string $description, string $url): string
    {
        return $this->generateEmailUrl($title, $description, $url);
    }

    /**
     * Get social media platform metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSocialPlatformsMetadata(): array
    {
        return [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877F2',
                'background_class' => 'social-facebook',
                'supports_image' => true,
                'character_limit' => null,
            ],
            'twitter' => [
                'name' => 'Twitter',
                'icon' => 'fab fa-twitter',
                'color' => '#1DA1F2',
                'background_class' => 'social-twitter',
                'supports_image' => true,
                'character_limit' => 280,
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => 'fab fa-linkedin-in',
                'color' => '#0A66C2',
                'background_class' => 'social-linkedin',
                'supports_image' => true,
                'character_limit' => 1300,
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25D366',
                'background_class' => 'social-whatsapp',
                'supports_image' => false,
                'character_limit' => null,
            ],
            'telegram' => [
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram-plane',
                'color' => '#0088CC',
                'background_class' => 'social-telegram',
                'supports_image' => false,
                'character_limit' => null,
            ],
            'email' => [
                'name' => 'Email',
                'icon' => 'fas fa-envelope',
                'color' => '#6B7280',
                'background_class' => 'social-email',
                'supports_image' => false,
                'character_limit' => null,
            ],
            'pinterest' => [
                'name' => 'Pinterest',
                'icon' => 'fab fa-pinterest-p',
                'color' => '#BD081C',
                'background_class' => 'social-pinterest',
                'supports_image' => true,
                'character_limit' => 500,
                'requires_image' => true,
            ],
            'reddit' => [
                'name' => 'Reddit',
                'icon' => 'fab fa-reddit-alien',
                'color' => '#FF4500',
                'background_class' => 'social-reddit',
                'supports_image' => false,
                'character_limit' => 300,
            ],
        ];
    }

    /**
     * Generate Open Graph meta tags for better social sharing.
     *
     * @return array<string, string>
     */
    public function generateOpenGraphTags(
        string $title,
        string $description,
        string $url,
        ?string $imageUrl = null,
        string $type = 'website',
    ): array {
        $tags = [
            'og:title' => $title,
            'og:description' => $this->sanitizeDescription($description, 160),
            'og:url' => $url,
            'og:type' => $type,
            'og:site_name' => (string) config('app.name'),
        ];

        if ($imageUrl !== null) {
            $tags['og:image'] = $imageUrl;
            $tags['og:image:alt'] = $title;
        }

        return $tags;
    }

    /**
     * Generate Twitter Card meta tags.
     *
     * @return array<string, string>
     */
    public function generateTwitterCardTags(
        string $title,
        string $description,
        ?string $imageUrl = null,
    ): array {
        $tags = [
            'twitter:card' => $imageUrl ? 'summary_large_image' : 'summary',
            'twitter:title' => Str::limit($title, 70),
            'twitter:description' => $this->sanitizeDescription($description, 200),
        ];

        if ($imageUrl) {
            $tags['twitter:image'] = $imageUrl;
        }

        return $tags;
    }

    /**
     * Generate Facebook sharing URL.
     */
    private function generateFacebookUrl(string $url, string $shareText): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?' . http_build_query([
            'u' => $url,
            'quote' => $shareText,
        ]);
    }

    /**
     * Generate Twitter sharing URL.
     */
    private function generateTwitterUrl(string $url, string $shareText): string
    {
        // Twitter has a character limit, so we need to be careful
        $maxLength = 240; // Leave some room for the URL
        $truncatedText = Str::limit($shareText, $maxLength - strlen($url) - 5, '...');

        return 'https://twitter.com/intent/tweet?' . http_build_query([
            'text' => $truncatedText,
            'url' => $url,
        ]);
    }

    /**
     * Generate LinkedIn sharing URL.
     */
    private function generateLinkedInUrl(string $url, string $title, string $shareText): string
    {
        return 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query([
            'url' => $url,
            'title' => $title,
            'summary' => Str::limit($shareText, 256),
        ]);
    }

    /**
     * Generate WhatsApp sharing URL.
     */
    private function generateWhatsAppUrl(string $url, string $shareText): string
    {
        return 'https://wa.me/?' . http_build_query([
            'text' => $shareText . ' ' . $url,
        ]);
    }

    /**
     * Generate Telegram sharing URL.
     */
    private function generateTelegramUrl(string $url, string $shareText): string
    {
        return 'https://t.me/share/url?' . http_build_query([
            'url' => $url,
            'text' => $shareText,
        ]);
    }

    /**
     * Generate email sharing URL.
     */
    private function generateEmailUrl(string $title, string $shareText, string $url): string
    {
        $body = $shareText . "\n\n" . $url . "\n\n" . __('common.shared_via_platform');

        return 'mailto:?' . http_build_query([
            'subject' => $title,
            'body' => $body,
        ]);
    }

    /**
     * Generate Pinterest sharing URL.
     */
    private function generatePinterestUrl(string $url, string $title, string $imageUrl): string
    {
        return 'https://pinterest.com/pin/create/button/?' . http_build_query([
            'url' => $url,
            'media' => $imageUrl,
            'description' => Str::limit($title, 500),
        ]);
    }

    /**
     * Generate Reddit sharing URL.
     */
    private function generateRedditUrl(string $url, string $title): string
    {
        return 'https://www.reddit.com/submit?' . http_build_query([
            'url' => $url,
            'title' => Str::limit($title, 300),
        ]);
    }

    /**
     * Generate QR code URL using a free service.
     */
    private function generateQrCodeUrl(
        string $url,
        int $size = 200,
        string $format = 'png',
        int $margin = 10,
    ): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => "{$size}x{$size}",
            'data' => $url,
            'format' => $format,
            'margin' => $margin,
            'ecc' => 'L', // Error correction level
        ]);
    }

    /**
     * Generate QR code download URL.
     */
    private function generateQrCodeDownloadUrl(
        string $url,
        int $size = 200,
        string $format = 'png',
        int $margin = 10,
    ): string {
        return $this->generateQrCodeUrl($url, $size, $format, $margin) . '&download=1';
    }

    /**
     * Generate share text based on title and description.
     */
    private function generateShareText(string $title, string $description = ''): string
    {
        $baseText = __('common.share_text_template', ['title' => $title]);

        if ($description !== '' && $description !== '0') {
            $cleanDescription = $this->sanitizeDescription($description, 100);
            $baseText .= ' - ' . $cleanDescription;
        }

        return $baseText;
    }

    /**
     * Sanitize description for sharing.
     */
    private function sanitizeDescription(string $description, ?int $limit = null): string
    {
        // Remove HTML tags and clean up whitespace
        $clean = strip_tags($description);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim((string) $clean);

        if ($limit && strlen($clean) > $limit) {
            return Str::limit($clean, $limit, '...');
        }

        return $clean;
    }
}
