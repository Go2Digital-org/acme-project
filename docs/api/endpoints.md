# ACME Corp CSR Platform - API Endpoints Documentation

## Overview

The ACME Corp CSR Platform provides a comprehensive RESTful API built with **API Platform** following **Hexagonal Architecture** and **CQRS patterns**. All endpoints are automatically discovered through API Platform resources and use Laravel Sanctum for authentication.

## Base URL

- **Development**: `http://localhost:8000/api`
- **Production**: `https://api.yourdomain.com/api`

## Authentication

All API endpoints require Bearer token authentication via Laravel Sanctum unless otherwise specified.

### Authentication Headers
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Authentication Workflow

#### 1. User Registration
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!"
}
```

**Response (201 Created):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified": false,
    "created_at": "2024-01-15T10:30:00Z"
  },
  "token": "1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "message": "Registration successful"
}
```

#### 2. User Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "department": "Engineering",
    "job_title": "Software Developer",
    "roles": ["employee"]
  },
  "token": "2|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "message": "Login successful"
}
```

#### 3. Get Current User Profile
```http
GET /api/auth/user
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "department": "Engineering",
    "job_title": "Software Developer",
    "manager_email": "manager@example.com",
    "phone": "+1-555-123-4567",
    "hire_date": "2023-01-15",
    "preferred_language": "en",
    "timezone": "America/New_York",
    "roles": ["employee"],
    "email_verified": true,
    "last_login_at": "2024-01-15T09:15:00Z"
  }
}
```

#### 4. User Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Logout successful"
}
```

---

## Campaign Management

