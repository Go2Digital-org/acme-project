<?php

declare(strict_types=1);

return [
    // General notification types
    'notifications' => 'Notifications',
    'new_notification' => 'Nouvelle Notification',
    'mark_as_read' => 'Marquer comme Lu',
    'mark_all_read' => 'Tout Marquer comme Lu',
    'mark_all_as_read' => 'Tout Marquer comme Lu',
    'view_all_notifications' => 'Voir Toutes les Notifications',
    'no_notifications' => 'Aucune notification',
    'unread_notifications' => 'Notifications Non Lues',
    'unread' => 'Non Lu',

    // Campaign notifications
    'new_campaign_available' => 'Nouvelle campagne disponible',
    'campaign_goal_reached' => 'Objectif de campagne atteint !',
    'campaign_ending_soon' => 'Campagne se terminant bientôt',
    'campaign_published' => 'Votre campagne a été publiée',
    'campaign_approved' => 'Votre campagne a été approuvée',
    'campaign_rejected' => 'Votre campagne nécessite des modifications',
    'new_donation_received' => 'Nouveau don reçu',
    'sample_campaign_description' => 'Aider à fournir de l\'eau potable aux communautés dans le besoin',

    // Donation notifications
    'donation_confirmed' => 'Don confirmé',
    'donation_processed' => 'Votre don de :amount à :campaign a été traité',
    'donation_receipt' => 'Reçu de don disponible',
    'recurring_donation_processed' => 'Don récurrent traité',
    'payment_failed' => 'Paiement échoué - veuillez mettre à jour votre méthode de paiement',

    // System notifications
    'system_maintenance' => 'Maintenance système programmée',
    'account_updated' => 'Informations du compte mises à jour',
    'password_changed' => 'Mot de passe changé avec succès',
    'login_alert' => 'Nouvelle connexion détectée',
    'security_alert' => 'Alerte de sécurité - activité inhabituelle détectée',

    // Admin notifications
    'new_user_registered' => 'Nouvel utilisateur inscrit',
    'new_campaign_submitted' => 'Nouvelle campagne en attente d\'approbation',
    'user_report_submitted' => 'Rapport d\'utilisateur soumis',
    'system_alert' => 'Alerte système nécessite attention',

    // Time stamps
    'just_now' => 'À l\'instant',
    'minutes_ago' => 'il y a :count minute|il y a :count minutes',
    'hours_ago' => 'il y a :count heure|il y a :count heures',
    'days_ago' => 'il y a :count jour|il y a :count jours',
    'weeks_ago' => 'il y a :count semaine|il y a :count semaines',

    // Email notifications
    'campaign_email_footer' => 'Merci de soutenir les initiatives de responsabilité sociale d\'entreprise.',
    'goal_reached_message' => 'La campagne a atteint avec succès son objectif de :amount !',
    'thank_you_support' => 'Merci pour votre soutien continu.',

    // Empty state
    'empty_state_message' => 'Vous n\'avez pas encore de notifications. Revenez plus tard ou parcourez nos campagnes actives.',

    // Notification preferences
    'notification_preferences' => 'Préférences de Notification',
    'email_notifications' => 'Notifications Email',
    'push_notifications' => 'Notifications Push',
    'sms_notifications' => 'Notifications SMS',
    'notification_frequency' => 'Fréquence des Notifications',
    'immediate' => 'Immédiat',
    'daily_digest' => 'Résumé Quotidien',
    'weekly_digest' => 'Résumé Hebdomadaire',
    'never' => 'Jamais',
];
