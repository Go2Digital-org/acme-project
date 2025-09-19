<?php

declare(strict_types=1);

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;

/**
 * Helper function to resolve donation from mixed input (Donation object or string ID).
 */
if (! function_exists('resolveDonation')) {
    function resolveDonation(mixed $donation): ?Donation
    {
        if ($donation instanceof Donation) {
            return $donation;
        }

        if (is_string($donation) || is_int($donation)) {
            try {
                $repository = app(DonationRepositoryInterface::class);

                return $repository->findById((int) $donation);
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }
}

/**
 * Helper function to resolve campaign from mixed input (Campaign object or string ID).
 */
if (! function_exists('resolveCampaign')) {
    function resolveCampaign(mixed $campaign): ?Campaign
    {
        if ($campaign instanceof Campaign) {
            return $campaign;
        }

        if (is_string($campaign) || is_int($campaign)) {
            try {
                $repository = app(CampaignRepositoryInterface::class);

                return $repository->findById((int) $campaign);
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }
}

/**
 * Helper function to resolve page from mixed input (Page object or string slug/ID).
 */
if (! function_exists('resolvePage')) {
    function resolvePage(mixed $page): ?Page
    {
        if ($page instanceof Page) {
            return $page;
        }

        if (is_string($page) || is_int($page)) {
            try {
                $repository = app(PageRepositoryInterface::class);

                // Try by slug first (if string), then by ID
                if (is_string($page) && ! is_numeric($page)) {
                    return $repository->findBySlug($page);
                }

                return $repository->findById((int) $page);
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }
}

// Home/Welcome
Breadcrumbs::for('welcome', function (BreadcrumbTrail $trail): void {
    $trail->push(__('navigation.home'), route('welcome'));
});

// Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail): void {
    $trail->parent('welcome');
    $trail->push(__('navigation.dashboard'), route('dashboard'));
});

// Style Guide
Breadcrumbs::for('style-guide', function (BreadcrumbTrail $trail): void {
    $trail->parent('welcome');
    $trail->push('Style Guide', route('style-guide'));
});

// Authentication Routes
Breadcrumbs::for('login', function (BreadcrumbTrail $trail): void {
    $trail->parent('welcome');
    $trail->push(__('auth.login'), route('login'));
});

Breadcrumbs::for('register', function (BreadcrumbTrail $trail): void {
    $trail->parent('welcome');
    $trail->push(__('auth.register'), route('register'));
});

Breadcrumbs::for('password.request', function (BreadcrumbTrail $trail): void {
    $trail->parent('login');
    $trail->push(__('auth.forgot_password'), route('password.request'));
});

Breadcrumbs::for('password.reset', function (BreadcrumbTrail $trail, string $token): void {
    $trail->parent('password.request');
    $trail->push(__('auth.reset_password'), route('password.reset', $token));
});

Breadcrumbs::for('verification.notice', function (BreadcrumbTrail $trail): void {
    $trail->parent('dashboard');
    $trail->push(__('auth.verify_email'), route('verification.notice'));
});

Breadcrumbs::for('password.confirm.custom', function (BreadcrumbTrail $trail): void {
    $trail->parent('dashboard');
    $trail->push(__('auth.confirm_password'), route('password.confirm.custom'));
});

Breadcrumbs::for('two-factor.login', function (BreadcrumbTrail $trail): void {
    $trail->parent('login');
    $trail->push(__('auth.two_factor_challenge'), route('two-factor.login'));
});

// Campaign Routes
Breadcrumbs::for('campaigns.index', function (BreadcrumbTrail $trail): void {
    $trail->parent('welcome');
    $trail->push(__('campaigns.campaigns'), route('campaigns.index'));
});

Breadcrumbs::for('campaigns.search', function (BreadcrumbTrail $trail): void {
    $trail->parent('campaigns.index');
    $trail->push(__('campaigns.search'), route('campaigns.search'));
});

Breadcrumbs::for('campaigns.create', function (BreadcrumbTrail $trail): void {
    $trail->parent('campaigns.index');
    $trail->push(__('campaigns.create_campaign'), route('campaigns.create'));
});

Breadcrumbs::for('campaigns.show', function (BreadcrumbTrail $trail, $campaign): void {
    $resolvedCampaign = resolveCampaign($campaign);

    if (! $resolvedCampaign instanceof Campaign) {
        $trail->parent('campaigns.index');
        $trail->push(__('campaigns.campaign'), route('campaigns.index'));

        return;
    }

    $trail->parent('campaigns.index');
    $trail->push($resolvedCampaign->getTitle(), route('campaigns.show', $resolvedCampaign));
});

Breadcrumbs::for('campaigns.edit', function (BreadcrumbTrail $trail, $campaign): void {
    $resolvedCampaign = resolveCampaign($campaign);

    if (! $resolvedCampaign instanceof Campaign) {
        $trail->parent('campaigns.index');
        $trail->push(__('campaigns.edit_campaign'), route('campaigns.index'));

        return;
    }

    $trail->parent('campaigns.show', $resolvedCampaign);
    $trail->push(__('campaigns.edit_campaign'), route('campaigns.edit', $resolvedCampaign));
});

Breadcrumbs::for('campaigns.my-campaigns', function (BreadcrumbTrail $trail): void {
    $trail->parent('campaigns.index');
    $trail->push(__('campaigns.my_campaigns'), route('campaigns.my-campaigns'));
});

Breadcrumbs::for('campaigns.donate', function (BreadcrumbTrail $trail, $campaign): void {
    $resolvedCampaign = resolveCampaign($campaign);

    if (! $resolvedCampaign instanceof Campaign) {
        $trail->parent('campaigns.index');
        $trail->push(__('campaigns.donate'), route('campaigns.index'));

        return;
    }

    $trail->parent('campaigns.show', $resolvedCampaign);
    $trail->push(__('campaigns.donate'), route('campaigns.donate', $resolvedCampaign));
});

// Donation Routes
Breadcrumbs::for('donations.index', function (BreadcrumbTrail $trail): void {
    $trail->parent('dashboard');
    $trail->push(__('donations.my_donations'), route('donations.index'));
});

Breadcrumbs::for('donations.create', function (BreadcrumbTrail $trail): void {
    $trail->parent('donations.index');
    $trail->push(__('donations.create_donation'), route('donations.create'));
});

Breadcrumbs::for('donations.show', function (BreadcrumbTrail $trail, $donation): void {
    $resolvedDonation = resolveDonation($donation);

    if (! $resolvedDonation instanceof Donation) {
        $trail->parent('donations.index');
        $trail->push(__('donations.donation'), route('donations.index'));

        return;
    }

    $trail->parent('donations.index');
    $trail->push(__('donations.donation') . ' #' . $resolvedDonation->id, route('donations.show', $resolvedDonation));
});

Breadcrumbs::for('donations.export', function (BreadcrumbTrail $trail): void {
    $trail->parent('donations.index');
    $trail->push(__('donations.export'), route('donations.export'));
});

// Payment Flow Routes
Breadcrumbs::for('donations.success', function (BreadcrumbTrail $trail, $donation): void {
    $resolvedDonation = resolveDonation($donation);

    if (! $resolvedDonation instanceof Donation) {
        $trail->parent('donations.index');
        $trail->push(__('donations.success'), route('donations.index'));

        return;
    }

    $trail->parent('donations.show', $resolvedDonation);
    $trail->push(__('donations.success'), route('donations.success', $resolvedDonation));
});

Breadcrumbs::for('donations.cancelled', function (BreadcrumbTrail $trail, $donation): void {
    $resolvedDonation = resolveDonation($donation);

    if (! $resolvedDonation instanceof Donation) {
        $trail->parent('donations.index');
        $trail->push(__('donations.cancelled'), route('donations.index'));

        return;
    }

    $trail->parent('donations.show', $resolvedDonation);
    $trail->push(__('donations.cancelled'), route('donations.cancelled', $resolvedDonation));
});

Breadcrumbs::for('donations.failed', function (BreadcrumbTrail $trail, $donation): void {
    $resolvedDonation = resolveDonation($donation);

    if (! $resolvedDonation instanceof Donation) {
        $trail->parent('donations.index');
        $trail->push(__('donations.failed'), route('donations.index'));

        return;
    }

    $trail->parent('donations.show', $resolvedDonation);
    $trail->push(__('donations.failed'), route('donations.failed', $resolvedDonation));
});

// Profile Routes
Breadcrumbs::for('profile.show', function (BreadcrumbTrail $trail): void {
    $trail->parent('dashboard');
    $trail->push(__('profile.profile'), route('profile.show'));
});

Breadcrumbs::for('profile.edit', function (BreadcrumbTrail $trail): void {
    $trail->parent('profile.show');
    $trail->push(__('profile.edit_profile'), route('profile.edit'));
});

Breadcrumbs::for('profile.security', function (BreadcrumbTrail $trail): void {
    $trail->parent('profile.show');
    $trail->push(__('profile.security'), route('profile.security'));
});

Breadcrumbs::for('profile.sessions', function (BreadcrumbTrail $trail): void {
    $trail->parent('profile.security');
    $trail->push(__('profile.sessions'), route('profile.sessions'));
});

// Notification Routes
Breadcrumbs::for('notifications.index', function (BreadcrumbTrail $trail): void {
    $trail->parent('dashboard');
    $trail->push(__('notifications.notifications'), route('notifications.index'));
});

// Dynamic Pages (using slug)
Breadcrumbs::for('page', function (BreadcrumbTrail $trail, $slug): void {
    $trail->parent('welcome');

    // Use the resolver to handle both Page objects and slug strings
    $page = resolvePage($slug);

    if ($page instanceof Page && $page->getTranslation('title')) {
        $title = $page->getTranslation('title');
    } else {
        // Fallback to formatting the slug
        $title = is_string($slug) ? ucfirst(str_replace('-', ' ', $slug)) : 'Page';
    }

    $trail->push($title, route('page', $slug));
});