### 1. List Campaigns
```http
GET /api/campaigns
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `itemsPerPage` (int): Items per page (max: 100, default: 20)
- `status` (string): Filter by status (active, completed, draft, cancelled)
- `organization_id` (int): Filter by organization
- `user_id` (int): Filter by campaign creator
- `search` (string): Full-text search in title and description
- `sort[property]` (string): Sort by field (created_at, title, goal_amount, end_date)
- `start_date[after]` (string): Filter campaigns starting after date
- `end_date[before]` (string): Filter campaigns ending before date
- `locale` (string): Language for content (en, fr, es, etc.)

**Example Request:**
```http
GET /api/campaigns?status=active&sort[end_date]=asc&itemsPerPage=10
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Campaign",
  "@id": "/api/campaigns",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/campaigns/1",
      "@type": "Campaign",
      "id": 1,
      "title": "Clean Water Initiative",
      "description": "Providing clean water access to rural communities",
      "goalAmount": 50000.00,
      "goal_amount": 50000.00,
      "currentAmount": 32500.00,
      "progressPercentage": 65.0,
      "startDate": "2024-01-01T00:00:00Z",
      "start_date": "2024-01-01T00:00:00Z",
      "endDate": "2024-12-31T23:59:59Z",
      "end_date": "2024-12-31T23:59:59Z",
      "status": "active",
      "organizationId": 1,
      "organization_id": 1,
      "organizationName": "Global Water Foundation",
      "userId": 5,
      "employeeName": "Sarah Johnson",
      "daysRemaining": 320,
      "remainingAmount": 17500.00,
      "hasReachedGoal": false,
      "isActive": true,
      "canAcceptDonation": true,
      "createdAt": "2024-01-01T08:00:00Z",
      "updatedAt": "2024-01-15T14:30:00Z"
    }
  ],
  "hydra:totalItems": 150,
  "hydra:view": {
    "@id": "/api/campaigns?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/campaigns?page=1",
    "hydra:last": "/api/campaigns?page=15",
    "hydra:next": "/api/campaigns?page=2"
  }
}
```

### 2. Get Campaign Details
```http
GET /api/campaigns/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Campaign",
  "@id": "/api/campaigns/1",
  "@type": "Campaign",
  "id": 1,
  "title": "Clean Water Initiative",
  "description": "Providing clean water access to rural communities in developing regions. This campaign aims to build sustainable water infrastructure and educate communities on water conservation practices.",
  "goalAmount": 50000.00,
  "goal_amount": 50000.00,
  "currentAmount": 32500.00,
  "progressPercentage": 65.0,
  "startDate": "2024-01-01T00:00:00Z",
  "start_date": "2024-01-01T00:00:00Z",
  "endDate": "2024-12-31T23:59:59Z",
  "end_date": "2024-12-31T23:59:59Z",
  "status": "active",
  "organizationId": 1,
  "organization_id": 1,
  "organizationName": "Global Water Foundation",
  "userId": 5,
  "employeeName": "Sarah Johnson",
  "daysRemaining": 320,
  "remainingAmount": 17500.00,
  "hasReachedGoal": false,
  "isActive": true,
  "canAcceptDonation": true,
  "completedAt": null,
  "createdAt": "2024-01-01T08:00:00Z",
  "updatedAt": "2024-01-15T14:30:00Z"
}
```

### 3. Create Campaign
```http
POST /api/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": {
    "en": "Educational Support Program",
    "fr": "Programme de soutien éducatif"
  },
  "description": {
    "en": "Supporting education in underserved communities",
    "fr": "Soutenir l'éducation dans les communautés mal desservies"
  },
  "goal_amount": 25000.00,
  "start_date": "2024-02-01",
  "end_date": "2024-11-30",
  "organization_id": 2
}
```

**Response (201 Created):**
```json
{
  "@context": "/api/contexts/Campaign",
  "@id": "/api/campaigns/2",
  "@type": "Campaign",
  "id": 2,
  "title": "Educational Support Program",
  "description": "Supporting education in underserved communities",
  "goalAmount": 25000.00,
  "goal_amount": 25000.00,
  "currentAmount": 0.00,
  "progressPercentage": 0.0,
  "startDate": "2024-02-01T00:00:00Z",
  "start_date": "2024-02-01T00:00:00Z",
  "endDate": "2024-11-30T23:59:59Z",
  "end_date": "2024-11-30T23:59:59Z",
  "status": "draft",
  "organizationId": 2,
  "organization_id": 2,
  "organizationName": "Education First",
  "userId": 1,
  "employeeName": "John Doe",
  "daysRemaining": 303,
  "remainingAmount": 25000.00,
  "hasReachedGoal": false,
  "isActive": false,
  "canAcceptDonation": false,
  "createdAt": "2024-01-15T15:00:00Z",
  "updatedAt": "2024-01-15T15:00:00Z"
}
```

### 4. Update Campaign (Full Update)
```http
PUT /api/campaigns/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": {
    "en": "Updated Educational Support Program",
    "fr": "Programme de soutien éducatif mis à jour"
  },
  "description": {
    "en": "Supporting quality education in underserved communities worldwide",
    "fr": "Soutenir une éducation de qualité dans les communautés mal desservies du monde entier"
  },
  "goal_amount": 30000.00,
  "end_date": "2024-12-15"
}
```

### 5. Partial Update Campaign
```http
PATCH /api/campaigns/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "active",
  "goal_amount": 35000.00
}
```

### 6. Delete Campaign
```http
DELETE /api/campaigns/{id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

---

## Campaign Bookmarks/Favorites

### 1. Toggle Campaign Bookmark
```http
POST /api/campaigns/{id}/bookmark
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "campaign_id": 1,
  "bookmarked": true,
  "bookmark_count": 15,
  "message": "Campaign bookmarked successfully"
}
```

### 2. Remove Campaign Bookmark
```http
DELETE /api/campaigns/{id}/bookmark
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "campaign_id": 1,
  "bookmarked": false,
  "bookmark_count": 14,
  "message": "Bookmark removed successfully"
}
```

### 3. Get Bookmark Status
```http
GET /api/campaigns/{id}/bookmark/status
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "campaign_id": 1,
  "bookmarked": true,
  "bookmark_count": 15
}
```

### 4. Get User's Bookmarked Campaigns
```http
GET /api/campaigns/bookmarked
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `itemsPerPage` (int): Items per page (max: 100, default: 20)

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Bookmark",
  "@id": "/api/campaigns/bookmarked",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/campaigns/1",
      "@type": "Bookmark",
      "id": 1,
      "campaign_id": 1,
      "campaign_title": "Clean Water Initiative",
      "campaign_description": "Providing clean water access to rural communities",
      "campaign_goal_amount": 50000.00,
      "campaign_current_amount": 32500.00,
      "progress_percentage": 65.0,
      "start_date": "2024-01-01T00:00:00Z",
      "end_date": "2024-12-31T23:59:59Z",
      "status": "active",
      "days_remaining": 320,
      "organization": {
        "id": 1,
        "name": "Global Water Foundation"
      },
      "creator": {
        "id": 5,
        "name": "Sarah Johnson"
      },
      "created_at": "2024-01-10T12:00:00Z"
    }
  ],
  "hydra:totalItems": 8,
  "hydra:view": {
    "@id": "/api/campaigns/bookmarked?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/campaigns/bookmarked?page=1",
    "hydra:last": "/api/campaigns/bookmarked?page=1"
  }
}
```

---

## Donation Management

### 1. List Donations
```http
GET /api/donations
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `itemsPerPage` (int): Items per page (max: 100, default: 20)
- `campaign_id` (int): Filter by campaign
- `user_id` (int): Filter by donor
- `status` (string): Filter by status (pending, completed, failed, cancelled, refunded)
- `payment_method` (string): Filter by payment method (stripe, paypal, bank_transfer, credit_card)
- `anonymous` (bool): Filter anonymous donations
- `recurring` (bool): Filter recurring donations
- `sort[property]` (string): Sort by field (donated_at, amount, created_at)
- `donated_at[after]` (string): Filter donations after date
- `donated_at[before]` (string): Filter donations before date

**Example Request:**
```http
GET /api/donations?campaign_id=1&status=completed&sort[donated_at]=desc
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Donation",
  "@id": "/api/donations",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/donations/1",
      "@type": "Donation",
      "id": 1,
      "campaign_id": 1,
      "campaign_title": "Clean Water Initiative",
      "user_id": 3,
      "employee_name": "Alice Smith",
      "amount": 150.00,
      "currency": "USD",
      "payment_method": "stripe",
      "payment_gateway": "stripe",
      "transaction_id": "pi_1234567890abcdef",
      "gateway_response_id": "ch_1234567890abcdef",
      "status": "completed",
      "anonymous": false,
      "recurring": false,
      "recurring_frequency": null,
      "donated_at": "2024-01-15T14:30:00Z",
      "processed_at": "2024-01-15T14:30:15Z",
      "completed_at": "2024-01-15T14:30:30Z",
      "cancelled_at": null,
      "refunded_at": null,
      "failure_reason": null,
      "refund_reason": null,
      "notes": "Great cause!",
      "metadata": {
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0..."
      },
      "formatted_amount": "$150.00",
      "days_since_donation": 5,
      "can_be_processed": false,
      "can_be_cancelled": false,
      "can_be_refunded": true,
      "is_successful": true,
      "is_pending": false,
      "is_failed": false,
      "created_at": "2024-01-15T14:25:00Z",
      "updated_at": "2024-01-15T14:30:30Z"
    }
  ],
  "hydra:totalItems": 245,
  "hydra:view": {
    "@id": "/api/donations?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/donations?page=1",
    "hydra:last": "/api/donations?page=13",
    "hydra:next": "/api/donations?page=2"
  }
}
```

### 2. Get Donation Details
```http
GET /api/donations/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Donation",
  "@id": "/api/donations/1",
  "@type": "Donation",
  "id": 1,
  "campaign_id": 1,
  "campaign_title": "Clean Water Initiative",
  "user_id": 3,
  "employee_name": "Alice Smith",
  "amount": 150.00,
  "currency": "USD",
  "payment_method": "stripe",
  "payment_gateway": "stripe",
  "transaction_id": "pi_1234567890abcdef",
  "gateway_response_id": "ch_1234567890abcdef",
  "status": "completed",
  "anonymous": false,
  "recurring": false,
  "recurring_frequency": null,
  "donated_at": "2024-01-15T14:30:00Z",
  "processed_at": "2024-01-15T14:30:15Z",
  "completed_at": "2024-01-15T14:30:30Z",
  "cancelled_at": null,
  "refunded_at": null,
  "failure_reason": null,
  "refund_reason": null,
  "notes": "Great cause! Happy to support this initiative.",
  "metadata": {
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "campaign_source": "email_newsletter"
  },
  "formatted_amount": "$150.00",
  "days_since_donation": 5,
  "can_be_processed": false,
  "can_be_cancelled": false,
  "can_be_refunded": true,
  "is_successful": true,
  "is_pending": false,
  "is_failed": false,
  "created_at": "2024-01-15T14:25:00Z",
  "updated_at": "2024-01-15T14:30:30Z"
}
```

### 3. Create Donation
```http
POST /api/donations
Authorization: Bearer {token}
Content-Type: application/json

