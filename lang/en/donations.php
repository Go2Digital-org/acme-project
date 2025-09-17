<?php

declare(strict_types=1);

return [
    // General
    'donations' => 'Donations',
    'donation' => 'Donation',
    'donate' => 'Donate',
    'donate_now' => 'Donate Now',
    'make_donation' => 'Make a Donation',
    'my_donations' => 'My Donations',
    'recent_donations' => 'Recent Donations',
    'all_donations' => 'All Donations',

    // Create donation page
    'create_title' => 'Make a Donation',
    'create_description' => 'Support a campaign and make a difference',
    'form_coming_soon' => 'Donation form coming soon',

    // View donation page
    'view_title' => 'Donation Details',
    'details_coming_soon' => 'Donation details coming soon',

    // Donation form
    'donation_amount' => 'Donation Amount',
    'amount' => 'Amount',
    'amount_placeholder' => 'Enter amount',
    'select_amount' => 'Select Amount',
    'custom_amount' => 'Custom Amount',
    'suggested_amounts' => 'Suggested Amounts',
    'minimum_donation' => 'Minimum donation: :amount',
    'maximum_donation' => 'Maximum donation: :amount',

    // Predefined amounts
    'amount_25' => '€25',
    'amount_50' => '€50',
    'amount_100' => '€100',
    'amount_250' => '€250',
    'amount_500' => '€500',
    'amount_1000' => '€1,000',

    // Donation details
    'donor_name' => 'Donor Name',
    'donor_email' => 'Email Address',
    'anonymous_donation' => 'Make this donation anonymous',
    'donation_message' => 'Message (Optional)',
    'donation_message_placeholder' => 'Leave a message of support...',
    'personal_message' => 'Personal Message',
    'public_message' => 'This message will be visible to others',

    // Payment information
    'payment_information' => 'Payment Information',
    'payment_method' => 'Payment Method',
    'credit_card' => 'Credit/Debit Card',
    'bank_transfer' => 'Bank Transfer',
    'paypal' => 'PayPal',
    'card_number' => 'Card Number',
    'card_number_placeholder' => '1234 5678 9012 3456',
    'expiry_date' => 'Expiry Date',
    'expiry_placeholder' => 'MM/YY',
    'cvv' => 'CVV',
    'cvv_placeholder' => '123',
    'cardholder_name' => 'Cardholder Name',
    'cardholder_name_placeholder' => 'Name on card',

    // Billing information
    'billing_information' => 'Billing Information',
    'billing_address' => 'Billing Address',
    'address_line_1' => 'Address Line 1',
    'address_line_2' => 'Address Line 2 (Optional)',
    'city' => 'City',
    'postal_code' => 'Postal Code',
    'country' => 'Country',
    'same_as_billing' => 'Same as billing address',

    // Donation processing
    'processing_donation' => 'Processing your donation...',
    'please_wait' => 'Please wait while we process your payment.',
    'do_not_refresh' => 'Please do not refresh or close this page.',
    'secure_payment' => 'Secure Payment',
    'ssl_encrypted' => 'SSL Encrypted',

    // Donation confirmation
    'donation_successful' => 'Donation Successful!',
    'thank_you_donation' => 'Thank you for your generous donation!',
    'donation_confirmation' => 'Donation Confirmation',
    'donation_id' => 'Donation ID',
    'transaction_id' => 'Transaction ID',
    'donation_receipt' => 'Donation Receipt',
    'email_receipt' => 'A receipt has been sent to your email address.',
    'donation_details' => 'Donation Details',
    'donated_to' => 'Donated to',
    'donation_date' => 'Donation Date',
    'donation_time' => 'Donation Time',

    // Donation history
    'donation_history' => 'Donation History',
    'total_donated' => 'Total Donated',
    'donations_made' => 'Donations Made',
    'favorite_causes' => 'Favorite Causes',
    'donation_summary' => 'Donation Summary',
    'this_month' => 'This Month',
    'this_year' => 'This Year',
    'all_time' => 'All Time',

    // Donation status
    'status' => 'Status',
    'status_pending' => 'Pending',
    'status_completed' => 'Completed',
    'status_failed' => 'Failed',
    'status_refunded' => 'Refunded',
    'status_cancelled' => 'Cancelled',

    // Recurring donations
    'recurring_donation' => 'Recurring Donation',
    'make_recurring' => 'Make this a recurring donation',
    'frequency' => 'Frequency',
    'monthly' => 'Monthly',
    'quarterly' => 'Quarterly',
    'annually' => 'Annually',
    'next_donation' => 'Next donation',
    'manage_recurring' => 'Manage Recurring Donations',
    'cancel_recurring' => 'Cancel Recurring Donation',
    'pause_recurring' => 'Pause Recurring Donation',
    'resume_recurring' => 'Resume Recurring Donation',

    // Donation impact
    'your_impact' => 'Your Impact',
    'impact_message' => 'Your :amount donation can help:',
    'lives_impacted' => 'Lives Impacted',
    'impact_stories' => 'Impact Stories',

    // Gift donations
    'gift_donation' => 'Gift Donation',
    'donate_on_behalf' => 'Donate on behalf of someone',
    'recipient_name' => 'Recipient Name',
    'recipient_email' => 'Recipient Email',
    'gift_message' => 'Gift Message',
    'send_notification' => 'Send notification to recipient',
    'honor_memory' => 'In honor/memory of',

    // Corporate donations
    'corporate_donation' => 'Corporate Donation',
    'company_name' => 'Company Name',
    'company_matching' => 'Company Matching',
    'matching_eligible' => 'This donation may be eligible for company matching',
    'contact_hr' => 'Contact HR for matching details',

    // Tax information
    'tax_information' => 'Tax Information',
    'tax_deductible' => 'This donation is tax deductible',
    'tax_receipt' => 'Tax Receipt',
    'download_receipt' => 'Download Receipt',
    'tax_id' => 'Tax ID',
    'charity_registration' => 'Charity Registration Number',

    // Error messages
    'donation_failed' => 'Donation failed. Please try again.',
    'payment_declined' => 'Payment was declined. Please check your payment details.',
    'insufficient_funds' => 'Insufficient funds. Please try a different payment method.',
    'invalid_card' => 'Invalid card details. Please check and try again.',
    'expired_card' => 'Card has expired. Please use a different card.',
    'amount_required' => 'Please enter a donation amount.',
    'amount_minimum' => 'Minimum donation amount is :amount.',
    'amount_maximum' => 'Maximum donation amount is :amount.',
    'invalid_amount' => 'Please enter a valid amount.',
    'email_required' => 'Email address is required.',
    'invalid_email' => 'Please enter a valid email address.',

    // Success messages
    'donation_completed' => 'Your donation has been completed successfully.',
    'thank_you' => 'Thank you for making a difference!',
    'donation_recorded' => 'Your donation has been recorded.',

    // Refunds
    'request_refund' => 'Request Refund',
    'refund_policy' => 'Refund Policy',
    'refund_reason' => 'Reason for Refund',
    'refund_requested' => 'Refund requested successfully.',
    'refund_processed' => 'Refund has been processed.',
    'refund_denied' => 'Refund request was denied.',

    // Admin features
    'export_donations' => 'Export Donations',
    'donation_analytics' => 'Donation Analytics',
    'top_donors' => 'Top Donors',
    'donation_trends' => 'Donation Trends',
    'average_donation' => 'Average Donation',
    'total_donations_count' => 'Total Donations',
    'donations_this_month' => 'Donations This Month',

    // Empty states
    'no_donations' => 'No donations found.',
    'no_donations_message' => 'You haven\'t made any donations yet.',
    'start_donating' => 'Start making a difference today!',
    'browse_campaigns' => 'Browse Campaigns',

    // Notifications
    'donation_reminder' => 'Don\'t forget to complete your donation',
    'donation_thank_you' => 'Thank you for your donation!',
    'recurring_reminder' => 'Your recurring donation is due soon',
    'goal_reached_notification' => 'A campaign you supported has reached its goal!',

    // Breadcrumb specific
    'create_donation' => 'Create Donation',

    // Payment result pages
    'success' => 'Success',
    'success_title' => 'Donation Successful!',
    'success_message' => 'Thank you for your generous donation. Your contribution will make a real difference.',
    'cancelled_title' => 'Donation Cancelled',
    'cancelled_message' => 'Your donation was cancelled. No payment was processed.',
    'failed_title' => 'Payment Failed',
    'failed_message' => 'We were unable to process your donation. Please try again.',
    'attempted_donation' => 'Attempted Donation',
    'campaign_info' => 'Campaign Information',
    'no_worries' => 'No worries!',
    'cancelled_explanation' => 'Your payment was cancelled and no charge was made to your account. You can try again whenever you\'re ready.',
    'what_happened' => 'What happened?',
    'payment_failed_explanation' => 'Your payment could not be processed. This can happen for various reasons:',
    'common_issues' => 'Common issues include',
    'card_declined' => 'Card was declined by your bank',
    'bank_security' => 'Bank security checks blocked the transaction',
    'network_timeout' => 'Network connection timeout',
    'need_help' => 'Need help?',
    'contact_support_message' => 'If you continue to have issues, please contact our support team with the reference number below.',
    'try_again' => 'Try Again',
    'back_to_campaign' => 'Back to Campaign',
    'view_my_donations' => 'View My Donations',
    'thank_you_title' => 'Thank you for your generosity!',
    'thank_you_message' => 'Your donation helps make a real difference in the lives of others. Together, we can create positive change.',
    'receipt_sent' => 'A confirmation email has been sent to your inbox.',
    'cancelled' => 'Cancelled',
    'failed' => 'Failed',
    'attempted_at' => 'Attempted At',
    'date' => 'Date',
    'message' => 'Message',
    'reference_number' => 'Reference Number',

    // Receipt specific translations
    'receipt' => [
        'title' => 'Donation Receipt',
        'receipt_number' => 'Receipt #:id',
        'donation_details' => 'Donation Details',
        'transaction_info' => 'Transaction information and receipt',
        'transaction_information' => 'Transaction Information',
        'campaign_information' => 'Campaign Information',
        'donor_information' => 'Donor Information',
        'date_time' => 'Date & Time',
        'anonymous_donor' => 'Anonymous Donor',
        'created_by' => 'Created by :name',
        'progress' => 'Progress',
        'name' => 'Name',
        'email' => 'Email',
        'type' => 'Type',
        'recurring_frequency' => 'Recurring :frequency',
        'tax_receipt_title' => 'Tax Receipt',
        'tax_receipt_number' => 'Tax Receipt Number',
        'tax_deductible_amount' => 'Tax Deductible Amount',
        'email_receipt' => 'Email Receipt',
        'footer_notice' => 'This receipt serves as proof of your charitable donation. Keep this for your tax records.',
        'tax_notice' => 'For tax purposes, no goods or services were provided in exchange for this donation.',
    ],

    'actions' => [
        'back_to_donations' => 'Back to Donations',
        'view_campaign' => 'View Campaign',
        'contact_support' => 'Contact Support',
    ],
];
