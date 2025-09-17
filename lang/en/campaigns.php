<?php

declare(strict_types=1);

return [
    // General
    'campaigns' => 'Campaigns',
    'campaign' => 'Campaign',
    'all_campaigns' => 'All Campaigns',
    'my_campaigns' => 'My Campaigns',
    'featured_campaigns' => 'Featured Campaigns',
    'recent_campaigns' => 'Recent Campaigns',
    'popular_campaigns' => 'Popular Campaigns',

    // Campaign details
    'title' => 'Title',
    'description' => 'Description',
    'goal' => 'Goal',
    'goal_amount' => 'Goal Amount',
    'current_amount' => 'Current Amount',
    'raised' => 'Raised',
    'progress' => 'Progress',
    'target' => 'Target',
    'category' => 'Category',
    'status' => 'Status',
    'created_by' => 'Created by',
    'created_at' => 'Created on',
    'end_date' => 'End Date',
    'start_date' => 'Start Date',

    // Campaign statuses
    'status_draft' => 'Draft',
    'status_active' => 'Active',
    'status_completed' => 'Completed',
    'status_cancelled' => 'Cancelled',
    'status_paused' => 'Paused',

    // Campaign actions
    'create_campaign' => 'Create Campaign',
    'edit_campaign' => 'Edit Campaign',
    'view_campaign' => 'View Campaign',
    'delete_campaign' => 'Delete Campaign',
    'publish_campaign' => 'Publish Campaign',
    'pause_campaign' => 'Pause Campaign',
    'resume_campaign' => 'Resume Campaign',
    'cancel_campaign' => 'Cancel Campaign',
    'duplicate_campaign' => 'Duplicate Campaign',
    'share_campaign' => 'Share Campaign',
    'browse_campaigns' => 'Browse Campaigns',

    // Campaign creation/editing
    'campaign_details' => 'Campaign Details',
    'basic_information' => 'Basic Information',
    'campaign_title' => 'Campaign Title',
    'campaign_title_placeholder' => 'Enter a compelling campaign title',
    'campaign_description' => 'Campaign Description',
    'campaign_description_placeholder' => 'Describe your campaign and its impact...',
    'campaign_goal_amount' => 'Goal Amount',
    'campaign_goal_placeholder' => 'Enter target amount in EUR',
    'campaign_category' => 'Category',
    'campaign_end_date' => 'End Date',
    'campaign_image' => 'Campaign Image',
    'upload_image' => 'Upload Image',
    'change_image' => 'Change Image',

    // Categories
    'categories' => 'Categories',
    'category_education' => 'Education',
    'category_health' => 'Health & Medical',
    'category_environment' => 'Environment',
    'category_animals' => 'Animals & Wildlife',
    'category_humanitarian' => 'Humanitarian Aid',
    'category_community' => 'Community Development',
    'category_arts' => 'Arts & Culture',
    'category_sports' => 'Sports & Recreation',
    'category_emergency' => 'Emergency Relief',
    'category_other' => 'Other',
    'of_goal' => 'of goal',
    'days_remaining' => 'days remaining',
    'day_remaining' => 'day remaining',
    'days_left' => ':count days left',
    'day_left' => ':count day left',
    'expired' => 'Expired',
    'goal_reached' => 'Goal Reached!',
    'goal_exceeded' => 'Goal Exceeded!',

    // Donations on campaign
    'donors' => 'Donors',
    'donor_count' => ':count donor|:count donors',
    'recent_donations' => 'Recent Donations',
    'top_donors' => 'Top Donors',
    'anonymous_donor' => 'Anonymous Donor',
    'first_donation' => 'Be the first to donate!',
    'latest_donation' => 'Latest donation: :amount by :donor',

    // Campaign statistics
    'total_raised' => 'Total Raised',
    'total_donors' => 'Total Donors',
    'average_donation' => 'Average Donation',
    'largest_donation' => 'Largest Donation',
    'campaign_statistics' => 'Campaign Statistics',

    // Search and filtering
    'search_campaigns' => 'Search campaigns...',
    'search_placeholder' => 'Search by title, description, or category',
    'filter_by_category' => 'Filter by Category',
    'filter_by_status' => 'Filter by Status',
    'sort_by' => 'Sort by',
    'sort_newest' => 'Newest First',
    'sort_oldest' => 'Oldest First',
    'sort_most_funded' => 'Most Funded',
    'sort_ending_soon' => 'Ending Soon',
    'sort_goal_amount' => 'Goal Amount',

    // Success messages
    'campaign_created' => 'Campaign created successfully!',
    'campaign_draft_saved' => 'Campaign saved as draft! You can continue editing before submitting for approval.',
    'campaign_submitted_for_approval' => 'Campaign submitted for approval! You will be notified once it has been reviewed.',
    'campaign_updated' => 'Campaign updated successfully!',
    'campaign_deleted' => 'Campaign deleted successfully.',
    'campaign_published' => 'Campaign published successfully!',
    'campaign_paused' => 'Campaign paused successfully.',
    'campaign_resumed' => 'Campaign resumed successfully.',
    'campaign_cancelled' => 'Campaign cancelled successfully.',

    // Error messages
    'campaign_not_found' => 'Campaign not found.',
    'cannot_edit_campaign' => 'You cannot edit this campaign.',
    'cannot_delete_campaign' => 'Cannot delete this campaign.',
    'goal_amount_required' => 'Goal amount is required.',
    'invalid_goal_amount' => 'Please enter a valid goal amount.',
    'end_date_required' => 'End date is required.',
    'end_date_future' => 'End date must be in the future.',
    'title_required' => 'Campaign title is required.',
    'description_required' => 'Campaign description is required.',

    // Validation messages
    'title_max_length' => 'Title cannot exceed :max characters.',
    'description_max_length' => 'Description cannot exceed :max characters.',
    'goal_amount_positive' => 'Goal amount must be greater than zero.',
    'image_required' => 'Campaign image is required.',
    'image_format' => 'Image must be in JPG, PNG, or GIF format.',
    'image_size' => 'Image size cannot exceed :size.',

    // Empty states
    'no_campaigns' => 'No campaigns found.',
    'no_campaigns_message' => 'There are currently no campaigns available.',
    'create_first_campaign' => 'Create your first campaign',
    'no_results' => 'No campaigns match your search criteria.',
    'try_different_search' => 'Try adjusting your search or filters.',

    // Campaign impact
    'impact' => 'Impact',
    'impact_story' => 'Impact Story',
    'how_funds_used' => 'How funds will be used',
    'expected_outcome' => 'Expected Outcome',
    'beneficiaries' => 'Beneficiaries',
    'organization' => 'Organization',
    'contact_info' => 'Contact Information',

    // Admin actions
    'approve_campaign' => 'Approve Campaign',
    'reject_campaign' => 'Reject Campaign',
    'feature_campaign' => 'Feature Campaign',
    'unfeature_campaign' => 'Remove from Featured',
    'campaign_approved' => 'Campaign approved successfully.',
    'campaign_rejected' => 'Campaign rejected.',
    'campaign_featured' => 'Campaign featured successfully.',
    'campaign_unfeatured' => 'Campaign removed from featured.',

    // Filament Resource Actions
    'approve' => 'Approve',
    'reject' => 'Reject',
    'pause' => 'Pause',
    'complete' => 'Complete',
    'cancel' => 'Cancel',
    'new_campaign' => 'New Campaign',
    'submit_for_approval' => 'Submit for Approval',
    'rejection_reason' => 'Rejection Reason',

    // Filament Resource Sections
    'campaign_information' => 'Campaign Information',
    'campaign_information_description' => 'Basic campaign details and content',
    'financial_details' => 'Financial Details',
    'financial_details_description' => 'Goal amount and corporate matching settings',
    'timeline_organization' => 'Timeline & Organization',
    'timeline_organization_description' => 'Campaign dates and organizational details',
    'media_assets' => 'Media & Assets',
    'media_assets_description' => 'Campaign images and media files',

    // Page Titles
    'create_new_campaign' => 'Create New Campaign',
    'edit_campaign_title' => 'Edit Campaign',
    'view_campaign_title' => 'View Campaign',

    // Tab Labels
    'all' => 'All',
    'needs_approval' => 'Needs Approval',
    'active' => 'Active',
    'draft' => 'Draft',
    'completed_tab' => 'Completed',

    // Modal Descriptions
    'approve_modal_description' => 'This will approve the campaign and make it active.',
    'reject_modal_description' => 'This will reject the campaign and send it back for revision.',
    'submit_approval_modal_description' => 'This will submit all selected draft campaigns for approval.',
    'bulk_approve_modal_description' => 'This will approve all selected campaigns and make them active.',
    'bulk_reject_modal_description' => 'This will reject all selected campaigns and send them back for revision.',
    'pause_modal_description' => 'This will pause all selected campaigns, temporarily stopping new donations.',
    'complete_modal_description' => 'This will mark all selected campaigns as completed.',
    'cancel_modal_description' => 'This will cancel all selected campaigns. This action cannot be undone.',

    // Form Labels and Helpers
    'enable_corporate_matching' => 'Enable Corporate Matching',
    'corporate_matching_help' => 'Company will match employee donations',
    'corporate_matching_rate_help' => 'Percentage of employee donation to match (100% = 1:1 matching)',
    'max_corporate_matching_help' => 'Maximum total corporate matching amount (leave empty for unlimited)',
    'slug_help' => 'Will be auto-generated if left empty',
    'category_help' => 'Select the campaign category',
    'current_amount_help' => 'Updated automatically as donations are received',
    'beneficiary_organization_help' => 'Beneficiary organization',
    'campaign_creator_help' => 'Campaign creator/manager',
    'featured_image_help' => 'Recommended size: 1200x675px (16:9 aspect ratio)',
    'goal_amount_help' => 'Minimum €100, Maximum €1,000,000',
    'rejection_reason_help' => 'Please provide a clear reason for rejection',

    // Visibility Options
    'visibility_public' => 'Public - Visible to all',
    'visibility_internal' => 'Internal - Company employees only',
    'visibility_private' => 'Private - Invited users only',

    // Status Transitions
    'publish_campaign_action' => 'Publish Campaign',
    'mark_as_completed' => 'Mark as Completed',

    // Notification Actions
    'edit_campaign_action' => 'Edit Campaign',
    'approve_now' => 'Approve Now',
    'view_results' => 'View Results',
    'review_campaign' => 'Review Campaign',

    // Status Display Names
    'status_pending_approval' => 'Pending Approval',
    'status_rejected' => 'Rejected',
    'status_expired' => 'Expired',

    // Export/Reports
    'created_by_export' => 'Created By',
    'submitted_by' => 'Submitted By',
    'submitted' => 'Submitted',
    'approved_today' => 'Approved Today',
    'rejected_today' => 'Rejected Today',

    // Sample campaign content
    'sample_education_title' => 'Global Education Initiative',
    'sample_education_desc' => 'Providing quality education resources to underserved communities worldwide.',
    'sample_healthcare_title' => 'Medical Equipment Drive',
    'sample_healthcare_desc' => 'Essential medical equipment for rural healthcare centers.',
    'sample_environment_title' => 'Forest Restoration Project',
    'sample_environment_desc' => 'Reforesting degraded lands to combat climate change and restore biodiversity.',

    // Additional campaign terms
    'donate' => 'Donate',
    'donate_to_campaign' => 'Donate to Campaign',
    'support_campaign' => 'Support Campaign',
    'campaign_updates' => 'Campaign Updates',
    'update_donors' => 'Update Donors',
    'thank_donors' => 'Thank Donors',
    'view_donations' => 'View Donations',
    'donation_history' => 'Donation History',

    // Campaign visibility
    'public_campaign' => 'Public Campaign',
    'private_campaign' => 'Private Campaign',
    'visibility' => 'Visibility',
    'make_public' => 'Make Public',
    'make_private' => 'Make Private',

    // More validation messages
    'category_required' => 'Please select a campaign category.',
    'start_date_required' => 'Start date is required.',
    'start_date_future' => 'Start date must be in the future.',
    'invalid_date_range' => 'End date must be after the start date.',

    // Additional keys for welcome page
    'subtitle' => 'Discover causes that matter and make your contribution count.',
    'funded' => 'Funded',
    'urgent' => 'Urgent',
    'areas_description' => 'We support causes across multiple domains to create comprehensive positive change.',
    'education_desc' => 'Supporting educational programs and infrastructure development worldwide.',
    'healthcare_desc' => 'Providing medical support and healthcare infrastructure to underserved communities.',
    'environment_desc' => 'Environmental conservation and sustainability initiatives for our planet.',
    'community_desc' => 'Building stronger communities through social development programs.',

    // Social sharing
    'share_message' => 'Help spread the word about this amazing campaign!',
    'copy_link' => 'Copy Link',
    'link_copied' => 'Link Copied!',
    'copy_failed' => 'Failed to copy link. Please try manually.',
];
