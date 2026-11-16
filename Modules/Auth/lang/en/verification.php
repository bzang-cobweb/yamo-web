<?php

// Codes de vérification (VerificationCodeService) : e-mail, SMS et erreurs.
return [
    'mail' => [
        'subject' => 'Your yamo code: :code',
        'intro' => 'Here is your yamo verification code:',
        'expires' => 'It is valid for :minutes minutes.',
        'ignore' => 'If you did not request it, ignore this e-mail.',
    ],
    'sms' => 'yamo: your code is :code (valid :minutes min).',
    'errors' => [
        'unknown' => 'This code is no longer valid. Start again.',
        'expired' => 'This code has expired. Request a new one.',
        'exhausted' => 'Too many attempts. Request a new code.',
        'wrong' => 'Incorrect code.',
        'too_soon' => 'Wait a minute before requesting a new code.',
    ],
];
