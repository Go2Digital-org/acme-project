<?php

declare(strict_types=1);

return [
    // General
    'campaigns' => 'Campagnes',
    'campaign' => 'Campagne',
    'all_campaigns' => 'Alle Campagnes',
    'my_campaigns' => 'Mijn Campagnes',
    'featured_campaigns' => 'Uitgelichte Campagnes',
    'recent_campaigns' => 'Recente Campagnes',
    'popular_campaigns' => 'Populaire Campagnes',

    // Campaign details
    'title' => 'Titel',
    'description' => 'Beschrijving',
    'goal_amount' => 'Doelbedrag',
    'current_amount' => 'Huidig Bedrag',
    'target' => 'Streefbedrag',
    'category' => 'Categorie',
    'status' => 'Status',
    'created_by' => 'Aangemaakt door',
    'created_at' => 'Aangemaakt op',
    'end_date' => 'Einddatum',
    'start_date' => 'Startdatum',

    // Campaign statuses
    'status_draft' => 'Concept',
    'status_active' => 'Actief',
    'status_completed' => 'Voltooid',
    'status_cancelled' => 'Geannuleerd',
    'status_paused' => 'Gepauzeerd',

    // Campaign actions
    'create_campaign' => 'Campagne Aanmaken',
    'edit_campaign' => 'Campagne Bewerken',
    'view_campaign' => 'Campagne Bekijken',
    'delete_campaign' => 'Campagne Verwijderen',
    'publish_campaign' => 'Campagne Publiceren',
    'pause_campaign' => 'Campagne Pauzeren',
    'resume_campaign' => 'Campagne Hervatten',
    'cancel_campaign' => 'Campagne Annuleren',
    'duplicate_campaign' => 'Campagne Dupliceren',
    'share_campaign' => 'Campagne Delen',

    // Campaign creation/editing
    'campaign_details' => 'Campagnedetails',
    'basic_information' => 'Basisinformatie',
    'campaign_title' => 'Campagnetitel',
    'campaign_title_placeholder' => 'Voer een overtuigende campagnetitel in',
    'campaign_description' => 'Campagnebeschrijving',
    'campaign_description_placeholder' => 'Beschrijf uw campagne en de impact...',
    'campaign_goal_amount' => 'Doelbedrag',
    'campaign_goal_placeholder' => 'Voer streefbedrag in euro\'s in',
    'campaign_category' => 'Categorie',
    'campaign_end_date' => 'Einddatum',
    'campaign_image' => 'Campagneafbeelding',
    'upload_image' => 'Afbeelding Uploaden',
    'change_image' => 'Afbeelding Wijzigen',

    // Categories
    'categories' => 'Categorieën',
    'category_education' => 'Onderwijs',
    'category_health' => 'Gezondheid & Medisch',
    'category_environment' => 'Milieu',
    'category_animals' => 'Dieren & Natuur',
    'category_humanitarian' => 'Humanitaire Hulp',
    'category_community' => 'Gemeenschapsontwikkeling',
    'category_arts' => 'Kunst & Cultuur',
    'category_sports' => 'Sport & Recreatie',
    'category_emergency' => 'Noodhulp',
    'category_other' => 'Anders',
    'of_goal' => 'van doel',
    'days_remaining' => 'dagen over',
    'day_remaining' => 'dag over',
    'days_left' => ':count dagen over',
    'day_left' => ':count dag over',
    'expired' => 'Verlopen',
    'goal_reached' => 'Doel Bereikt!',
    'goal_exceeded' => 'Doel Overschreden!',

    // Donations on campaign
    'donors' => 'Donateurs',
    'donor_count' => ':count donateur|:count donateurs',
    'recent_donations' => 'Recente Donaties',
    'top_donors' => 'Top Donateurs',
    'anonymous_donor' => 'Anonieme Donateur',
    'first_donation' => 'Wees de eerste om te doneren!',
    'latest_donation' => 'Laatste donatie: :amount door :donor',

    // Campaign statistics
    'total_raised' => 'Totaal Opgehaald',
    'total_donors' => 'Totaal Donateurs',
    'average_donation' => 'Gemiddelde Donatie',
    'largest_donation' => 'Grootste Donatie',
    'campaign_statistics' => 'Campagnestatistieken',

    // Search and filtering
    'search_campaigns' => 'Zoek campagnes...',
    'search_placeholder' => 'Zoek op titel, beschrijving of categorie',
    'filter_by_category' => 'Filter op Categorie',
    'filter_by_status' => 'Filter op Status',
    'sort_by' => 'Sorteer op',
    'sort_newest' => 'Nieuwste Eerst',
    'sort_oldest' => 'Oudste Eerst',
    'sort_most_funded' => 'Meest Gefinancierd',
    'sort_ending_soon' => 'Bijna Afgelopen',
    'sort_goal_amount' => 'Doelbedrag',

    // Success messages
    'campaign_created' => 'Campagne succesvol aangemaakt!',
    'campaign_updated' => 'Campagne succesvol bijgewerkt!',
    'campaign_deleted' => 'Campagne succesvol verwijderd.',
    'campaign_published' => 'Campagne succesvol gepubliceerd!',
    'campaign_paused' => 'Campagne succesvol gepauzeerd.',
    'campaign_resumed' => 'Campagne succesvol hervat.',
    'campaign_cancelled' => 'Campagne succesvol geannuleerd.',

    // Error messages
    'campaign_not_found' => 'Campagne niet gevonden.',
    'cannot_edit_campaign' => 'U kunt deze campagne niet bewerken.',
    'cannot_delete_campaign' => 'Kan deze campagne niet verwijderen.',
    'goal_amount_required' => 'Doelbedrag is verplicht.',
    'invalid_goal_amount' => 'Voer een geldig doelbedrag in.',
    'end_date_required' => 'Einddatum is verplicht.',
    'end_date_future' => 'Einddatum moet in de toekomst liggen.',
    'title_required' => 'Campagnetitel is verplicht.',
    'description_required' => 'Campagnebeschrijving is verplicht.',

    // Empty states
    'no_campaigns' => 'Geen campagnes gevonden.',
    'no_campaigns_message' => 'Er zijn momenteel geen campagnes beschikbaar.',
    'create_first_campaign' => 'Maak uw eerste campagne aan',
    'no_results' => 'Geen campagnes voldoen aan uw zoekcriteria.',
    'try_different_search' => 'Probeer uw zoekterm of filters aan te passen.',

    // Sample campaign content
    'sample_education_title' => 'Wereldwijd Onderwijsinitiatief',
    'sample_education_desc' => 'Kwalitatieve onderwijsmiddelen bieden aan onderbediende gemeenschappen wereldwijd.',
    'sample_healthcare_title' => 'Medische Apparatuur Inzamelingsactie',
    'sample_healthcare_desc' => 'Essentiële medische apparatuur voor landelijke gezondheidscentra.',
    'sample_environment_title' => 'Bosherstellingsproject',
    'sample_environment_desc' => 'Ontboste gronden herbebossen om klimaatverandering tegen te gaan en biodiversiteit te herstellen.',

    // Additional Dutch-specific terms
    'donate_now' => 'Doneer Nu',
    'share' => 'Delen',
    'progress' => 'Voortgang',
    'raised' => 'opgehaald',
    'goal' => 'doel',

    // Additional keys for welcome page
    'subtitle' => 'Ontdek doelen die ertoe doen en laat je bijdrage tellen.',
    'funded' => 'Gefinancierd',
    'urgent' => 'Urgent',
    'areas_description' => 'We ondersteunen doelen in meerdere domeinen om uitgebreide positieve verandering te creëren.',
    'education_desc' => 'Ondersteuning van onderwijsprogramma\'s en infrastructuurontwikkeling wereldwijd.',
    'healthcare_desc' => 'Medische ondersteuning en zorginfrastructuur bieden aan onderbediende gemeenschappen.',
    'environment_desc' => 'Milieubehoud en duurzaamheidsinitiatieven voor onze planeet.',
    'community_desc' => 'Sterkere gemeenschappen opbouwen door sociale ontwikkelingsprogramma\'s.',

    // Social sharing
    'share_message' => 'Help de boodschap over deze geweldige campagne te verspreiden!',
    'copy_link' => 'Link Kopiëren',
    'link_copied' => 'Link Gekopieerd!',
    'copy_failed' => 'Kon de link niet kopiëren. Probeer het handmatig.',

    // Filament Resource Actions
    'approve' => 'Goedkeuren',
    'reject' => 'Afwijzen',
    'pause' => 'Pauzeren',
    'complete' => 'Voltooien',
    'cancel' => 'Annuleren',
    'new_campaign' => 'Nieuwe Campagne',
    'submit_for_approval' => 'Indienen voor Goedkeuring',
    'rejection_reason' => 'Reden van Afwijzing',

    // Filament Resource Sections
    'campaign_information' => 'Campagne Informatie',
    'campaign_information_description' => 'Basis campagnedetails en inhoud',
    'financial_details' => 'Financiële Details',
    'financial_details_description' => 'Doelbedrag en bedrijfsmatching instellingen',
    'timeline_organization' => 'Tijdlijn & Organisatie',
    'timeline_organization_description' => 'Campagnedatums en organisatorische details',
    'media_assets' => 'Media & Assets',
    'media_assets_description' => 'Campagneafbeeldingen en mediabestanden',

    // Page Titles
    'create_new_campaign' => 'Nieuwe Campagne Aanmaken',
    'edit_campaign_title' => 'Campagne Bewerken',
    'view_campaign_title' => 'Campagne Bekijken',

    // Tab Labels
    'all' => 'Alle',
    'needs_approval' => 'Vereist Goedkeuring',
    'active' => 'Actief',
    'draft' => 'Concept',
    'completed_tab' => 'Voltooid',

    // Modal Descriptions
    'approve_modal_description' => 'Dit zal de campagne goedkeuren en actief maken.',
    'reject_modal_description' => 'Dit zal de campagne afwijzen en terugsturen voor revisie.',
    'submit_approval_modal_description' => 'Dit zal alle geselecteerde conceptcampagnes indienen voor goedkeuring.',
    'bulk_approve_modal_description' => 'Dit zal alle geselecteerde campagnes goedkeuren en actief maken.',
    'bulk_reject_modal_description' => 'Dit zal alle geselecteerde campagnes afwijzen en terugsturen voor revisie.',
    'pause_modal_description' => 'Dit zal alle geselecteerde campagnes pauzeren, waardoor nieuwe donaties tijdelijk worden gestopt.',
    'complete_modal_description' => 'Dit zal alle geselecteerde campagnes markeren als voltooid.',
    'cancel_modal_description' => 'Dit zal alle geselecteerde campagnes annuleren. Deze actie kan niet ongedaan worden gemaakt.',

    // Form Labels and Helpers
    'enable_corporate_matching' => 'Bedrijfsmatching Inschakelen',
    'corporate_matching_help' => 'Het bedrijf zal werknemersdonaties evenaren',
    'corporate_matching_rate_help' => 'Percentage van werknemersdonatie om te evenaren (100% = 1:1 matching)',
    'max_corporate_matching_help' => 'Maximum totaalbedrag voor bedrijfsmatching (leeg laten voor onbeperkt)',
    'slug_help' => 'Wordt automatisch gegenereerd indien leeg gelaten',
    'category_help' => 'Selecteer de campagnecategorie',
    'current_amount_help' => 'Wordt automatisch bijgewerkt wanneer donaties worden ontvangen',
    'beneficiary_organization_help' => 'Begunstigde organisatie',
    'campaign_creator_help' => 'Campagne maker/beheerder',
    'featured_image_help' => 'Aanbevolen formaat: 1200x675px (16:9 aspectverhouding)',
    'goal_amount_help' => 'Minimum €100, Maximum €1.000.000',
    'rejection_reason_help' => 'Gelieve een duidelijke reden voor afwijzing op te geven',

    // Visibility Options
    'visibility_public' => 'Openbaar - Zichtbaar voor iedereen',
    'visibility_internal' => 'Intern - Alleen bedrijfsmedewerkers',
    'visibility_private' => 'Privé - Alleen uitgenodigde gebruikers',

    // Status Transitions
    'publish_campaign_action' => 'Campagne Publiceren',
    'mark_as_completed' => 'Markeren als Voltooid',

    // Notification Actions
    'edit_campaign_action' => 'Campagne Bewerken',
    'approve_now' => 'Nu Goedkeuren',
    'view_results' => 'Resultaten Bekijken',
    'review_campaign' => 'Campagne Beoordelen',

    // Status Display Names
    'status_pending_approval' => 'In Afwachting van Goedkeuring',
    'status_rejected' => 'Afgewezen',
    'status_expired' => 'Verlopen',

    // Export/Reports
    'created_by_export' => 'Aangemaakt Door',
    'submitted_by' => 'Ingediend Door',
    'submitted' => 'Ingediend',
    'approved_today' => 'Vandaag Goedgekeurd',
    'rejected_today' => 'Vandaag Afgewezen',
];
