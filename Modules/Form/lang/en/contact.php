<?php

// Contact details not allowed in a published text (NoContactDetails rule,
// decision 2026-11-19-contrat-annonces-sans-coordonnees). Extend the lists here,
// without touching the code; they are compared lowercase, without accents.
return [
    'message' => 'Remove the contact details (phone, e-mail, website, social network): they are not allowed in a listing.',
    'networks' => ['whatsapp', 'whats app', 'telegram', 'signal', 'viber', 'messenger', 'facebook', 'fb', 'instagram', 'insta', 'tiktok', 'snapchat', 'twitter', 'linkedin'],
    'phrases' => ['call me', 'call at', 'my number', 'my phone', 'my cell', 'my mobile', 'my email', 'my e-mail', 'text me', 'reach me', 'contact me', 'email me', 'e-mail me', 'dm me', 'message me'],
    'digit_words' => ['zero', 'oh', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'],
    'at_words' => ['at'],
    'dot_words' => ['dot'],
    'currencies' => ['xaf', 'cfa', 'fcfa', 'chf', 'eur', 'euros', 'euro', 'usd', 'dollars', 'dollar'],
];