{
  "campaign_id": 1,
  "amount": 100.00,
  "currency": "USD",
  "payment_method": "stripe",
  "anonymous": false,
  "recurring": false,
  "recurring_frequency": null,
  "notes": "Supporting this great cause!",
  "payment_details": {
    "payment_method_id": "pm_1234567890",
    "billing_address": {
      "line1": "123 Main St",
      "city": "New York",
      "state": "NY",
      "postal_code": "10001",
      "country": "US"
    }
  }
}
```

**Response (201 Created):**
```json
{
  "@context": "/api/contexts/Donation",
  "@id": "/api/donations/2",
  "@type": "Donation",
  "id": 2,
  "campaign_id": 1,
  "campaign_title": "Clean Water Initiative",
  "user_id": 1,
  "employee_name": "John Doe",
  "amount": 100.00,
  "currency": "USD",
  "payment_method": "stripe",
  "payment_gateway": "stripe",
  "transaction_id": null,
  "gateway_response_id": null,
  "status": "pending",
  "anonymous": false,
  "recurring": false,
  "recurring_frequency": null,
  "donated_at": "2024-01-20T10:15:00Z",
  "processed_at": null,
  "completed_at": null,
  "cancelled_at": null,
  "refunded_at": null,
  "failure_reason": null,
  "refund_reason": null,
  "notes": "Supporting this great cause!",
  "metadata": {
    "ip_address": "192.168.1.105",
    "user_agent": "Mozilla/5.0..."
  },
  "formatted_amount": "$100.00",
  "days_since_donation": 0,
  "can_be_processed": true,
  "can_be_cancelled": true,
  "can_be_refunded": false,
  "is_successful": false,
  "is_pending": true,
  "is_failed": false,
  "created_at": "2024-01-20T10:15:00Z",
  "updated_at": "2024-01-20T10:15:00Z"
}
```

### 4. Cancel Donation
```http
PATCH /api/donations/{id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "User requested cancellation"
}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Donation",
  "@id": "/api/donations/2",
  "@type": "Donation",
  "id": 2,
  "status": "cancelled",
  "cancelled_at": "2024-01-20T11:00:00Z",
  "failure_reason": "User requested cancellation",
  "can_be_processed": false,
  "can_be_cancelled": false,
  "can_be_refunded": false,
  "message": "Donation cancelled successfully"
}
```

### 5. Refund Donation
```http
PATCH /api/donations/{id}/refund
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Duplicate donation",
  "refund_amount": 100.00
}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Donation",
  "@id": "/api/donations/1",
  "@type": "Donation",
  "id": 1,
  "status": "refunded",
  "refunded_at": "2024-01-20T12:00:00Z",
  "refund_reason": "Duplicate donation",
  "can_be_processed": false,
  "can_be_cancelled": false,
  "can_be_refunded": false,
  "message": "Donation refunded successfully"
}
```

---

## Organization Management

### 1. List Organizations
```http
GET /api/organizations
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `itemsPerPage` (int): Items per page (max: 100, default: 20)
- `name` (string): Partial search in organization name
- `category` (string): Filter by category
- `verified` (bool): Filter by verification status
- `active` (bool): Filter by active status
- `country` (string): Filter by country
- `city` (string): Filter by city
- `search` (string): Full-text search in name, category, city, country
- `sort[property]` (string): Sort by field (name, created_at, verification_date)

