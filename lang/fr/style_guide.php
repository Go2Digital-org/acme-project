<?php

declare(strict_types=1);

return [
    'title' => 'Guide de Style',
    'page_title' => 'Système de Design ACME Corp',
    'page_subtitle' => 'Un guide complet de notre système de design de plateforme RSE, typographie et composants',

    'typography' => [
        'title' => 'Typographie',
        'heading_1' => [
            'label' => 'Titre 1 - 5xl',
            'example' => 'Faire une différence ensemble',
        ],
        'heading_2' => [
            'label' => 'Titre 2 - 4xl',
            'example' => 'Responsabilité Sociale des Entreprises',
        ],
        'heading_3' => [
            'label' => 'Titre 3 - 3xl',
            'example' => 'Campagnes en Vedette',
        ],
        'heading_4' => [
            'label' => 'Titre 4 - 2xl',
            'example' => 'Domaines d\'Impact',
        ],
        'heading_5' => [
            'label' => 'Titre 5 - xl',
            'example' => 'Initiative Éducative',
        ],
        'body_text' => [
            'label' => 'Texte de Corps - base',
            'example' => 'Rejoignez notre mission pour créer un changement positif dans les communautés du monde entier grâce à des initiatives stratégiques de responsabilité sociale des entreprises.',
        ],
        'small_text' => [
            'label' => 'Petit Texte',
            'example' => 'Soutenir les causes qui comptent pour nos employés et nos communautés.',
        ],
        'extra_small_text' => [
            'label' => 'Très Petit Texte',
            'example' => 'Détails de campagne et informations en petits caractères.',
        ],
    ],

    'colors' => [
        'title' => 'Palette de Couleurs',
        'primary' => [
            'title' => 'Couleurs Primaires',
            'primary_blue' => [
                'name' => 'Bleu Primaire',
                'description' => 'Utilisé pour les CTA et liens',
            ],
            'secondary' => [
                'name' => 'Secondaire',
                'description' => 'Couleur d\'accent',
            ],
        ],
        'status' => [
            'title' => 'Couleurs de Statut',
            'success' => [
                'name' => 'Succès',
                'description' => 'Campagne terminée',
            ],
            'warning' => [
                'name' => 'Avertissement',
                'description' => 'Campagne se terminant bientôt',
            ],
            'urgent' => [
                'name' => 'Urgent',
                'description' => 'Besoins critiques',
            ],
        ],
        'background' => [
            'title' => 'Couleurs d\'Arrière-plan',
            'page_background' => [
                'name' => 'Arrière-plan de Page',
                'description' => 'Arrière-plan principal de l\'app',
            ],
            'card_background' => [
                'name' => 'Arrière-plan de Carte',
                'description' => 'Cartes de contenu',
            ],
            'section_background' => [
                'name' => 'Arrière-plan de Section',
                'description' => 'Sections alternées',
            ],
        ],
    ],

    'buttons' => [
        'title' => 'Composants de Bouton',
        'primary' => [
            'title' => 'Boutons Primaires',
            'small_primary' => 'Petit Primaire',
            'medium_primary' => 'Moyen Primaire',
            'large_with_icon' => 'Grand avec Icône',
        ],
        'secondary' => [
            'title' => 'Boutons Secondaires',
            'secondary' => 'Secondaire',
            'outline' => 'Contour',
            'ghost' => 'Fantôme',
        ],
        'status' => [
            'title' => 'Boutons de Statut et d\'Action',
            'donate_now' => 'Faire un Don',
            'urgent' => 'Urgent',
            'cancel' => 'Annuler',
        ],
    ],

    'campaigns' => [
        'title' => 'Cartes de Campagne',
        'featured_card' => [
            'title' => 'Carte de Campagne en Vedette',
            'category' => 'Éducation',
            'funding_status' => '72% Financé',
            'name' => 'Initiative Éducative Mondiale',
            'description' => 'Fournir des ressources éducatives de qualité aux communautés mal desservies du monde entier.',
            'raised' => '72 000 $ collectés',
            'goal' => '100 000 $ objectif',
        ],
        'impact_stats' => [
            'title' => 'Cartes de Statistiques d\'Impact',
            'total_raised' => [
                'label' => 'Total Collecté',
                'value' => '2,4 M$',
            ],
            'active_campaigns' => [
                'label' => 'Campagnes Actives',
                'value' => '147',
            ],
            'employees_participating' => [
                'label' => 'Employés Participants',
                'value' => '12 847',
            ],
        ],
    ],

    'forms' => [
        'title' => 'Composants de Formulaire',
        'campaign_name' => [
            'label' => 'Nom de la Campagne',
            'placeholder' => 'Entrez le nom de la campagne',
        ],
        'campaign_category' => [
            'label' => 'Catégorie de Campagne',
            'placeholder' => 'Choisir une catégorie',
            'options' => [
                'education' => 'Éducation',
                'healthcare' => 'Santé',
                'environment' => 'Environnement',
                'community' => 'Communauté',
            ],
        ],
        'campaign_description' => [
            'label' => 'Description de la Campagne',
            'placeholder' => 'Décrivez votre campagne...',
        ],
        'terms_agreement' => 'J\'accepte les termes et conditions',
    ],

    'icons' => [
        'title' => 'Icônes de la Plateforme RSE',
        'donate' => 'Faire un Don',
        'education' => 'Éducation',
        'healthcare' => 'Santé',
        'environment' => 'Environnement',
        'community' => 'Communauté',
        'support' => 'Support',
        'impact' => 'Impact',
        'global' => 'Mondial',
    ],

    'principles' => [
        'title' => 'Principes de Design',
        'clean_professional' => [
            'title' => 'Propre et Professionnel',
            'items' => [
                'AUCUN dégradé - utilisez uniquement des arrière-plans solides',
                'Hiérarchie typographique propre',
                'Espacement et alignement cohérents',
                'Palette de couleurs professionnelle',
            ],
        ],
        'accessible_inclusive' => [
            'title' => 'Accessible et Inclusif',
            'items' => [
                'Conformité WCAG 2.1 AA',
                'Rapports de contraste élevés',
                'Indicateurs de focus clairs',
                'Optimisé pour lecteur d\'écran',
            ],
        ],
        'corporate_standards' => [
            'title' => 'Normes d\'Entreprise',
            'items' => [
                'Cohérent avec la marque ACME',
                'Apparence professionnelle',
                'Éléments de design qui inspirent confiance',
                'Hiérarchie d\'information claire',
            ],
        ],
        'user_centered' => [
            'title' => 'Centré sur l\'Utilisateur',
            'items' => [
                'Appels à l\'action clairs',
                'Navigation intuitive',
                'Design réactif mobile-first',
                'Performance de chargement rapide',
            ],
        ],
    ],
];
