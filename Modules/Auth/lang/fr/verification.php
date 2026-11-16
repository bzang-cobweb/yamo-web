<?php

// Codes de vérification (VerificationCodeService) : e-mail, SMS et erreurs.
return [
    'mail' => [
        'subject' => 'Votre code yamo : :code',
        'intro' => 'Voici votre code de vérification yamo :',
        'expires' => 'Il est valable :minutes minutes.',
        'ignore' => 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.',
    ],
    'sms' => 'yamo : votre code est :code (valable :minutes min).',
    'errors' => [
        'unknown' => 'Ce code n\'est plus valable. Recommencez.',
        'expired' => 'Ce code a expiré. Demandez-en un nouveau.',
        'exhausted' => 'Trop d\'essais. Demandez un nouveau code.',
        'wrong' => 'Code incorrect.',
        'too_soon' => 'Patientez une minute avant de demander un nouveau code.',
    ],
];
