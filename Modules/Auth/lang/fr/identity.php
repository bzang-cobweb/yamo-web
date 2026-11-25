<?php

// L'identification d'un compte (décision 2026-11-25-identification).
return [
    'title' => "S'identifier",
    'documents' => "Pièce d'identité",
    'documents_help' => "Des photos lisibles de votre carte d'identité, de votre passeport ou de votre permis de conduire (recto et verso), jusqu'à :max. Seule l'équipe les voit.",
    'add' => 'Ajouter des photos',
    'submit' => 'Envoyer',
    'sent' => "Merci ! L'équipe va vérifier votre pièce d'identité.",
    'notice' => [
        'missing' => "Envoyez une pièce d'identité : une fois vérifiée par l'équipe, votre profil sera affiché comme identifié.",
        'pending' => "Votre pièce d'identité est en cours de vérification par l'équipe.",
        'identified' => 'Votre compte est identifié depuis le :date. Les photos validées restent ; vous pouvez en ajouter.',
    ],
    'admin' => [
        'section' => 'Identification',
        'identified_at' => "Date d'identification",
        'documents' => "Pièces d'identité",
        'no_documents' => 'Aucun document envoyé.',
        'validate' => "Valider l'identité",
        'validated' => 'Identité validée.',
        'revoke' => "Retirer l'identification",
        'revoked' => 'Identification retirée.',
        'identified' => 'Identifié',
    ],
];
