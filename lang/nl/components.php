<?php

declare(strict_types=1);

return [
    // Valuta selector component
    'currency_selector' => [
        'select_currency' => 'Selecteer valuta',
        'current_currency' => 'Huidige valuta: :currency',
        'available_currencies' => 'Beschikbare valuta\'s',
        'currency_changed' => 'Valuta gewijzigd naar :currency',
    ],

    // SEO Preview component
    'seo_preview' => [
        'title' => 'SEO Voorbeeld',
        'description_too_long' => 'Beschrijving is :count tekens (aanbevolen: :max max)',
        'title_preview' => 'Titel voorbeeld',
        'url_preview' => 'URL voorbeeld',
        'description_preview' => 'Beschrijving voorbeeld',
        'character_count' => ':current/:max tekens',
        'recommended_length' => 'Aanbevolen lengte',
        'title_length' => 'Titel lengte: :length tekens',
        'description_length' => 'Beschrijving lengte: :length tekens',
    ],

    // Validatie fouten component
    'validation_errors' => [
        'title' => 'Validatiefouten',
        'please_fix' => 'Corrigeer de volgende fouten:',
        'error_occurred' => 'Er is een fout opgetreden',
        'multiple_errors' => 'Meerdere fouten gevonden',
    ],

    // Generieke component berichten
    'loading' => 'Laden...',
    'no_data' => 'Geen gegevens beschikbaar',
    'error_loading' => 'Fout bij laden van gegevens',
    'retry' => 'Opnieuw proberen',
    'refresh' => 'Vernieuwen',
    'close' => 'Sluiten',
    'cancel' => 'Annuleren',
    'save' => 'Opslaan',
    'submit' => 'Versturen',
    'reset' => 'Resetten',
    'clear' => 'Wissen',
    'select_all' => 'Alles selecteren',
    'deselect_all' => 'Alles deselecteren',
    'show_more' => 'Meer tonen',
    'show_less' => 'Minder tonen',
    'expand' => 'Uitklappen',
    'collapse' => 'Inklappen',
    'toggle' => 'Schakelen',
    'preview' => 'Voorbeeld',
    'edit' => 'Bewerken',
    'delete' => 'Verwijderen',
    'duplicate' => 'Dupliceren',
    'copy' => 'Kopiëren',
    'move' => 'Verplaatsen',
    'sort' => 'Sorteren',
    'filter' => 'Filteren',
    'search' => 'Zoeken',

    // Status indicatoren
    'active' => 'Actief',
    'inactive' => 'Inactief',
    'pending' => 'In behandeling',
    'approved' => 'Goedgekeurd',
    'rejected' => 'Afgewezen',
    'draft' => 'Concept',
    'published' => 'Gepubliceerd',
    'archived' => 'Gearchiveerd',

    // Formulier componenten
    'required_field' => 'Verplicht veld',
    'optional_field' => 'Optioneel veld',
    'select_option' => 'Selecteer een optie',
    'choose_file' => 'Kies bestand',
    'no_file_selected' => 'Geen bestand geselecteerd',
    'file_selected' => 'Bestand geselecteerd',
    'upload_file' => 'Bestand uploaden',
    'remove_file' => 'Bestand verwijderen',
    'drag_drop_files' => 'Sleep bestanden hier naartoe',
    'browse_files' => 'Bestanden doorzoeken',
    'max_file_size' => 'Maximale bestandsgrootte: :size',
    'allowed_formats' => 'Toegestane formaten: :formats',

    // Paginering
    'showing_results' => ':first tot :last van :total resultaten weergeven',
    'no_results' => 'Geen resultaten gevonden',
    'items_per_page' => 'Items per pagina',
    'page' => 'Pagina',
    'of_pages' => 'van :total',
    'first_page' => 'Eerste pagina',
    'last_page' => 'Laatste pagina',
    'previous_page' => 'Vorige pagina',
    'next_page' => 'Volgende pagina',

    // Datum en tijd
    'select_date' => 'Selecteer datum',
    'select_time' => 'Selecteer tijd',
    'today' => 'Vandaag',
    'yesterday' => 'Gisteren',
    'tomorrow' => 'Morgen',
    'this_week' => 'Deze week',
    'last_week' => 'Vorige week',
    'next_week' => 'Volgende week',
    'this_month' => 'Deze maand',
    'last_month' => 'Vorige maand',
    'next_month' => 'Volgende maand',
    'this_year' => 'Dit jaar',
    'last_year' => 'Vorig jaar',
    'next_year' => 'Volgend jaar',

    // Bevestigingsdialogen
    'confirm_action' => 'Actie bevestigen',
    'are_you_sure' => 'Weet je het zeker?',
    'cannot_be_undone' => 'Deze actie kan niet ongedaan worden gemaakt.',
    'confirm_delete' => 'Verwijdering bevestigen',
    'delete_warning' => 'Weet je zeker dat je dit item wilt verwijderen?',
    'yes_delete' => 'Ja, verwijderen',
    'no_cancel' => 'Nee, annuleren',

    // Export knop component
    'export' => [
        // Export type labels
        'campaigns' => 'Campagnes exporteren',
        'donations' => 'Donaties exporteren',
        'reports' => 'Rapporten exporteren',
        'users' => 'Gebruikers exporteren',

        // Export type beschrijvingen
        'campaigns_description' => 'Exporteer campagnegegevens met voortgang en donatiestatistieken',
        'donations_description' => 'Exporteer donatiegegevens met donatorinformatie',
        'reports_description' => 'Exporteer analytische rapporten en statistieken',
        'users_description' => 'Exporteer gebruikersaccounts en profielgegevens',

        // Export status berichten
        'exporting' => 'Bezig met exporteren...',
        'export_progress' => ':progress%',

        // Geavanceerde opties
        'advanced_options' => 'Geavanceerde opties...',
        'advanced_export_options' => 'Geavanceerde exportopties',
        'export_format' => 'Exportformaat',
        'date_range' => 'Datumbereik',
        'include_archived' => 'Gearchiveerde items meenemen',
        'include_metadata' => 'Metadata meenemen',
        'start_export' => 'Export starten',

        // Export formaten
        'csv' => 'CSV',
        'xlsx' => 'Excel (XLSX)',
        'json' => 'JSON',

        // Datumbereik opties
        'all_time' => 'Alle tijd',
        'this_week' => 'Deze week',
        'this_month' => 'Deze maand',
        'this_quarter' => 'Dit kwartaal',
        'this_year' => 'Dit jaar',

        // Export berichten
        'export_failed' => 'Kon export niet starten',
    ],
];
