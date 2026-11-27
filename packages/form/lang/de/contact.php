<?php

// Contact details not allowed in a published text (NoContactDetails rule,
// decision 2026-11-19-contrat-annonces-sans-coordonnees). Extend the lists here,
// without touching the code; they are compared lowercase, without accents.
return [
    'message' => 'Entfernen Sie die Kontaktdaten (Telefon, E-Mail, Website, soziales Netzwerk): Sie sind in einem Inserat nicht erlaubt.',
    'networks' => ['whatsapp', 'whats app', 'telegram', 'signal', 'viber', 'messenger', 'facebook', 'fb', 'instagram', 'insta', 'tiktok', 'snapchat', 'twitter', 'linkedin'],
    'phrases' => ['ruf mich an', 'rufen sie mich an', 'meine nummer', 'meine telefonnummer', 'meine handynummer', 'mein handy', 'meine e-mail', 'meine email', 'schreib mir', 'schreiben sie mir', 'kontaktiere mich', 'kontaktieren sie mich', 'melde dich', 'melden sie sich'],
    'digit_words' => ['null', 'eins', 'zwei', 'drei', 'vier', 'funf', 'fuenf', 'sechs', 'sieben', 'acht', 'neun'],
    'at_words' => ['at'],
    'dot_words' => ['punkt'],
    'currencies' => ['xaf', 'cfa', 'fcfa', 'chf', 'eur', 'euros', 'euro', 'usd', 'dollars', 'dollar'],
];
