<?php

declare(strict_types=1);

return [
    // General API messages
    'success' => 'Demande traitée avec succès',
    'error' => 'Une erreur s\'est produite lors du traitement de votre demande',
    'validation_failed' => 'Les données fournies sont invalides',
    'resource_not_found' => 'La ressource demandée n\'a pas été trouvée',
    'unauthorized' => 'L\'authentification est requise pour accéder à cette ressource',
    'forbidden' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette ressource',
    'rate_limit_exceeded' => 'Trop de demandes. Veuillez réessayer plus tard',
    'server_error' => 'Erreur interne du serveur',

    // Pagination messages
    'pagination' => [
        'showing_results' => 'Affichage de :from à :to sur :total résultats',
        'page_not_found' => 'La page demandée n\'existe pas',
        'invalid_page_size' => 'Taille de page invalide. Doit être entre :min et :max',
    ],

    // Campaign messages
    'campaigns' => [
        'created' => 'Campagne créée avec succès',
        'updated' => 'Campagne mise à jour avec succès',
        'deleted' => 'Campagne supprimée avec succès',
        'activated' => 'Campagne activée avec succès',
        'completed' => 'Campagne terminée avec succès',
        'not_found' => 'Campagne non trouvée',
        'goal_reached' => 'L\'objectif de la campagne a été atteint',
        'expired' => 'La campagne a expiré',
        'inactive' => 'La campagne n\'est pas active',
        'search_results' => ':count campagnes trouvées correspondant à votre recherche',
        'no_results' => 'Aucune campagne trouvée correspondant à vos critères',
    ],

    // Donation messages
    'donations' => [
        'created' => 'Don créé avec succès',
        'processed' => 'Don traité avec succès',
        'cancelled' => 'Don annulé avec succès',
        'refunded' => 'Don remboursé avec succès',
        'failed' => 'Échec du traitement du don',
        'not_found' => 'Don non trouvé',
        'receipt_generated' => 'Reçu de don généré avec succès',
        'minimum_amount' => 'Le montant minimum du don est :amount',
        'maximum_amount' => 'Le montant maximum du don est :amount',
        'campaign_inactive' => 'Impossible de faire un don à une campagne inactive',
        'goal_exceeded' => 'Le don dépasserait l\'objectif de la campagne',
    ],

    // Organization messages
    'organizations' => [
        'created' => 'Organisation créée avec succès',
        'updated' => 'Organisation mise à jour avec succès',
        'verified' => 'Organisation vérifiée avec succès',
        'activated' => 'Organisation activée avec succès',
        'deactivated' => 'Organisation désactivée avec succès',
        'not_found' => 'Organisation non trouvée',
        'search_results' => ':count organisations trouvées correspondant à votre recherche',
    ],

    // Employee messages
    'employees' => [
        'profile_updated' => 'Profil mis à jour avec succès',
        'not_found' => 'Employé non trouvé',
        'unauthorized_access' => 'Vous ne pouvez accéder qu\'à votre propre profil',
        'campaigns_retrieved' => 'Campagnes de l\'employé récupérées avec succès',
        'donations_retrieved' => 'Dons de l\'employé récupérés avec succès',
    ],

    // Authentication messages
    'auth' => [
        'login_successful' => 'Connexion réussie',
        'logout_successful' => 'Déconnexion réussie',
        'registration_successful' => 'Inscription réussie',
        'invalid_credentials' => 'Email ou mot de passe invalide',
        'account_disabled' => 'Votre compte a été désactivé',
        'token_expired' => 'Le jeton d\'authentification a expiré',
        'token_invalid' => 'Jeton d\'authentification invalide',
        'email_already_exists' => 'Un compte avec cet email existe déjà',
        'password_too_weak' => 'Le mot de passe doit contenir au moins 8 caractères avec majuscules, minuscules, chiffres et symboles',
    ],

    // Payment messages
    'payments' => [
        'processing' => 'Le paiement est en cours de traitement',
        'completed' => 'Paiement terminé avec succès',
        'failed' => 'Échec du paiement',
        'cancelled' => 'Paiement annulé par l\'utilisateur',
        'refunded' => 'Paiement remboursé avec succès',
        'webhook_processed' => 'Webhook de paiement traité avec succès',
        'invalid_signature' => 'Signature de webhook de paiement invalide',
        'gateway_error' => 'Erreur de passerelle de paiement',
    ],

    // Validation messages
    'validation' => [
        'required_field' => 'Le champ :field est requis',
        'invalid_email' => 'Veuillez fournir une adresse email valide',
        'invalid_date' => 'Veuillez fournir une date valide',
        'invalid_amount' => 'Veuillez fournir un montant monétaire valide',
        'string_too_long' => 'Le champ :field ne peut pas dépasser :max caractères',
        'string_too_short' => 'Le champ :field doit contenir au moins :min caractères',
        'invalid_uuid' => 'Veuillez fournir un identifiant valide',
        'invalid_phone' => 'Veuillez fournir un numéro de téléphone valide',
        'invalid_url' => 'Veuillez fournir une URL valide',
    ],

    // Filter and search messages
    'filters' => [
        'invalid_filter' => 'Paramètre de filtre invalide : :filter',
        'invalid_sort' => 'Paramètre de tri invalide : :sort',
        'invalid_date_range' => 'Plage de dates invalide spécifiée',
        'unsupported_operator' => 'Opérateur de filtre non supporté : :operator',
    ],

    // Locale messages
    'locale' => [
        'unsupported' => 'Langue non supportée : :locale',
        'changed' => 'Préférence linguistique mise à jour vers :locale',
        'default_used' => 'Utilisation de la langue par défaut : :locale',
    ],
];