**Example Request:**
```http
GET /api/organizations?verified=true&category=non-profit&sort[name]=asc
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Organization",
  "@id": "/api/organizations",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/organizations/1",
      "@type": "Organization",
      "id": 1,
      "name": "Global Water Foundation",
      "registration_number": "NP-2019-001",
      "tax_id": "12-3456789",
      "category": "non-profit",
      "website": "https://example-charity.org",
      "email": "contact@example-charity.org",
      "phone": "+1-555-001-0001",
      "address": "123 Charity Lane",
      "city": "New York",
      "country": "United States",
      "verified": true,
      "active": true,
      "verified_at": "2023-06-15T10:00:00Z",
      "can_create_campaigns": true,
      "status": "active",
      "status_color": "green",
      "status_label": "Active & Verified",
      "is_eligible_for_verification": false,
      "created_at": "2023-01-15T08:00:00Z",
      "updated_at": "2023-06-15T10:00:00Z"
    }
  ],
  "hydra:totalItems": 45,
  "hydra:view": {
    "@id": "/api/organizations?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/organizations?page=1",
    "hydra:last": "/api/organizations?page=3",
    "hydra:next": "/api/organizations?page=2"
  }
}
```

### 2. Get Organization Details
```http
GET /api/organizations/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Organization",
  "@id": "/api/organizations/1",
  "@type": "Organization",
  "id": 1,
  "name": "Global Water Foundation",
  "registration_number": "NP-2019-001",
  "tax_id": "12-3456789",
  "category": "non-profit",
  "website": "https://example-charity.org",
  "email": "contact@example-charity.org",
  "phone": "+1-555-001-0001",
  "address": "123 Charity Lane, Suite 100",
  "city": "New York",
  "country": "United States",
  "verified": true,
  "active": true,
  "verified_at": "2023-06-15T10:00:00Z",
  "can_create_campaigns": true,
  "status": "active",
  "status_color": "green",
  "status_label": "Active & Verified",
  "is_eligible_for_verification": false,
  "created_at": "2023-01-15T08:00:00Z",
  "updated_at": "2023-06-15T10:00:00Z"
}
```

