<?php

// Account addresses (`UserAddress`): recipient, app form and list
// (decision 2026-11-18-useraddress-dans-auth). Labels of the address itself
// (street, zip, city) stay in `location::address`.

return [
    'recipient' => 'Empfänger',
    'indication' => 'Wegbeschreibung',
    'indication_placeholder' => 'Z. B. hinter dem Markt, blaues Tor',
    'street_help' => 'Geben Sie mindestens 3 Zeichen ein: Bekannte Adressen werden vorgeschlagen.',
    'detach' => 'Entfernen',
    'form' => [
        'create_title' => 'Adresse hinzufügen',
        'edit_title' => 'Adresse bearbeiten',
        'save' => 'Speichern',
        'saved' => 'Adresse gespeichert.',
    ],
    'list' => [
        'title' => 'Meine Adressen',
        'empty' => 'Noch keine Adresse.',
        'search' => 'Adresse suchen…',
        'create' => 'Adresse hinzufügen',
        'confirm' => 'Diese Adresse entfernen?',
        'detached' => 'Adresse entfernt.',
    ],
];
