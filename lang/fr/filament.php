<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Admin Panel Language Lines - French
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes sont utilisées dans le panneau d'administration Filament.
    |
    */

    // Navigation Groups
    'navigation_groups' => [
        'csr_management' => 'Gestion RSE',
        'content_management' => 'Gestion de Contenu',
        'system' => 'Système',
        'reports' => 'Rapports',
    ],

    // Resources
    'resources' => [
        'campaign' => [
            'label' => 'Campagne',
            'plural' => 'Campagnes',
            'navigation_label' => 'Campagnes',
            'sections' => [
                'campaign_information' => 'Informations de la Campagne',
                'campaign_information_desc' => 'Détails et contenu de base de la campagne',
                'financial_details' => 'Détails Financiers',
                'financial_details_desc' => 'Montant cible et paramètres de correspondance d\'entreprise',
                'timeline_organization' => 'Chronologie & Organisation',
                'timeline_organization_desc' => 'Dates de campagne et détails organisationnels',
                'media_assets' => 'Médias & Ressources',
                'media_assets_desc' => 'Images et fichiers multimédias de la campagne',
            ],
            'fields' => [
                'title' => 'Titre',
                'description' => 'Description',
                'slug' => 'Slug',
                'category' => 'Catégorie',
                'visibility' => 'Visibilité',
                'goal_amount' => 'Montant Cible',
                'current_amount' => 'Montant Actuel',
                'has_corporate_matching' => 'Activer la Correspondance d\'Entreprise',
                'corporate_matching_rate' => 'Taux de Correspondance',
                'max_corporate_matching' => 'Correspondance d\'Entreprise Max',
                'start_date' => 'Date de Début',
                'end_date' => 'Date de Fin',
                'organization' => 'Organisation',
                'employee' => 'Gestionnaire de Campagne',
                'status' => 'Statut',
                'featured_image' => 'Image Vedette',
            ],
        ],
        'organization' => [
            'label' => 'Organisation',
            'plural' => 'Organisations',
            'navigation_label' => 'Organisations',
            'sections' => [
                'organization_information' => 'Informations de l\'Organisation',
                'organization_information_desc' => 'Détails de base et informations de contact',
                'legal_registration' => 'Juridique & Enregistrement',
                'legal_registration_desc' => 'Informations juridiques et détails d\'enregistrement',
                'contact_information' => 'Informations de Contact',
                'contact_information_desc' => 'Coordonnées et emplacement physique',
            ],
            'fields' => [
                'name' => 'Nom',
                'description' => 'Description',
                'mission' => 'Mission',
                'category' => 'Catégorie',
                'registration_number' => 'Numéro d\'Enregistrement',
                'tax_id' => 'Numéro de TVA',
                'is_verified' => 'Organisation Vérifiée',
                'verification_date' => 'Date de Vérification',
                'is_active' => 'Statut Actif',
                'website' => 'Site Web',
                'email' => 'E-mail',
                'phone' => 'Téléphone',
                'address' => 'Adresse',
                'city' => 'Ville',
                'postal_code' => 'Code Postal',
                'country' => 'Pays',
                'logo_url' => 'Logo',
            ],
        ],
        'donation' => [
            'label' => 'Don',
            'plural' => 'Dons',
            'navigation_label' => 'Dons',
            'sections' => [
                'donation_information' => 'Informations du Don',
                'donation_information_desc' => 'Détails de base du don et informations de transaction',
                'payment_details' => 'Détails de Paiement',
                'payment_details_desc' => 'Méthode de paiement et informations de passerelle',
                'additional_settings' => 'Paramètres Supplémentaires',
                'additional_settings_desc' => 'Paramètres de confidentialité et de don récurrent',
                'timestamps' => 'Horodatages',
                'timestamps_desc' => 'Dates importantes et chronologie de traitement',
            ],
            'fields' => [
                'campaign' => 'Campagne',
                'employee' => 'Donateur',
                'amount' => 'Montant',
                'currency' => 'Devise',
                'payment_method' => 'Méthode de Paiement',
                'payment_gateway' => 'Passerelle de Paiement',
                'transaction_id' => 'ID de Transaction',
                'status' => 'Statut',
                'anonymous' => 'Don Anonyme',
                'recurring' => 'Don Récurrent',
                'recurring_frequency' => 'Fréquence de Récurrence',
                'notes' => 'Notes',
                'donated_at' => 'Date du Don',
                'processed_at' => 'Date de Traitement',
                'completed_at' => 'Date d\'Achèvement',
            ],
        ],
        'page' => [
            'label' => 'Page',
            'plural' => 'Pages',
            'navigation_label' => 'Pages',
            'sections' => [
                'page_information' => 'Informations de la Page',
                'page_information_desc' => 'Détails de base et paramètres de la page',
                'page_content' => 'Contenu de la Page',
                'page_content_desc' => 'Contenu de la page en plusieurs langues',
                'seo_meta' => 'SEO & Méta',
                'seo_meta_desc' => 'Optimisation pour les moteurs de recherche et métadonnées',
                'seo_preview' => 'Aperçu SEO',
                'seo_preview_desc' => 'Comment votre page apparaît dans les résultats de recherche',
            ],
            'fields' => [
                'title' => 'Titre de la Page',
                'slug' => 'Slug',
                'status' => 'Statut',
                'template' => 'Modèle',
                'order' => 'Ordre',
                'content' => 'Contenu',
                'meta_description' => 'Méta Description',
                'meta_keywords' => 'Méta Mots-clés',
            ],
        ],
        'user' => [
            'label' => 'Utilisateur',
            'plural' => 'Utilisateurs',
            'navigation_label' => 'Utilisateurs',
        ],
        'role' => [
            'label' => 'Rôle',
            'plural' => 'Rôles',
            'navigation_label' => 'Rôles',
        ],
    ],

    // Common Actions
    'actions' => [
        'create' => 'Créer',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'view' => 'Voir',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'filter' => 'Filtrer',
        'search' => 'Rechercher',
        'export' => 'Exporter',
        'import' => 'Importer',
        'refresh' => 'Actualiser',
        'bulk_delete' => 'Supprimer la Sélection',
    ],

    // Common Messages
    'messages' => [
        'created' => 'Enregistrement créé avec succès',
        'updated' => 'Enregistrement mis à jour avec succès',
        'deleted' => 'Enregistrement supprimé avec succès',
        'saved' => 'Modifications enregistrées avec succès',
        'error' => 'Une erreur s\'est produite',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cet enregistrement?',
        'no_records' => 'Aucun enregistrement trouvé',
    ],

    // Status Labels
    'statuses' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'pending' => 'En Attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'paused' => 'En Pause',
    ],

    // Categories
    'categories' => [
        'education' => 'Éducation',
        'health' => 'Santé & Médical',
        'environment' => 'Environnement',
        'community' => 'Développement Communautaire',
        'disaster_relief' => 'Aide d\'Urgence',
        'poverty' => 'Lutte contre la Pauvreté',
        'animal_welfare' => 'Bien-être Animal',
        'human_rights' => 'Droits de l\'Homme',
        'arts_culture' => 'Arts & Culture',
        'sports' => 'Sports & Loisirs',
        'charity' => 'Charité',
        'non_profit' => 'À But Non Lucratif',
        'ngo' => 'ONG',
        'foundation' => 'Fondation',
        'other' => 'Autre',
    ],

    // Visibility Options
    'visibility' => [
        'public' => 'Public - Visible pour tous',
        'internal' => 'Interne - Employés de l\'entreprise uniquement',
        'private' => 'Privé - Utilisateurs invités uniquement',
    ],

    // Payment Methods
    'payment_methods' => [
        'credit_card' => 'Carte de Crédit',
        'bank_transfer' => 'Virement Bancaire',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'mollie' => 'Mollie',
    ],

    // Recurring Frequencies
    'recurring_frequencies' => [
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
        'quarterly' => 'Trimestriel',
        'yearly' => 'Annuel',
    ],

    // Helper Texts
    'helpers' => [
        'slug_auto_generated' => 'Sera généré automatiquement si laissé vide',
        'current_amount_readonly' => 'Mis à jour automatiquement à la réception des dons',
        'corporate_matching_help' => 'L\'entreprise égale les dons des employés',
        'matching_rate_help' => 'Pourcentage du don de l\'employé à égaler (100% = correspondance 1:1)',
        'max_matching_help' => 'Montant total maximal de correspondance d\'entreprise (laisser vide pour illimité)',
        'anonymous_help' => 'Masquer le nom du donateur dans les affichages publics',
        'recurring_help' => 'Configurer des dons récurrents automatiques',
        'featured_image_help' => 'Taille recommandée: 1200x675px (format 16:9)',
        'meta_description_help' => 'Brève description pour les moteurs de recherche (160 caractères max)',
        'keywords_help' => 'Mots-clés séparés par des virgules',
    ],

    // Payment Gateway Configuration
    'payment_gateway_configuration_guide' => 'Guide de Configuration des Passerelles de Paiement',
    'stripe_configuration' => 'Configuration Stripe',
    'mollie_configuration' => 'Configuration Mollie',
    'security_notes' => 'Notes de Sécurité',
    'api_key' => 'Clé API',
    'webhook_secret' => 'Secret Webhook',
    'webhook_url' => 'URL Webhook',
    'publishable_key' => 'Clé Publiable',
    'your_secret_api_key_stripe' => 'Votre clé API secrète depuis le tableau de bord Stripe',
    'endpoint_signing_secret' => 'Secret de signature d\'endpoint pour la validation webhook',
    'your_application_webhook_endpoint' => 'L\'endpoint webhook de votre application',
    'publishable_key_client_side' => 'Votre clé publiable pour l\'intégration côté client',
    'your_live_test_api_key_mollie' => 'Votre clé API de production ou de test depuis le tableau de bord Mollie',
    'secret_for_webhook_validation' => 'Secret pour la validation webhook',
    'all_sensitive_data_encrypted' => 'Toutes les données sensibles sont automatiquement chiffrées dans la base de données',
    'test_configuration_before_activating' => 'Testez votre configuration avant d\'activer la passerelle',
    'use_test_mode_during_development' => 'Utilisez le mode test pendant le développement et les tests',
    'keep_api_keys_secure' => 'Gardez vos clés API sécurisées et renouvelez-les régulièrement',
];