### 3. Create Organization
```http
POST /api/organizations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Education First Foundation",
  "registration_number": "NP-2024-005",
  "tax_id": "98-7654321",
  "category": "education",
  "website": "https://example-education.org",
  "email": "info@example-education.org",
  "phone": "+1-555-002-0002",
  "address": "456 Learning Ave",
  "city": "Boston",
  "country": "United States"
}
```

**Response (201 Created):**
```json
{
  "@context": "/api/contexts/Organization",
  "@id": "/api/organizations/2",
  "@type": "Organization",
  "id": 2,
  "name": "Education First Foundation",
  "registration_number": "NP-2024-005",
  "tax_id": "98-7654321",
  "category": "education",
  "website": "https://example-education.org",
  "email": "info@example-education.org",
  "phone": "+1-555-002-0002",
  "address": "456 Learning Ave",
  "city": "Boston",
  "country": "United States",
  "verified": false,
  "active": true,
  "verified_at": null,
  "can_create_campaigns": false,
  "status": "pending_verification",
  "status_color": "orange",
  "status_label": "Pending Verification",
  "is_eligible_for_verification": true,
  "created_at": "2024-01-20T15:30:00Z",
  "updated_at": "2024-01-20T15:30:00Z"
}
```

### 4. Update Organization
```http
PUT /api/organizations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Education First Foundation Updated",
  "website": "https://example-education.org",
  "email": "contact@example-education.org",
  "phone": "+1-555-002-0003",
  "address": "456 Learning Ave, Suite 200",
  "city": "Boston",
  "country": "United States"
}
```

