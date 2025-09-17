<?php

declare(strict_types=1);

return [
    // Composant de sélection de devise
    'currency_selector' => [
        'select_currency' => 'Sélectionner la devise',
        'current_currency' => 'Devise actuelle : :currency',
        'available_currencies' => 'Devises disponibles',
        'currency_changed' => 'Devise changée en :currency',
    ],

    // Composant de prévisualisation SEO
    'seo_preview' => [
        'title' => 'Aperçu SEO',
        'description_too_long' => 'La description fait :count caractères (:max maximum recommandé)',
        'title_preview' => 'Aperçu du titre',
        'url_preview' => 'Aperçu de l\'URL',
        'description_preview' => 'Aperçu de la description',
        'character_count' => ':current/:max caractères',
        'recommended_length' => 'Longueur recommandée',
        'title_length' => 'Longueur du titre : :length caractères',
        'description_length' => 'Longueur de la description : :length caractères',
    ],

    // Composant d'erreurs de validation
    'validation_errors' => [
        'title' => 'Erreurs de validation',
        'please_fix' => 'Veuillez corriger les erreurs suivantes :',
        'error_occurred' => 'Une erreur s\'est produite',
        'multiple_errors' => 'Plusieurs erreurs trouvées',
    ],

    // Messages génériques des composants
    'loading' => 'Chargement...',
    'no_data' => 'Aucune donnée disponible',
    'error_loading' => 'Erreur lors du chargement des données',
    'retry' => 'Réessayer',
    'refresh' => 'Actualiser',
    'close' => 'Fermer',
    'cancel' => 'Annuler',
    'save' => 'Enregistrer',
    'submit' => 'Soumettre',
    'reset' => 'Réinitialiser',
    'clear' => 'Effacer',
    'select_all' => 'Tout sélectionner',
    'deselect_all' => 'Tout désélectionner',
    'show_more' => 'Afficher plus',
    'show_less' => 'Afficher moins',
    'expand' => 'Développer',
    'collapse' => 'Réduire',
    'toggle' => 'Basculer',
    'preview' => 'Aperçu',
    'edit' => 'Modifier',
    'delete' => 'Supprimer',
    'duplicate' => 'Dupliquer',
    'copy' => 'Copier',
    'move' => 'Déplacer',
    'sort' => 'Trier',
    'filter' => 'Filtrer',
    'search' => 'Rechercher',

    // Indicateurs de statut
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'pending' => 'En attente',
    'approved' => 'Approuvé',
    'rejected' => 'Rejeté',
    'draft' => 'Brouillon',
    'published' => 'Publié',
    'archived' => 'Archivé',

    // Composants de formulaire
    'required_field' => 'Champ requis',
    'optional_field' => 'Champ optionnel',
    'select_option' => 'Sélectionner une option',
    'choose_file' => 'Choisir un fichier',
    'no_file_selected' => 'Aucun fichier sélectionné',
    'file_selected' => 'Fichier sélectionné',
    'upload_file' => 'Télécharger un fichier',
    'remove_file' => 'Supprimer le fichier',
    'drag_drop_files' => 'Glissez et déposez les fichiers ici',
    'browse_files' => 'Parcourir les fichiers',
    'max_file_size' => 'Taille de fichier maximale : :size',
    'allowed_formats' => 'Formats autorisés : :formats',

    // Pagination
    'showing_results' => 'Affichage de :first à :last sur :total résultats',
    'no_results' => 'Aucun résultat trouvé',
    'items_per_page' => 'Éléments par page',
    'page' => 'Page',
    'of_pages' => 'sur :total',
    'first_page' => 'Première page',
    'last_page' => 'Dernière page',
    'previous_page' => 'Page précédente',
    'next_page' => 'Page suivante',

    // Date et heure
    'select_date' => 'Sélectionner une date',
    'select_time' => 'Sélectionner une heure',
    'today' => 'Aujourd\'hui',
    'yesterday' => 'Hier',
    'tomorrow' => 'Demain',
    'this_week' => 'Cette semaine',
    'last_week' => 'La semaine dernière',
    'next_week' => 'La semaine prochaine',
    'this_month' => 'Ce mois-ci',
    'last_month' => 'Le mois dernier',
    'next_month' => 'Le mois prochain',
    'this_year' => 'Cette année',
    'last_year' => 'L\'année dernière',
    'next_year' => 'L\'année prochaine',

    // Boîtes de dialogue de confirmation
    'confirm_action' => 'Confirmer l\'action',
    'are_you_sure' => 'Êtes-vous sûr ?',
    'cannot_be_undone' => 'Cette action ne peut pas être annulée.',
    'confirm_delete' => 'Confirmer la suppression',
    'delete_warning' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
    'yes_delete' => 'Oui, supprimer',
    'no_cancel' => 'Non, annuler',

    // Composant bouton d'export
    'export' => [
        // Libellés des types d'export
        'campaigns' => 'Exporter les campagnes',
        'donations' => 'Exporter les dons',
        'reports' => 'Exporter les rapports',
        'users' => 'Exporter les utilisateurs',

        // Descriptions des types d'export
        'campaigns_description' => 'Exporter les données de campagne avec progression et statistiques de dons',
        'donations_description' => 'Exporter les enregistrements de dons avec informations des donateurs',
        'reports_description' => 'Exporter les rapports analytiques et métriques',
        'users_description' => 'Exporter les comptes utilisateurs et données de profil',

        // Messages de statut d'export
        'exporting' => 'Export en cours...',
        'export_progress' => ':progress%',

        // Options avancées
        'advanced_options' => 'Options avancées...',
        'advanced_export_options' => 'Options d\'export avancées',
        'export_format' => 'Format d\'export',
        'date_range' => 'Plage de dates',
        'include_archived' => 'Inclure les éléments archivés',
        'include_metadata' => 'Inclure les métadonnées',
        'start_export' => 'Démarrer l\'export',

        // Formats d'export
        'csv' => 'CSV',
        'xlsx' => 'Excel (XLSX)',
        'json' => 'JSON',

        // Options de plage de dates
        'all_time' => 'Toute la période',
        'this_week' => 'Cette semaine',
        'this_month' => 'Ce mois-ci',
        'this_quarter' => 'Ce trimestre',
        'this_year' => 'Cette année',

        // Messages d'export
        'export_failed' => 'Échec du démarrage de l\'export',
    ],
];
