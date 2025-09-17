<?php

declare(strict_types=1);

return [
    // Algemene foutmeldingen
    'validation_error' => 'Oeps! Er is iets misgegaan.',
    'go_back_home' => 'Terug naar Home',

    // HTTP foutmeldingen
    '403' => [
        'title' => '403 - Verboden',
        'message' => 'U heeft geen toestemming om deze pagina te bekijken.',
    ],
    '404' => [
        'title' => '404 - Pagina niet gevonden',
        'message' => 'De pagina die u zoekt kon niet worden gevonden.',
    ],
    '419' => [
        'title' => '419 - Pagina verlopen',
        'message' => 'Uw sessie is verlopen. Vernieuw de pagina en probeer opnieuw.',
    ],
    '429' => [
        'title' => '429 - Te veel verzoeken',
        'message' => 'Te veel verzoeken. Probeer het later opnieuw.',
    ],
    '500' => [
        'title' => '500 - Serverfout',
        'message' => 'Er was een probleem met onze server. Probeer het later opnieuw.',
    ],
    '503' => [
        'title' => '503 - Service niet beschikbaar',
        'message' => 'De service is tijdelijk niet beschikbaar. Probeer het later opnieuw.',
    ],

    // Formulier foutmeldingen
    'form_errors' => 'Corrigeer de volgende fouten:',
    'required_field' => 'Dit veld is verplicht',
    'invalid_format' => 'Ongeldig formaat',
    'file_upload_failed' => 'Bestand uploaden mislukt',
    'permission_denied' => 'Toestemming geweigerd',

    // Systeemfouten
    'database_error' => 'Database verbindingsfout',
    'network_error' => 'Netwerkverbindingsfout',
    'timeout_error' => 'Verzoek time-out',
    'unknown_error' => 'Er is een onbekende fout opgetreden',
];