### 5. Verify Organization
```http
PATCH /api/organizations/{id}/verify
Authorization: Bearer {token}
Content-Type: application/json

{
  "verification_notes": "All documentation verified and approved",
  "verification_documents": [
    "registration_certificate.pdf",
    "tax_exemption_letter.pdf"
  ]
}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Organization",
  "@id": "/api/organizations/2",
  "@type": "Organization",
  "id": 2,
  "name": "Education First Foundation",
  "verified": true,
  "verified_at": "2024-01-20T16:00:00Z",
  "can_create_campaigns": true,
  "status": "active",
  "status_color": "green",
  "status_label": "Active & Verified",
  "is_eligible_for_verification": false,
  "message": "Organization verified successfully"
}
```

---

## User Management

### 1. List Users
```http
GET /api/users
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `itemsPerPage` (int): Items per page (max: 100, default: 20)
- `name` (string): Partial search in user name
- `email` (string): Partial search in email
- `job_title` (string): Partial search in job title
- `search` (string): Full-text search in name, email, job title
- `sort[property]` (string): Sort by field (name, email, created_at)

**Security:** Requires `ROLE_USER` (admin/manager access)

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/users/1",
      "@type": "User",
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "department": "Engineering",
      "jobTitle": "Software Developer",
      "job_title": "Software Developer",
      "employeeId": "EMP001",
      "manager_email": "manager@example.com",
      "phone": "+1-555-123-4567",
      "hire_date": "2023-01-15",
      "preferred_language": "en",
      "timezone": "America/New_York",
      "roles": ["employee"],
      "emailVerified": true,
      "email_verified": true,
      "last_login_at": "2024-01-20T09:15:00Z",
      "account_locked": false,
      "createdAt": "2023-01-15T08:00:00Z",
      "created_at": "2023-01-15T08:00:00Z",
      "updatedAt": "2024-01-20T09:15:00Z",
      "updated_at": "2024-01-20T09:15:00Z"
    }
  ],
  "hydra:totalItems": 125,
  "hydra:view": {
    "@id": "/api/users?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/users?page=1",
    "hydra:last": "/api/users?page=7",
    "hydra:next": "/api/users?page=2"
  }
}
```

### 2. Get User Details
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

**Security:** Requires `ROLE_USER` OR owner access (`object.id == user.id`)

### 3. Update User Profile
```http
PUT /api/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe Updated",
  "phone": "+1-555-123-4568",
  "department": "Engineering",
  "job_title": "Senior Software Developer",
  "preferred_language": "en",
  "timezone": "America/New_York"
}
```

### 4. Delete User
```http
DELETE /api/users/{id}
Authorization: Bearer {token}
```

**Security:** Requires `ROLE_USER` (admin access)
**Response (204 No Content)**

---

## Currency Management

### 1. List Available Currencies
```http
GET /api/currencies
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Currency",
  "@id": "/api/currencies",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/currencies/1",
      "@type": "Currency",
      "id": 1,
      "code": "USD",
      "name": "US Dollar",
      "symbol": "$",
      "flag": "🇺🇸",
      "decimal_places": 2,
      "decimal_separator": ".",
      "thousands_separator": ",",
      "symbol_position": "before",
      "is_active": true,
      "is_default": true,
      "exchange_rate": 1.0,
      "sort_order": 1
    },
    {
      "@id": "/api/currencies/2",
      "@type": "Currency",
      "id": 2,
      "code": "EUR",
      "name": "Euro",
      "symbol": "€",
      "flag": "🇪🇺",
      "decimal_places": 2,
      "decimal_separator": ",",
      "thousands_separator": ".",
      "symbol_position": "after",
      "is_active": true,
      "is_default": false,
      "exchange_rate": 0.85,
      "sort_order": 2
    }
  ]
}
```

### 2. Get Current User Currency
```http
GET /api/currencies/current
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Currency",
  "@id": "/api/currencies/1",
  "@type": "Currency",
  "id": 1,
  "code": "USD",
  "name": "US Dollar",
  "symbol": "$",
  "flag": "🇺🇸",
  "decimal_places": 2,
  "decimal_separator": ".",
  "thousands_separator": ",",
  "symbol_position": "before",
  "is_active": true,
  "is_default": true,
  "exchange_rate": 1.0
}
```

### 3. Set User Currency Preference
```http
POST /api/currencies/set
Authorization: Bearer {token}
Content-Type: application/json

{
  "currency": "EUR"
}
```

