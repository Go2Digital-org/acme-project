<?php

declare(strict_types=1);

return [
    // Messages d'erreur généraux
    'validation_error' => 'Oups ! Quelque chose s\'est mal passé.',
    'go_back_home' => 'Retourner à l\'accueil',

    // Messages d'erreur HTTP
    '403' => [
        'title' => '403 - Accès interdit',
        'message' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.',
    ],
    '404' => [
        'title' => '404 - Page introuvable',
        'message' => 'La page que vous recherchez est introuvable.',
    ],
    '419' => [
        'title' => '419 - Page expirée',
        'message' => 'Votre session a expiré. Veuillez actualiser la page et réessayer.',
    ],
    '429' => [
        'title' => '429 - Trop de requêtes',
        'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
    ],
    '500' => [
        'title' => '500 - Erreur serveur',
        'message' => 'Il y a eu un problème avec notre serveur. Veuillez réessayer plus tard.',
    ],
    '503' => [
        'title' => '503 - Service indisponible',
        'message' => 'Le service est temporairement indisponible. Veuillez réessayer plus tard.',
    ],

    // Messages d'erreur de formulaire
    'form_errors' => 'Veuillez corriger les erreurs suivantes :',
    'required_field' => 'Ce champ est requis',
    'invalid_format' => 'Format invalide',
    'file_upload_failed' => 'Échec du téléchargement du fichier',
    'permission_denied' => 'Autorisation refusée',

    // Erreurs système
    'database_error' => 'Erreur de connexion à la base de données',
    'network_error' => 'Erreur de connexion réseau',
    'timeout_error' => 'Délai d\'attente de la requête expiré',
    'unknown_error' => 'Une erreur inconnue s\'est produite',
];
