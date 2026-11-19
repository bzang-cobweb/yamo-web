<?php

return [
    'title' => 'Anwendung',
    'navigation_group' => 'Einstellungen',
    'sections' => [
        'general' => 'Vorstellung',
        'payment' => 'Zahlung',
        'users' => 'Benutzer',
    ],
    'description' => 'Beschreibung',
    'payment_required' => 'Zahlung anwenden',
    'minimum_age' => 'Mindestalter',
    'years' => 'Jahre',
    'help' => [
        'description' => 'Vorstellung der Anwendung auf der Startseite, eine pro Sprache.',
        'payment_required' => 'Wenn aktiviert, ist ein Angebot nach seinem Ablaufdatum für andere nicht mehr sichtbar; ohne Datum ist es nicht sichtbar. Sein Verfasser sieht es immer.',
        'minimum_age' => 'Wenn angegeben, wird das Geburtsdatum bei der Registrierung und in den persönlichen Daten Pflicht und muss mindestens dieses Alter ergeben. Leer: keine Grenze.',
    ],
];
