<?php

declare(strict_types=1);

return [
    // General API messages
    'success' => 'Verzoek succesvol voltooid',
    'error' => 'Er is een fout opgetreden bij het verwerken van uw verzoek',
    'validation_failed' => 'De opgegeven gegevens zijn ongeldig',
    'resource_not_found' => 'De gevraagde bron is niet gevonden',
    'unauthorized' => 'Authenticatie is vereist om toegang te krijgen tot deze bron',
    'forbidden' => 'U heeft geen toestemming om toegang te krijgen tot deze bron',
    'rate_limit_exceeded' => 'Te veel verzoeken. Probeer het later opnieuw',
    'server_error' => 'Interne serverfout opgetreden',

    // Pagination messages
    'pagination' => [
        'showing_results' => 'Toont :from tot :to van :total resultaten',
        'page_not_found' => 'De gevraagde pagina bestaat niet',
        'invalid_page_size' => 'Ongeldige paginagrootte. Moet tussen :min en :max zijn',
    ],

    // Campaign messages
    'campaigns' => [
        'created' => 'Campagne succesvol aangemaakt',
        'updated' => 'Campagne succesvol bijgewerkt',
        'deleted' => 'Campagne succesvol verwijderd',
        'activated' => 'Campagne succesvol geactiveerd',
        'completed' => 'Campagne succesvol voltooid',
        'not_found' => 'Campagne niet gevonden',
        'goal_reached' => 'Campagnedoel is bereikt',
        'expired' => 'Campagne is verlopen',
        'inactive' => 'Campagne is niet actief',
        'search_results' => ':count campagnes gevonden die overeenkomen met uw zoekopdracht',
        'no_results' => 'Geen campagnes gevonden die voldoen aan uw criteria',
    ],

    // Donation messages
    'donations' => [
        'created' => 'Donatie succesvol aangemaakt',
        'processed' => 'Donatie succesvol verwerkt',
        'cancelled' => 'Donatie succesvol geannuleerd',
        'refunded' => 'Donatie succesvol terugbetaald',
        'failed' => 'Donatieverwerking mislukt',
        'not_found' => 'Donatie niet gevonden',
        'receipt_generated' => 'Donatiebewijs succesvol gegenereerd',
        'minimum_amount' => 'Minimum donatiebedrag is :amount',
        'maximum_amount' => 'Maximum donatiebedrag is :amount',
        'campaign_inactive' => 'Kan niet doneren aan inactieve campagne',
        'goal_exceeded' => 'Donatie zou campagnedoel overschrijden',
    ],

    // Organization messages
    'organizations' => [
        'created' => 'Organisatie succesvol aangemaakt',
        'updated' => 'Organisatie succesvol bijgewerkt',
        'verified' => 'Organisatie succesvol geverifieerd',
        'activated' => 'Organisatie succesvol geactiveerd',
        'deactivated' => 'Organisatie succesvol gedeactiveerd',
        'not_found' => 'Organisatie niet gevonden',
        'search_results' => ':count organisaties gevonden die overeenkomen met uw zoekopdracht',
    ],

    // Employee messages
    'employees' => [
        'profile_updated' => 'Profiel succesvol bijgewerkt',
        'not_found' => 'Medewerker niet gevonden',
        'unauthorized_access' => 'U kunt alleen uw eigen profiel bekijken',
        'campaigns_retrieved' => 'Medewerkercampagnes succesvol opgehaald',
        'donations_retrieved' => 'Medewerkersdonaties succesvol opgehaald',
    ],

    // Authentication messages
    'auth' => [
        'login_successful' => 'Aanmelding succesvol',
        'logout_successful' => 'Afmelding succesvol',
        'registration_successful' => 'Registratie succesvol',
        'invalid_credentials' => 'Ongeldig e-mailadres of wachtwoord',
        'account_disabled' => 'Uw account is uitgeschakeld',
        'token_expired' => 'Authenticatietoken is verlopen',
        'token_invalid' => 'Ongeldig authenticatietoken',
        'email_already_exists' => 'Er bestaat al een account met dit e-mailadres',
        'password_too_weak' => 'Wachtwoord moet ten minste 8 tekens bevatten met hoofdletters, kleine letters, cijfers en symbolen',
    ],

    // Payment messages
    'payments' => [
        'processing' => 'Betaling wordt verwerkt',
        'completed' => 'Betaling succesvol voltooid',
        'failed' => 'Betaling mislukt',
        'cancelled' => 'Betaling geannuleerd door gebruiker',
        'refunded' => 'Betaling succesvol terugbetaald',
        'webhook_processed' => 'Betalingswebhook succesvol verwerkt',
        'invalid_signature' => 'Ongeldige betalingswebhook handtekening',
        'gateway_error' => 'Betalingsgateway fout opgetreden',
    ],

    // Validation messages
    'validation' => [
        'required_field' => 'Het :field veld is verplicht',
        'invalid_email' => 'Geef een geldig e-mailadres op',
        'invalid_date' => 'Geef een geldige datum op',
        'invalid_amount' => 'Geef een geldig geldbedrag op',
        'string_too_long' => 'Het :field veld mag niet meer dan :max tekens bevatten',
        'string_too_short' => 'Het :field veld moet ten minste :min tekens bevatten',
        'invalid_uuid' => 'Geef een geldige identificator op',
        'invalid_phone' => 'Geef een geldig telefoonnummer op',
        'invalid_url' => 'Geef een geldige URL op',
    ],

    // Filter and search messages
    'filters' => [
        'invalid_filter' => 'Ongeldige filterparameter: :filter',
        'invalid_sort' => 'Ongeldige sorteerparameter: :sort',
        'invalid_date_range' => 'Ongeldig datumbereik opgegeven',
        'unsupported_operator' => 'Niet-ondersteunde filteroperator: :operator',
    ],

    // Locale messages
    'locale' => [
        'unsupported' => 'Niet-ondersteunde taal: :locale',
        'changed' => 'Taalvoorkeur bijgewerkt naar :locale',
        'default_used' => 'Standaardtaal wordt gebruikt: :locale',
    ],
];
