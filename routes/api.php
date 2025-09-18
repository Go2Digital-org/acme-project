<?php

declare(strict_types=1);

/**
 * API Platform ONLY - Pure Hexagonal/CQRS Architecture
 * =====================================================
 *
 * This project uses EXCLUSIVELY API Platform for ALL API endpoints.
 * NO traditional Laravel routes. NO controllers. ONLY API Platform resources.
 *
 * Architecture:
 * - Hexagonal Architecture with Domain, Application, and Infrastructure layers
 * - CQRS pattern for all operations (Commands/Queries with Handlers)
 * - API Platform Resources define endpoints declaratively
 * - Processors and Providers handle request/response through CQRS
 *
 * API Platform Resources Location:
 * modules/{Module}/Infrastructure/ApiPlatform/Resource/{Entity}Resource.php
 *
 * Available API Endpoints (ALL via API Platform):
 * - Authentication: /api/auth/* (via AuthenticationResource)
 * - Campaigns: /api/campaigns (via CampaignResource)
 * - Donations: /api/donations (via DonationResource)
 * - Organizations: /api/organizations (via OrganizationResource)
 * - Users: /api/users (via UserResource)
 * - Bookmarks: /api/campaigns/bookmark (via BookmarkResource)
 * - Dashboard: /api/dashboard/* (via DashboardResource)
 * - Exports: /api/exports (via ExportResource)
 * - Cache Status: /api/cache-status (via CacheStatusResource)
 * - Search: /api/search/* (via SearchResource)
 * - Notifications: /api/notifications/* (via NotificationResource)
 * - Currencies: /api/currencies/* (via CurrencyResource)
 * - Webhooks: /api/webhooks/* (via WebhookResource)
 *
 * CQRS Flow:
 * 1. API Platform Resource receives request
 * 2. Processor/Provider delegates to Command/Query
 * 3. CommandHandler/QueryHandler executes business logic
 * 4. Response returned through API Platform
 *
 * Authentication:
 * - Bearer token via Laravel Sanctum
 * - Header: Authorization: Bearer {token}
 * - Security configured in ApiResource attributes
 *
 * NO ROUTES ARE DEFINED HERE - Everything is handled by API Platform autodiscovery.
 */

// This file intentionally contains no route definitions.
// All API endpoints are automatically handled by API Platform through resource attributes.
