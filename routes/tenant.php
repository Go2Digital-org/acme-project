<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| HYBRID ROUTING ARCHITECTURE:
|
| This application uses a hybrid multi-tenant routing approach:
|
| 1. ALL APPLICATION ROUTES are defined in routes/web.php
|    - CSR platform routes (campaigns, volunteering, donations, etc.)
|    - Admin panel routes
|    - API routes
|    - Authentication routes
|
| 2. BOTH central and tenant domains use the SAME routes from web.php
|    - Central domain: acme-corp-optimy.local
|    - Tenant domains: tenant1.acme-corp-optimy.local, tenant2.acme-corp-optimy.local
|
| 3. DATA ISOLATION is handled by middleware, not route separation:
|    - InitializeTenancyByDomain middleware identifies the tenant
|    - Tenant context is automatically applied to all database queries
|    - Same controllers/logic serve different tenant data
|
| 4. THIS FILE (routes/tenant.php) is kept for:
|    - TenancyServiceProvider compatibility (references this file)
|    - Future tenant-specific route overrides if needed
|    - Tenant-only functionality that shouldn't be available on central domain
|
| BENEFITS of this approach:
| - Single codebase for all tenants
| - Consistent routing structure
| - Easy maintenance and updates
| - Automatic data isolation
| - Scalable multi-tenant architecture
|
|--------------------------------------------------------------------------
*/

// This file is intentionally minimal - all routes are in web.php
// Add tenant-specific overrides here only if needed in the future