**Response (200 OK):**
```json
{
  "currency": "EUR",
  "message": "Currency preference updated successfully"
}
```

### 4. Format Currency Amount
```http
POST /api/currencies/format
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 1234.56,
  "currency": "EUR"
}
```

**Response (200 OK):**
```json
{
  "amount": 1234.56,
  "currency": "EUR",
  "formatted": "1.234,56 €"
}
```

---

## Search Functionality

### 1. Perform Search
```http
POST /api/search
Authorization: Bearer {token}
Content-Type: application/json

{
  "query": "water clean environment",
  "filters": {
    "status": ["active"],
    "organization_id": [1, 2],
    "category": ["environment", "health"]
  },
  "sort": {
    "field": "end_date",
    "direction": "asc"
  },
  "facets": ["status", "category", "organization_id"],
  "highlight": true,
  "limit": 20
}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Search",
  "@id": "/api/search/12345",
  "@type": "Search",
  "query": "water clean environment",
  "results": [
    {
      "id": 1,
      "title": "Clean Water Initiative",
      "description": "Providing clean water access to rural communities",
      "status": "active",
      "category": "environment",
      "goal_amount": 50000.00,
      "current_amount": 32500.00,
      "organization": {
        "id": 1,
        "name": "Global Water Foundation"
      },
      "relevance_score": 0.95
    }
  ],
  "facets": {
    "status": {
      "active": 15,
      "completed": 3
    },
    "category": {
      "environment": 12,
      "health": 6
    },
    "organization_id": {
      "1": 8,
      "2": 10
    }
  },
  "totalResults": 18,
  "processingTime": 0.025,
  "highlights": {
    "1": {
      "title": ["<mark>Clean Water</mark> Initiative"],
      "description": ["Providing <mark>clean water</mark> access to rural communities"]
    }
  }
}
```

### 2. Get Search Suggestions
```http
GET /api/search/suggestions?query=wat
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Search",
  "@id": "/api/search/suggestions",
  "@type": "Search",
  "query": "wat",
  "suggestions": [
    "water",
    "water clean",
    "water initiative",
    "water access",
    "water conservation"
  ]
}
```

### 3. Get Search Facets
```http
GET /api/search/facets
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "@context": "/api/contexts/Search",
  "@id": "/api/search/facets",
  "@type": "Search",
  "facets": {
    "status": {
      "active": 125,
      "completed": 89,
      "draft": 15
    },
    "category": {
      "environment": 45,
      "education": 38,
      "health": 32,
      "technology": 25
    },
    "organization_type": {
      "non-profit": 95,
      "corporate": 45,
      "government": 15
    }
  }
}
```

---

## Webhook Management

### 1. List Webhooks
```http
GET /api/webhooks
Authorization: Bearer {token}
```

### 2. Create Webhook
```http
POST /api/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://example.com/webhooks/donations",
  "events": ["donation.created", "donation.completed"],
  "secret": "your-webhook-secret",
  "active": true
}
```

### 3. Update Webhook
```http
PUT /api/webhooks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://example.com/webhooks/updated",
  "events": ["donation.created", "donation.completed", "campaign.created"],
  "active": true
}
```

### 4. Delete Webhook
```http
DELETE /api/webhooks/{id}
Authorization: Bearer {token}
```

---

## Error Handling

### Error Response Format
All API errors follow the JSON-LD format:

```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Detailed error message explaining what went wrong",
  "status": 400,
  "detail": "Validation failed for field 'email': must be a valid email address",
  "violations": [
    {
      "propertyPath": "email",
      "message": "This value is not a valid email address.",
      "code": "INVALID_EMAIL"
    }
  ],
  "trace": [] // Only in development environment
}
```

### HTTP Status Codes

| Code | Description | Usage |
|------|-------------|-------|
| 200 | OK | Successful GET, PUT, PATCH requests |
| 201 | Created | Successful POST requests |
| 204 | No Content | Successful DELETE requests |
| 400 | Bad Request | Invalid request syntax or parameters |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Valid token but insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

