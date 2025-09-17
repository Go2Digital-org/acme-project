<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Admin Panel Language Lines - Dutch
    |--------------------------------------------------------------------------
    |
    | De volgende taalregels worden gebruikt in het Filament-beheerpaneel.
    |
    */

    // Navigation Groups
    'navigation_groups' => [
        'csr_management' => 'MVO Beheer',
        'content_management' => 'Inhoudsbeheer',
        'system' => 'Systeem',
        'reports' => 'Rapporten',
    ],

    // Resources
    'resources' => [
        'campaign' => [
            'label' => 'Campagne',
            'plural' => 'Campagnes',
            'navigation_label' => 'Campagnes',
            'sections' => [
                'campaign_information' => 'Campagne Informatie',
                'campaign_information_desc' => 'Basis campagnegegevens en inhoud',
                'financial_details' => 'Financiële Details',
                'financial_details_desc' => 'Doelbedrag en bedrijfsmatching instellingen',
                'timeline_organization' => 'Tijdlijn & Organisatie',
                'timeline_organization_desc' => 'Campagnedatums en organisatiegegevens',
                'media_assets' => 'Media & Bestanden',
                'media_assets_desc' => 'Campagneafbeeldingen en mediabestanden',
            ],
            'fields' => [
                'title' => 'Titel',
                'description' => 'Beschrijving',
                'slug' => 'Slug',
                'category' => 'Categorie',
                'visibility' => 'Zichtbaarheid',
                'goal_amount' => 'Doelbedrag',
                'current_amount' => 'Huidig Bedrag',
                'has_corporate_matching' => 'Bedrijfsmatching Inschakelen',
                'corporate_matching_rate' => 'Matchingspercentage',
                'max_corporate_matching' => 'Max Bedrijfsmatching',
                'start_date' => 'Startdatum',
                'end_date' => 'Einddatum',
                'organization' => 'Organisatie',
                'employee' => 'Campagnebeheerder',
                'status' => 'Status',
                'featured_image' => 'Uitgelichte Afbeelding',
            ],
        ],
        'organization' => [
            'label' => 'Organisatie',
            'plural' => 'Organisaties',
            'navigation_label' => 'Organisaties',
            'sections' => [
                'organization_information' => 'Organisatie Informatie',
                'organization_information_desc' => 'Basis organisatiegegevens en contactinformatie',
                'legal_registration' => 'Juridisch & Registratie',
                'legal_registration_desc' => 'Juridische informatie en registratiegegevens',
                'contact_information' => 'Contactinformatie',
                'contact_information_desc' => 'Contactgegevens en fysieke locatie',
            ],
            'fields' => [
                'name' => 'Naam',
                'description' => 'Beschrijving',
                'mission' => 'Missie',
                'category' => 'Categorie',
                'registration_number' => 'Registratienummer',
                'tax_id' => 'BTW-nummer',
                'is_verified' => 'Geverifieerde Organisatie',
                'verification_date' => 'Verificatiedatum',
                'is_active' => 'Actieve Status',
                'website' => 'Website',
                'email' => 'E-mail',
                'phone' => 'Telefoon',
                'address' => 'Adres',
                'city' => 'Stad',
                'postal_code' => 'Postcode',
                'country' => 'Land',
                'logo_url' => 'Logo',
            ],
        ],
        'donation' => [
            'label' => 'Donatie',
            'plural' => 'Donaties',
            'navigation_label' => 'Donaties',
            'sections' => [
                'donation_information' => 'Donatie Informatie',
                'donation_information_desc' => 'Basis donatiegegevens en transactie-info',
                'payment_details' => 'Betalingsdetails',
                'payment_details_desc' => 'Betaalmethode en gateway informatie',
                'additional_settings' => 'Extra Instellingen',
                'additional_settings_desc' => 'Privacy en terugkerende donatie-instellingen',
                'timestamps' => 'Tijdstempels',
                'timestamps_desc' => 'Belangrijke datums en verwerkingstijdlijn',
            ],
            'fields' => [
                'campaign' => 'Campagne',
                'employee' => 'Donateur',
                'amount' => 'Bedrag',
                'currency' => 'Valuta',
                'payment_method' => 'Betaalmethode',
                'payment_gateway' => 'Betalingsgateway',
                'transaction_id' => 'Transactie-ID',
                'status' => 'Status',
                'anonymous' => 'Anonieme Donatie',
                'recurring' => 'Terugkerende Donatie',
                'recurring_frequency' => 'Terugkeerfrequentie',
                'notes' => 'Opmerkingen',
                'donated_at' => 'Donatiedatum',
                'processed_at' => 'Verwerkingsdatum',
                'completed_at' => 'Voltooiingsdatum',
            ],
        ],
        'page' => [
            'label' => 'Pagina',
            'plural' => "Pagina's",
            'navigation_label' => "Pagina's",
            'sections' => [
                'page_information' => 'Pagina Informatie',
                'page_information_desc' => 'Basis paginagegevens en instellingen',
                'page_content' => 'Pagina-inhoud',
                'page_content_desc' => 'Pagina-inhoud in meerdere talen',
                'seo_meta' => 'SEO & Meta',
                'seo_meta_desc' => 'Zoekmachine optimalisatie en metadata',
                'seo_preview' => 'SEO Voorbeeld',
                'seo_preview_desc' => 'Hoe uw pagina verschijnt in zoekresultaten',
            ],
            'fields' => [
                'title' => 'Paginatitel',
                'slug' => 'Slug',
                'status' => 'Status',
                'template' => 'Sjabloon',
                'order' => 'Volgorde',
                'content' => 'Inhoud',
                'meta_description' => 'Meta Beschrijving',
                'meta_keywords' => 'Meta Trefwoorden',
            ],
        ],
        'user' => [
            'label' => 'Gebruiker',
            'plural' => 'Gebruikers',
            'navigation_label' => 'Gebruikers',
        ],
        'role' => [
            'label' => 'Rol',
            'plural' => 'Rollen',
            'navigation_label' => 'Rollen',
        ],
    ],

    // Common Actions
    'actions' => [
        'create' => 'Aanmaken',
        'edit' => 'Bewerken',
        'delete' => 'Verwijderen',
        'view' => 'Bekijken',
        'save' => 'Opslaan',
        'cancel' => 'Annuleren',
        'filter' => 'Filteren',
        'search' => 'Zoeken',
        'export' => 'Exporteren',
        'import' => 'Importeren',
        'refresh' => 'Vernieuwen',
        'bulk_delete' => 'Geselecteerde Verwijderen',
    ],

    // Common Messages
    'messages' => [
        'created' => 'Record succesvol aangemaakt',
        'updated' => 'Record succesvol bijgewerkt',
        'deleted' => 'Record succesvol verwijderd',
        'saved' => 'Wijzigingen succesvol opgeslagen',
        'error' => 'Er is een fout opgetreden',
        'confirm_delete' => 'Weet u zeker dat u dit record wilt verwijderen?',
        'no_records' => 'Geen records gevonden',
    ],

    // Status Labels
    'statuses' => [
        'draft' => 'Concept',
        'published' => 'Gepubliceerd',
        'active' => 'Actief',
        'inactive' => 'Inactief',
        'pending' => 'In Afwachting',
        'approved' => 'Goedgekeurd',
        'rejected' => 'Afgewezen',
        'completed' => 'Voltooid',
        'cancelled' => 'Geannuleerd',
        'paused' => 'Gepauzeerd',
    ],

    // Categories
    'categories' => [
        'education' => 'Onderwijs',
        'health' => 'Gezondheid & Medisch',
        'environment' => 'Milieu',
        'community' => 'Gemeenschapsontwikkeling',
        'disaster_relief' => 'Noodhulp',
        'poverty' => 'Armoedebestrijding',
        'animal_welfare' => 'Dierenwelzijn',
        'human_rights' => 'Mensenrechten',
        'arts_culture' => 'Kunst & Cultuur',
        'sports' => 'Sport & Recreatie',
        'charity' => 'Liefdadigheid',
        'non_profit' => 'Non-Profit',
        'ngo' => 'NGO',
        'foundation' => 'Stichting',
        'other' => 'Overig',
    ],

    // Visibility Options
    'visibility' => [
        'public' => 'Openbaar - Zichtbaar voor iedereen',
        'internal' => 'Intern - Alleen bedrijfsmedewerkers',
        'private' => 'Privé - Alleen uitgenodigde gebruikers',
    ],

    // Payment Methods
    'payment_methods' => [
        'credit_card' => 'Creditcard',
        'bank_transfer' => 'Bankoverschrijving',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'mollie' => 'Mollie',
    ],

    // Recurring Frequencies
    'recurring_frequencies' => [
        'weekly' => 'Wekelijks',
        'monthly' => 'Maandelijks',
        'quarterly' => 'Per Kwartaal',
        'yearly' => 'Jaarlijks',
    ],

    // Helper Texts
    'helpers' => [
        'slug_auto_generated' => 'Wordt automatisch gegenereerd indien leeg gelaten',
        'current_amount_readonly' => 'Automatisch bijgewerkt bij ontvangst van donaties',
        'corporate_matching_help' => 'Bedrijf matcht werknemersdonaties',
        'matching_rate_help' => 'Percentage van werknemersdonatie om te matchen (100% = 1:1 matching)',
        'max_matching_help' => 'Maximaal totaalbedrag bedrijfsmatching (laat leeg voor onbeperkt)',
        'anonymous_help' => 'Verberg donateursnaam in openbare weergaven',
        'recurring_help' => 'Stel automatische terugkerende donaties in',
        'featured_image_help' => 'Aanbevolen grootte: 1200x675px (16:9 beeldverhouding)',
        'meta_description_help' => 'Korte beschrijving voor zoekmachines (max 160 tekens)',
        'keywords_help' => 'Trefwoorden gescheiden door kommas',
    ],

    // Payment Gateway Configuration
    'payment_gateway_configuration_guide' => 'Configuratiegids voor Betaalpoorten',
    'stripe_configuration' => 'Stripe Configuratie',
    'mollie_configuration' => 'Mollie Configuratie',
    'security_notes' => 'Beveiligingsnotities',
    'api_key' => 'API-sleutel',
    'webhook_secret' => 'Webhook Secret',
    'webhook_url' => 'Webhook URL',
    'publishable_key' => 'Publiceerbare Sleutel',
    'your_secret_api_key_stripe' => 'Je geheime API-sleutel uit het Stripe dashboard',
    'endpoint_signing_secret' => 'Endpoint ondertekeningssecret voor webhook validatie',
    'your_application_webhook_endpoint' => 'Het webhook endpoint van je applicatie',
    'publishable_key_client_side' => 'Je publiceerbare sleutel voor client-side integratie',
    'your_live_test_api_key_mollie' => 'Je live of test API-sleutel uit het Mollie dashboard',
    'secret_for_webhook_validation' => 'Secret voor webhook validatie',
    'all_sensitive_data_encrypted' => 'Alle gevoelige gegevens worden automatisch versleuteld in de database',
    'test_configuration_before_activating' => 'Test je configuratie voordat je de betaalpoort activeert',
    'use_test_mode_during_development' => 'Gebruik testmodus tijdens ontwikkeling en testen',
    'keep_api_keys_secure' => 'Houd je API-sleutels veilig en roteer ze regelmatig',
];
