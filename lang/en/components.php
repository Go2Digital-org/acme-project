<?php

declare(strict_types=1);

return [
    // Currency selector component
    'currency_selector' => [
        'select_currency' => 'Select Currency',
        'current_currency' => 'Current currency: :currency',
        'available_currencies' => 'Available currencies',
        'currency_changed' => 'Currency changed to :currency',
    ],

    // SEO Preview component
    'seo_preview' => [
        'title' => 'SEO Preview',
        'description_too_long' => 'Description is :count characters (recommended: :max max)',
        'title_preview' => 'Preview title',
        'url_preview' => 'Preview URL',
        'description_preview' => 'Preview description',
        'character_count' => ':current/:max characters',
        'recommended_length' => 'Recommended length',
        'title_length' => 'Title length: :length characters',
        'description_length' => 'Description length: :length characters',
    ],

    // Validation errors component
    'validation_errors' => [
        'title' => 'Validation Errors',
        'please_fix' => 'Please fix the following errors:',
        'error_occurred' => 'An error occurred',
        'multiple_errors' => 'Multiple errors found',
    ],

    // Generic component messages
    'loading' => 'Loading...',
    'no_data' => 'No data available',
    'error_loading' => 'Error loading data',
    'retry' => 'Retry',
    'refresh' => 'Refresh',
    'close' => 'Close',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'submit' => 'Submit',
    'reset' => 'Reset',
    'clear' => 'Clear',
    'select_all' => 'Select All',
    'deselect_all' => 'Deselect All',
    'show_more' => 'Show More',
    'show_less' => 'Show Less',
    'expand' => 'Expand',
    'collapse' => 'Collapse',
    'toggle' => 'Toggle',
    'preview' => 'Preview',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'duplicate' => 'Duplicate',
    'copy' => 'Copy',
    'move' => 'Move',
    'sort' => 'Sort',
    'filter' => 'Filter',
    'search' => 'Search',

    // Status indicators
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived',

    // Form components
    'required_field' => 'Required field',
    'optional_field' => 'Optional field',
    'select_option' => 'Select an option',
    'choose_file' => 'Choose file',
    'no_file_selected' => 'No file selected',
    'file_selected' => 'File selected',
    'upload_file' => 'Upload file',
    'remove_file' => 'Remove file',
    'drag_drop_files' => 'Drag and drop files here',
    'browse_files' => 'Browse files',
    'max_file_size' => 'Maximum file size: :size',
    'allowed_formats' => 'Allowed formats: :formats',

    // Pagination
    'showing_results' => 'Showing :first to :last of :total results',
    'no_results' => 'No results found',
    'items_per_page' => 'Items per page',
    'page' => 'Page',
    'of_pages' => 'of :total',
    'first_page' => 'First page',
    'last_page' => 'Last page',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',

    // Date and time
    'select_date' => 'Select date',
    'select_time' => 'Select time',
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'tomorrow' => 'Tomorrow',
    'this_week' => 'This week',
    'last_week' => 'Last week',
    'next_week' => 'Next week',
    'this_month' => 'This month',
    'last_month' => 'Last month',
    'next_month' => 'Next month',
    'this_year' => 'This year',
    'last_year' => 'Last year',
    'next_year' => 'Next year',

    // Confirmation dialogs
    'confirm_action' => 'Confirm Action',
    'are_you_sure' => 'Are you sure?',
    'cannot_be_undone' => 'This action cannot be undone.',
    'confirm_delete' => 'Confirm deletion',
    'delete_warning' => 'Are you sure you want to delete this item?',
    'yes_delete' => 'Yes, delete it',
    'no_cancel' => 'No, cancel',

    // Export button component
    'export' => [
        // Export type labels
        'campaigns' => 'Export Campaigns',
        'donations' => 'Export Donations',
        'reports' => 'Export Reports',
        'users' => 'Export Users',

        // Export type descriptions
        'campaigns_description' => 'Export campaign data with progress and donation stats',
        'donations_description' => 'Export donation records with donor information',
        'reports_description' => 'Export analytical reports and metrics',
        'users_description' => 'Export user accounts and profile data',

        // Export status messages
        'exporting' => 'Exporting...',
        'export_progress' => ':progress%',

        // Advanced options
        'advanced_options' => 'Advanced Options...',
        'advanced_export_options' => 'Advanced Export Options',
        'export_format' => 'Export Format',
        'date_range' => 'Date Range',
        'include_archived' => 'Include archived items',
        'include_metadata' => 'Include metadata',
        'start_export' => 'Start Export',

        // Export formats
        'csv' => 'CSV',
        'xlsx' => 'Excel (XLSX)',
        'json' => 'JSON',

        // Date range options
        'all_time' => 'All Time',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'this_quarter' => 'This Quarter',
        'this_year' => 'This Year',

        // Export messages
        'export_failed' => 'Failed to start export',
    ],
];
