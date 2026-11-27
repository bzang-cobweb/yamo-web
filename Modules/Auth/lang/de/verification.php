<?php

// Codes de vérification (VerificationCodeService) : e-mail, SMS et erreurs.
return [
    'mail' => [
        'subject' => 'Ihr yamo-Code: :code',
        'intro' => 'Hier ist Ihr yamo-Bestätigungscode:',
        'expires' => 'Er ist :minutes Minuten gültig.',
        'ignore' => 'Wenn Sie ihn nicht angefordert haben, ignorieren Sie diese E-Mail.',
    ],
    'sms' => 'yamo: Ihr Code lautet :code (gültig :minutes Min.).',
    'errors' => [
        'unknown' => 'Dieser Code ist nicht mehr gültig. Bitte von vorne beginnen.',
        'expired' => 'Dieser Code ist abgelaufen. Fordern Sie einen neuen an.',
        'exhausted' => 'Zu viele Versuche. Fordern Sie einen neuen Code an.',
        'wrong' => 'Falscher Code.',
        'too_soon' => 'Warten Sie eine Minute, bevor Sie einen neuen Code anfordern.',
        'already_verified' => 'Bereits bestätigt.',
        'missing' => 'Nichts zu bestätigen: Fügen Sie es zuerst hinzu.',
    ],
];
