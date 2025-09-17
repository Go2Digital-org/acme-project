<?php

declare(strict_types=1);

return [
    // General API messages
    'success' => 'Request completed successfully',
    'error' => 'An error occurred while processing your request',
    'validation_failed' => 'The given data was invalid',
    'resource_not_found' => 'The requested resource was not found',
    'unauthorized' => 'Authentication is required to access this resource',
    'forbidden' => 'You do not have permission to access this resource',
    'rate_limit_exceeded' => 'Too many requests. Please try again later',
    'server_error' => 'Internal server error occurred',

    // Pagination messages
    'pagination' => [
        'showing_results' => 'Showing :from to :to of :total results',
        'page_not_found' => 'The requested page does not exist',
        'invalid_page_size' => 'Invalid page size. Must be between :min and :max',
    ],

    // Campaign messages
    'campaigns' => [
        'created' => 'Campaign created successfully',
        'updated' => 'Campaign updated successfully',
        'deleted' => 'Campaign deleted successfully',
        'activated' => 'Campaign activated successfully',
        'completed' => 'Campaign completed successfully',
        'not_found' => 'Campaign not found',
        'goal_reached' => 'Campaign goal has been reached',
        'expired' => 'Campaign has expired',
        'inactive' => 'Campaign is not active',
        'search_results' => 'Found :count campaigns matching your search',
        'no_results' => 'No campaigns found matching your criteria',
    ],

    // Donation messages
    'donations' => [
        'created' => 'Donation created successfully',
        'processed' => 'Donation processed successfully',
        'cancelled' => 'Donation cancelled successfully',
        'refunded' => 'Donation refunded successfully',
        'failed' => 'Donation processing failed',
        'not_found' => 'Donation not found',
        'receipt_generated' => 'Donation receipt generated successfully',
        'minimum_amount' => 'Minimum donation amount is :amount',
        'maximum_amount' => 'Maximum donation amount is :amount',
        'campaign_inactive' => 'Cannot donate to inactive campaign',
        'goal_exceeded' => 'Donation would exceed campaign goal',
    ],

    // Organization messages
    'organizations' => [
        'created' => 'Organization created successfully',
        'updated' => 'Organization updated successfully',
        'verified' => 'Organization verified successfully',
        'activated' => 'Organization activated successfully',
        'deactivated' => 'Organization deactivated successfully',
        'not_found' => 'Organization not found',
        'search_results' => 'Found :count organizations matching your search',
    ],

    // Employee messages
    'employees' => [
        'profile_updated' => 'Profile updated successfully',
        'not_found' => 'Employee not found',
        'unauthorized_access' => 'You can only access your own profile',
        'campaigns_retrieved' => 'Employee campaigns retrieved successfully',
        'donations_retrieved' => 'Employee donations retrieved successfully',
    ],

    // Authentication messages
    'auth' => [
        'login_successful' => 'Login successful',
        'logout_successful' => 'Logout successful',
        'registration_successful' => 'Registration successful',
        'invalid_credentials' => 'Invalid email or password',
        'account_disabled' => 'Your account has been disabled',
        'token_expired' => 'Authentication token has expired',
        'token_invalid' => 'Invalid authentication token',
        'email_already_exists' => 'An account with this email already exists',
        'password_too_weak' => 'Password must be at least 8 characters with mixed case, numbers, and symbols',
    ],

    // Payment messages
    'payments' => [
        'processing' => 'Payment is being processed',
        'completed' => 'Payment completed successfully',
        'failed' => 'Payment failed',
        'cancelled' => 'Payment cancelled by user',
        'refunded' => 'Payment refunded successfully',
        'webhook_processed' => 'Payment webhook processed successfully',
        'invalid_signature' => 'Invalid payment webhook signature',
        'gateway_error' => 'Payment gateway error occurred',
    ],

    // Validation messages
    'validation' => [
        'required_field' => 'The :field field is required',
        'invalid_email' => 'Please provide a valid email address',
        'invalid_date' => 'Please provide a valid date',
        'invalid_amount' => 'Please provide a valid monetary amount',
        'string_too_long' => 'The :field field cannot exceed :max characters',
        'string_too_short' => 'The :field field must be at least :min characters',
        'invalid_uuid' => 'Please provide a valid identifier',
        'invalid_phone' => 'Please provide a valid phone number',
        'invalid_url' => 'Please provide a valid URL',
    ],

    // Filter and search messages
    'filters' => [
        'invalid_filter' => 'Invalid filter parameter: :filter',
        'invalid_sort' => 'Invalid sort parameter: :sort',
        'invalid_date_range' => 'Invalid date range specified',
        'unsupported_operator' => 'Unsupported filter operator: :operator',
    ],

    // Locale messages
    'locale' => [
        'unsupported' => 'Unsupported locale: :locale',
        'changed' => 'Language preference updated to :locale',
        'default_used' => 'Using default language: :locale',
    ],
];
