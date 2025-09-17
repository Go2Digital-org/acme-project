<?php

declare(strict_types=1);

return [
    // General
    'campaigns' => 'Campagnes',
    'campaign' => 'Campagne',
    'all_campaigns' => 'Toutes les Campagnes',
    'my_campaigns' => 'Mes Campagnes',
    'featured_campaigns' => 'Campagnes Mises en Avant',
    'recent_campaigns' => 'Campagnes Récentes',
    'popular_campaigns' => 'Campagnes Populaires',

    // Campaign details
    'title' => 'Titre',
    'description' => 'Description',
    'goal_amount' => 'Montant Objectif',
    'current_amount' => 'Montant Actuel',
    'target' => 'Cible',
    'category' => 'Catégorie',
    'status' => 'Statut',
    'created_by' => 'Créé par',
    'created_at' => 'Créé le',
    'end_date' => 'Date de Fin',
    'start_date' => 'Date de Début',

    // Campaign statuses
    'status_draft' => 'Brouillon',
    'status_active' => 'Active',
    'status_completed' => 'Terminée',
    'status_cancelled' => 'Annulée',
    'status_paused' => 'En Pause',

    // Campaign actions
    'create_campaign' => 'Créer une Campagne',
    'edit_campaign' => 'Modifier la Campagne',
    'view_campaign' => 'Voir la Campagne',
    'delete_campaign' => 'Supprimer la Campagne',
    'publish_campaign' => 'Publier la Campagne',
    'pause_campaign' => 'Mettre en Pause',
    'resume_campaign' => 'Reprendre la Campagne',
    'cancel_campaign' => 'Annuler la Campagne',
    'duplicate_campaign' => 'Dupliquer la Campagne',
    'share_campaign' => 'Partager la Campagne',

    // Campaign creation/editing
    'campaign_details' => 'Détails de la Campagne',
    'basic_information' => 'Informations de Base',
    'campaign_title' => 'Titre de la Campagne',
    'campaign_title_placeholder' => 'Entrez un titre convaincant pour la campagne',
    'campaign_description' => 'Description de la Campagne',
    'campaign_description_placeholder' => 'Décrivez votre campagne et son impact...',
    'campaign_goal_amount' => 'Montant Objectif',
    'campaign_goal_placeholder' => 'Entrez le montant cible en euros',
    'campaign_category' => 'Catégorie',
    'campaign_end_date' => 'Date de Fin',
    'campaign_image' => 'Image de la Campagne',
    'upload_image' => 'Téléverser une Image',
    'change_image' => 'Changer l\'Image',

    // Categories
    'categories' => 'Catégories',
    'category_education' => 'Éducation',
    'category_health' => 'Santé et Médical',
    'category_environment' => 'Environnement',
    'category_animals' => 'Animaux et Faune',
    'category_humanitarian' => 'Aide Humanitaire',
    'category_community' => 'Développement Communautaire',
    'category_arts' => 'Arts et Culture',
    'category_sports' => 'Sports et Loisirs',
    'category_emergency' => 'Secours d\'Urgence',
    'category_other' => 'Autre',
    'of_goal' => 'de l\'objectif',
    'days_remaining' => 'jours restants',
    'day_remaining' => 'jour restant',
    'days_left' => ':count jours restants',
    'day_left' => ':count jour restant',
    'expired' => 'Expiré',
    'goal_reached' => 'Objectif Atteint !',
    'goal_exceeded' => 'Objectif Dépassé !',

    // Donations on campaign
    'donors' => 'Donateurs',
    'donor_count' => ':count donateur|:count donateurs',
    'recent_donations' => 'Dons Récents',
    'top_donors' => 'Meilleurs Donateurs',
    'anonymous_donor' => 'Donateur Anonyme',
    'first_donation' => 'Soyez le premier à faire un don !',
    'latest_donation' => 'Dernier don : :amount par :donor',

    // Campaign statistics
    'total_raised' => 'Total Collecté',
    'total_donors' => 'Total Donateurs',
    'average_donation' => 'Don Moyen',
    'largest_donation' => 'Plus Gros Don',
    'campaign_statistics' => 'Statistiques de la Campagne',

    // Search and filtering
    'search_campaigns' => 'Rechercher des campagnes...',
    'search_placeholder' => 'Rechercher par titre, description ou catégorie',
    'filter_by_category' => 'Filtrer par Catégorie',
    'filter_by_status' => 'Filtrer par Statut',
    'sort_by' => 'Trier par',
    'sort_newest' => 'Plus Récent d\'Abord',
    'sort_oldest' => 'Plus Ancien d\'Abord',
    'sort_most_funded' => 'Plus Financé',
    'sort_ending_soon' => 'Se Termine Bientôt',
    'sort_goal_amount' => 'Montant Objectif',

    // Success messages
    'campaign_created' => 'Campagne créée avec succès !',
    'campaign_updated' => 'Campagne mise à jour avec succès !',
    'campaign_deleted' => 'Campagne supprimée avec succès.',
    'campaign_published' => 'Campagne publiée avec succès !',
    'campaign_paused' => 'Campagne mise en pause avec succès.',
    'campaign_resumed' => 'Campagne reprise avec succès.',
    'campaign_cancelled' => 'Campagne annulée avec succès.',

    // Error messages
    'campaign_not_found' => 'Campagne introuvable.',
    'cannot_edit_campaign' => 'Vous ne pouvez pas modifier cette campagne.',
    'cannot_delete_campaign' => 'Impossible de supprimer cette campagne.',
    'goal_amount_required' => 'Le montant objectif est requis.',
    'invalid_goal_amount' => 'Veuillez entrer un montant objectif valide.',
    'end_date_required' => 'La date de fin est requise.',
    'end_date_future' => 'La date de fin doit être dans le futur.',
    'title_required' => 'Le titre de la campagne est requis.',
    'description_required' => 'La description de la campagne est requise.',

    // Empty states
    'no_campaigns' => 'Aucune campagne trouvée.',
    'no_campaigns_message' => 'Il n\'y a actuellement aucune campagne disponible.',
    'create_first_campaign' => 'Créez votre première campagne',
    'no_results' => 'Aucune campagne ne correspond à vos critères de recherche.',
    'try_different_search' => 'Essayez d\'ajuster votre recherche ou vos filtres.',

    // Sample campaign content
    'sample_education_title' => 'Initiative Éducative Mondiale',
    'sample_education_desc' => 'Fournir des ressources éducatives de qualité aux communautés défavorisées dans le monde entier.',
    'sample_healthcare_title' => 'Collecte d\'Équipements Médicaux',
    'sample_healthcare_desc' => 'Équipements médicaux essentiels pour les centres de santé ruraux.',
    'sample_environment_title' => 'Projet de Restauration Forestière',
    'sample_environment_desc' => 'Reboiser les terres dégradées pour lutter contre le changement climatique et restaurer la biodiversité.',

    // Additional French-specific terms
    'donate_now' => 'Faire un Don',
    'share' => 'Partager',
    'progress' => 'Progression',
    'raised' => 'collecté',
    'goal' => 'objectif',

    // Additional keys for welcome page
    'subtitle' => 'Découvrez des causes qui comptent et faites que votre contribution compte.',
    'funded' => 'Financé',
    'urgent' => 'Urgent',
    'areas_description' => 'Nous soutenons des causes dans plusieurs domaines pour créer un changement positif complet.',
    'education_desc' => 'Soutien aux programmes éducatifs et au développement d\'infrastructures dans le monde entier.',
    'healthcare_desc' => 'Fourniture de soutien médical et d\'infrastructures de santé aux communautés mal desservies.',
    'environment_desc' => 'Conservation environnementale et initiatives de durabilité pour notre planète.',
    'community_desc' => 'Construire des communautés plus fortes grâce aux programmes de développement social.',

    // Social sharing
    'share_message' => 'Aidez à faire connaître cette campagne extraordinaire!',
    'copy_link' => 'Copier le Lien',
    'link_copied' => 'Lien Copié!',
    'copy_failed' => 'Échec de la copie du lien. Veuillez essayer manuellement.',

    // Filament Resource Actions
    'approve' => 'Approuver',
    'reject' => 'Rejeter',
    'pause' => 'Mettre en Pause',
    'complete' => 'Terminer',
    'cancel' => 'Annuler',
    'new_campaign' => 'Nouvelle Campagne',
    'submit_for_approval' => 'Soumettre pour Approbation',
    'rejection_reason' => 'Motif de Rejet',

    // Filament Resource Sections
    'campaign_information' => 'Informations de la Campagne',
    'campaign_information_description' => 'Détails de base et contenu de la campagne',
    'financial_details' => 'Détails Financiers',
    'financial_details_description' => 'Montant objectif et paramètres de contrepartie d\'entreprise',
    'timeline_organization' => 'Calendrier et Organisation',
    'timeline_organization_description' => 'Dates de campagne et détails organisationnels',
    'media_assets' => 'Médias et Assets',
    'media_assets_description' => 'Images et fichiers médias de la campagne',

    // Page Titles
    'create_new_campaign' => 'Créer une Nouvelle Campagne',
    'edit_campaign_title' => 'Modifier la Campagne',
    'view_campaign_title' => 'Voir la Campagne',

    // Tab Labels
    'all' => 'Toutes',
    'needs_approval' => 'Nécessite Approbation',
    'active' => 'Actives',
    'draft' => 'Brouillon',
    'completed_tab' => 'Terminées',

    // Modal Descriptions
    'approve_modal_description' => 'Ceci approuvera la campagne et la rendra active.',
    'reject_modal_description' => 'Ceci rejettera la campagne et la renverra pour révision.',
    'submit_approval_modal_description' => 'Ceci soumettra toutes les campagnes brouillons sélectionnées pour approbation.',
    'bulk_approve_modal_description' => 'Ceci approuvera toutes les campagnes sélectionnées et les rendra actives.',
    'bulk_reject_modal_description' => 'Ceci rejettera toutes les campagnes sélectionnées et les renverra pour révision.',
    'pause_modal_description' => 'Ceci mettra en pause toutes les campagnes sélectionnées, arrêtant temporairement les nouveaux dons.',
    'complete_modal_description' => 'Ceci marquera toutes les campagnes sélectionnées comme terminées.',
    'cancel_modal_description' => 'Ceci annulera toutes les campagnes sélectionnées. Cette action ne peut pas être annulée.',

    // Form Labels and Helpers
    'enable_corporate_matching' => 'Activer la Contrepartie d\'Entreprise',
    'corporate_matching_help' => 'L\'entreprise égalera les dons des employés',
    'corporate_matching_rate_help' => 'Pourcentage du don de l\'employé à égaler (100% = égalisation 1:1)',
    'max_corporate_matching_help' => 'Montant maximum total de contrepartie d\'entreprise (laisser vide pour illimité)',
    'slug_help' => 'Sera généré automatiquement si laissé vide',
    'category_help' => 'Sélectionner la catégorie de campagne',
    'current_amount_help' => 'Mis à jour automatiquement lors de la réception des dons',
    'beneficiary_organization_help' => 'Organisation bénéficiaire',
    'campaign_creator_help' => 'Créateur/gestionnaire de campagne',
    'featured_image_help' => 'Taille recommandée : 1200x675px (ratio d\'aspect 16:9)',
    'goal_amount_help' => 'Minimum 100€, Maximum 1 000 000€',
    'rejection_reason_help' => 'Veuillez fournir une raison claire pour le rejet',

    // Visibility Options
    'visibility_public' => 'Public - Visible pour tous',
    'visibility_internal' => 'Interne - Employés de l\'entreprise uniquement',
    'visibility_private' => 'Privé - Utilisateurs invités uniquement',

    // Status Transitions
    'publish_campaign_action' => 'Publier la Campagne',
    'mark_as_completed' => 'Marquer comme Terminé',

    // Notification Actions
    'edit_campaign_action' => 'Modifier la Campagne',
    'approve_now' => 'Approuver Maintenant',
    'view_results' => 'Voir les Résultats',
    'review_campaign' => 'Réviser la Campagne',

    // Status Display Names
    'status_pending_approval' => 'En Attente d\'Approbation',
    'status_rejected' => 'Rejetée',
    'status_expired' => 'Expirée',

    // Export/Reports
    'created_by_export' => 'Créé Par',
    'submitted_by' => 'Soumis Par',
    'submitted' => 'Soumis',
    'approved_today' => 'Approuvé Aujourd\'hui',
    'rejected_today' => 'Rejeté Aujourd\'hui',
];