### Validation Error Example
```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "Validation Failed",
  "hydra:description": "The request contains invalid data",
  "status": 422,
  "violations": [
    {
      "propertyPath": "goal_amount",
      "message": "This value should be greater than 0.",
      "code": "GREATER_THAN_ZERO_ERROR"
    },
    {
      "propertyPath": "end_date",
      "message": "The end date must be after the start date.",
      "code": "DATE_RANGE_ERROR"
    }
  ]
}
```

### Authentication Error Example
```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "Unauthorized",
  "hydra:description": "Authentication token is missing or invalid",
  "status": 401,
  "detail": "Bearer token not provided in Authorization header"
}
```

---

## Rate Limiting

### Rate Limit Headers
Every API response includes rate limiting information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642694400
X-RateLimit-Retry-After: 60
```

### Rate Limits by Authentication

| User Type | Requests per Minute | Burst Limit |
|-----------|-------------------|-------------|
| Anonymous | 30 | 40 |
| Authenticated Employee | 60 | 80 |
| Admin/Manager | 120 | 150 |
| API Service Account | 300 | 400 |

### Rate Limit Exceeded Response
```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "Too Many Requests",
  "hydra:description": "Rate limit exceeded. Please try again later.",
  "status": 429,
  "detail": "Maximum 60 requests per minute allowed. Limit resets at 2024-01-20T15:00:00Z",
  "retry_after": 45
}
```

---

## OpenAPI Specification

### Interactive Documentation
- **Swagger UI**: `GET /api` - Interactive API documentation
- **OpenAPI JSON**: `GET /api/docs.json` - Machine-readable OpenAPI 3.0 specification
- **JSON-LD Context**: `GET /api/contexts/{Resource}` - Resource context definitions

### API Versioning
The API supports versioning through the `Accept` header:

```http
Accept: application/vnd.api+json;version=1
```

Current versions:
- **v1**: Current stable version (default)
- **v2**: Beta version with breaking changes

### Content Types
Supported content types:
- `application/json` - Standard JSON format
- `application/ld+json` - JSON-LD format with linked data
- `application/hal+json` - HAL (Hypertext Application Language) format

---

## Performance and Caching

### Response Times (95th percentile)
- Simple GET requests: < 50ms
- Complex queries with filters: < 200ms
- Write operations (POST/PUT/PATCH): < 300ms
- Search queries (Meilisearch): < 100ms

### Caching Strategy
- **ETags**: Client-side caching for individual resources
- **Cache-Control**: Server-side cache directives
- **Redis**: API response caching (60s TTL for collections)
- **CDN**: Static asset caching

### ETag Example
```http
HTTP/1.1 200 OK
ETag: "33a64df551425fcc55e4d42a148795d9f25f89d4"
Cache-Control: max-age=300, must-revalidate
```

---

## Security

### HTTPS Requirements
- All API traffic MUST use HTTPS in production
- HTTP requests are automatically redirected to HTTPS
- TLS 1.2+ required

### CORS Configuration
```http
Access-Control-Allow-Origin: https://app.yourdomain.com, https://admin.yourdomain.com
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With
Access-Control-Max-Age: 86400
```

### Data Encryption
- Sensitive data encrypted at rest using AES-256
- Payment processing is PCI DSS compliant
- Personal data follows GDPR compliance standards

---

## Testing and Development

### Development Environment
```bash
# Start development server
php artisan serve

# Access API documentation
curl http://localhost:8000/api

# Test authentication
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```


### API Testing
```bash
# Run API tests
./vendor/bin/pest tests/Feature/Api/

# Run specific endpoint tests
./vendor/bin/pest tests/Feature/Api/CampaignTest.php

# Test with coverage
./vendor/bin/pest tests/Feature/Api/ --coverage
```

---

## Support and Resources

### Documentation Links
- **Interactive API**: `/api`
- **OpenAPI Spec**: `/api/docs.json`
- **Architecture Docs**: `/docs/architecture/`
- **Development Guide**: `/docs/development/`

### Contact Information
- **Technical Support**: info@go2digit.al

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved