<?php

// Coordonnées interdites dans un texte publié (règle NoContactDetails, décision
// 2026-11-19-contrat-annonces-sans-coordonnees). Les listes se complètent ici,
// sans toucher au code ; elles sont comparées en minuscules, sans accents.
return [
    'message' => 'Retirez les coordonnées (téléphone, e-mail, site, réseau social) : elles ne sont pas autorisées dans une annonce.',
    // Réseaux et messageries (mot entier).
    'networks' => ['whatsapp', 'whats app', 'wtsp', 'watsap', 'whatsap', 'telegram', 'signal', 'viber', 'messenger', 'facebook', 'fb', 'instagram', 'insta', 'tiktok', 'snapchat', 'twitter', 'linkedin'],
    // Formules d'appel au contact.
    'phrases' => ['appelez-moi', 'appelez moi', 'appelez au', 'appeler au', 'contactez-moi', 'contactez moi', 'contactez nous', 'me contacter', 'nous contacter', 'joindre au', 'appel au', 'contacter au', 'mon numero', 'mon num', 'mon tel', 'mon telephone', 'mon portable', 'mon mail', 'mon e-mail', 'mon email', 'joignable au', 'joignable sur', 'ecrivez-moi', 'ecrivez moi', 'bipez-moi', 'bipez moi', 'texto au', 'sms au'],
    // Chiffres écrits en lettres.
    'digit_words' => ['zero', 'un', 'une', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'],
    // Mots pour « @ » et « . » dans une adresse masquée.
    'at_words' => ['arobase', 'arrobase', 'arobas'],
    'dot_words' => ['point'],
    // Une devise après un nombre : c'est un montant, pas un téléphone.
    'currencies' => ['fcfa', 'f cfa', 'cfa', 'xaf', 'francs', 'franc', 'frs', 'fr', 'f'],
];
