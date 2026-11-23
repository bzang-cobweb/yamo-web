<?php

return [
    'title' => 'Anwendung',
    'navigation_group' => 'Einstellungen',
    'sections' => [
        'home' => 'App-Startseite',
        'general' => 'Vorstellung',
        'payment' => 'Zahlung',
        'users' => 'Benutzer',
    ],
    'description' => 'Beschreibung',
    'payment_required' => 'Zahlung für Eigentümer anwenden',
    'partner_payment_required' => 'Zahlung für Partner anwenden',
    'minimum_age' => 'Mindestalter',
    'years' => 'Jahre',
    'home_offers_per_city' => 'Angebote pro Stadt',
    'home_partners' => 'Angezeigte Partner',
    'help' => [
        'partner_payment_required' => 'Wenn aktiviert, ist ein Partner nach seinem Datum « Läuft ab am » in der App nicht mehr sichtbar; ohne Datum ist er nicht sichtbar. Deaktiviert wird das Datum ignoriert.',
        'home_count' => 'Bei jedem Aufruf der Startseite zufällig ausgewählt. Leer: 10.',
        'description' => 'Vorstellung der Anwendung auf der Startseite, eine pro Sprache.',
        'payment_required' => 'Wenn aktiviert, ist ein Angebot nach seinem Datum « Bezahlt bis » für andere nicht mehr sichtbar; ohne Datum ist es nicht sichtbar. Sein Verfasser sieht es immer.',
        'minimum_age' => 'Wenn angegeben, wird das Geburtsdatum bei der Registrierung und in den persönlichen Daten Pflicht und muss mindestens dieses Alter ergeben. Leer: keine Grenze.',
    ],
];
