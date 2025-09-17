<?php

declare(strict_types=1);

return [
    'title' => 'Stijlgids',
    'page_title' => 'ACME Corp Design Systeem',
    'page_subtitle' => 'Een uitgebreide gids voor ons MVO-platform design systeem, typografie en componenten',

    'typography' => [
        'title' => 'Typografie',
        'heading_1' => [
            'label' => 'Koptekst 1 - 5xl',
            'example' => 'Samen het verschil maken',
        ],
        'heading_2' => [
            'label' => 'Koptekst 2 - 4xl',
            'example' => 'Maatschappelijk Verantwoord Ondernemen',
        ],
        'heading_3' => [
            'label' => 'Koptekst 3 - 3xl',
            'example' => 'Uitgelichte Campagnes',
        ],
        'heading_4' => [
            'label' => 'Koptekst 4 - 2xl',
            'example' => 'Impactgebieden',
        ],
        'heading_5' => [
            'label' => 'Koptekst 5 - xl',
            'example' => 'Onderwijs Initiatief',
        ],
        'body_text' => [
            'label' => 'Broodtekst - base',
            'example' => 'Sluit je aan bij onze missie om positieve verandering te creëren in gemeenschappen wereldwijd door strategische maatschappelijk verantwoord ondernemen initiatieven.',
        ],
        'small_text' => [
            'label' => 'Kleine Tekst',
            'example' => 'Ondersteuning van doelen die belangrijk zijn voor onze werknemers en gemeenschappen.',
        ],
        'extra_small_text' => [
            'label' => 'Extra Kleine Tekst',
            'example' => 'Campagne details en kleine lettertjes informatie.',
        ],
    ],

    'colors' => [
        'title' => 'Kleurenpalet',
        'primary' => [
            'title' => 'Primaire Kleuren',
            'primary_blue' => [
                'name' => 'Primair Blauw',
                'description' => 'Gebruikt voor CTA\'s en links',
            ],
            'secondary' => [
                'name' => 'Secundair',
                'description' => 'Accent kleur',
            ],
        ],
        'status' => [
            'title' => 'Status Kleuren',
            'success' => [
                'name' => 'Succes',
                'description' => 'Campagne voltooid',
            ],
            'warning' => [
                'name' => 'Waarschuwing',
                'description' => 'Campagne eindigt binnenkort',
            ],
            'urgent' => [
                'name' => 'Urgent',
                'description' => 'Kritieke behoeften',
            ],
        ],
        'background' => [
            'title' => 'Achtergrond Kleuren',
            'page_background' => [
                'name' => 'Pagina Achtergrond',
                'description' => 'Hoofdapp achtergrond',
            ],
            'card_background' => [
                'name' => 'Kaart Achtergrond',
                'description' => 'Inhoud kaarten',
            ],
            'section_background' => [
                'name' => 'Sectie Achtergrond',
                'description' => 'Alternatieve secties',
            ],
        ],
    ],

    'buttons' => [
        'title' => 'Knop Componenten',
        'primary' => [
            'title' => 'Primaire Knoppen',
            'small_primary' => 'Kleine Primaire',
            'medium_primary' => 'Middelgrote Primaire',
            'large_with_icon' => 'Grote met Icoon',
        ],
        'secondary' => [
            'title' => 'Secundaire Knoppen',
            'secondary' => 'Secundair',
            'outline' => 'Omlijning',
            'ghost' => 'Spook',
        ],
        'status' => [
            'title' => 'Status & Actie Knoppen',
            'donate_now' => 'Doneer Nu',
            'urgent' => 'Urgent',
            'cancel' => 'Annuleren',
        ],
    ],

    'campaigns' => [
        'title' => 'Campagne Kaarten',
        'featured_card' => [
            'title' => 'Uitgelichte Campagne Kaart',
            'category' => 'Onderwijs',
            'funding_status' => '72% Gefinancierd',
            'name' => 'Wereldwijd Onderwijs Initiatief',
            'description' => 'Kwaliteitsonderwijsresources bieden aan onderbedeelde gemeenschappen wereldwijd.',
            'raised' => '€72.000 opgehaald',
            'goal' => '€100.000 doel',
        ],
        'impact_stats' => [
            'title' => 'Impact Statistiek Kaarten',
            'total_raised' => [
                'label' => 'Totaal Opgehaald',
                'value' => '€2,4M',
            ],
            'active_campaigns' => [
                'label' => 'Actieve Campagnes',
                'value' => '147',
            ],
            'employees_participating' => [
                'label' => 'Deelnemende Werknemers',
                'value' => '12.847',
            ],
        ],
    ],

    'forms' => [
        'title' => 'Formulier Componenten',
        'campaign_name' => [
            'label' => 'Campagne Naam',
            'placeholder' => 'Voer campagne naam in',
        ],
        'campaign_category' => [
            'label' => 'Campagne Categorie',
            'placeholder' => 'Kies categorie',
            'options' => [
                'education' => 'Onderwijs',
                'healthcare' => 'Gezondheidszorg',
                'environment' => 'Milieu',
                'community' => 'Gemeenschap',
            ],
        ],
        'campaign_description' => [
            'label' => 'Campagne Beschrijving',
            'placeholder' => 'Beschrijf uw campagne...',
        ],
        'terms_agreement' => 'Ik ga akkoord met de algemene voorwaarden',
    ],

    'icons' => [
        'title' => 'MVO Platform Iconen',
        'donate' => 'Doneren',
        'education' => 'Onderwijs',
        'healthcare' => 'Gezondheidszorg',
        'environment' => 'Milieu',
        'community' => 'Gemeenschap',
        'support' => 'Ondersteuning',
        'impact' => 'Impact',
        'global' => 'Wereldwijd',
    ],

    'principles' => [
        'title' => 'Design Principes',
        'clean_professional' => [
            'title' => 'Schoon & Professioneel',
            'items' => [
                'GEEN gradiënten - gebruik alleen solide achtergronden',
                'Schone typografie hiërarchie',
                'Consistente ruimte en uitlijning',
                'Professioneel kleurenpalet',
            ],
        ],
        'accessible_inclusive' => [
            'title' => 'Toegankelijk & Inclusief',
            'items' => [
                'WCAG 2.1 AA compliance',
                'Hoge contrast verhoudingen',
                'Duidelijke focus indicatoren',
                'Schermlezer geoptimaliseerd',
            ],
        ],
        'corporate_standards' => [
            'title' => 'Bedrijfsstandaarden',
            'items' => [
                'Consistent met ACME branding',
                'Professionele uitstraling',
                'Vertrouwen opbouwende design elementen',
                'Duidelijke informatie hiërarchie',
            ],
        ],
        'user_centered' => [
            'title' => 'Gebruiker-Gericht',
            'items' => [
                'Duidelijke oproepen tot actie',
                'Intuïtieve navigatie',
                'Mobile-first responsief ontwerp',
                'Snelle laadprestaties',
            ],
        ],
    ],
];
