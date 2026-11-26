<?php

// Dem Anbieter eines Angebots schreiben und antworten (Entscheidung 2026-11-26-messages-editeur).
return [
    'action' => 'Nachricht senden',
    'title' => 'Nachricht senden',
    'field' => 'Nachricht',
    'placeholder' => 'Ihre Nachricht',
    'submit' => 'Senden',
    'sent' => 'Nachricht gesendet.',
    'own_offer' => 'Sie können sich nicht selbst schreiben.',
    'not_started' => 'Sie können nur einem Mitglied antworten, das Ihnen zu diesem Angebot geschrieben hat.',
    'not_yours' => 'Sie können in dieser Unterhaltung nicht schreiben.',
    'notify' => [
        // Le type « Message » du filtre des notifications (décision 2026-11-26-filtre-type-en-puces).
        'filter_type' => 'Nachricht',
        'title' => 'Nachricht',
        'summary' => 'Zu „:offer“',
        'reply' => 'Antworten',
        'subject' => 'Neue Nachricht zu „:offer“',
        'intro' => ':name hat Ihnen zu „:offer“ geschrieben.',
        'open_app' => 'Antworten Sie im Tab „Benachrichtigungen“ von :app.',
        'sms' => ':app: :name hat Ihnen zu „:offer“ geschrieben. Antworten Sie in der App.',
    ],
];
