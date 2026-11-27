<?php

// Écrire à l'éditeur d'une offre, et lui répondre (décision 2026-11-26-messages-editeur).
return [
    // Le bouton de la section « Éditeur » de la fiche, et le titre du formulaire.
    'action' => 'Envoyer un message',
    'title' => 'Envoyer un message',
    'field' => 'Message',
    'placeholder' => 'Votre message',
    'submit' => 'Envoyer',
    'sent' => 'Message envoyé.',
    'own_offer' => 'Vous ne pouvez pas vous écrire à vous-même.',
    'not_started' => 'Vous ne pouvez répondre qu\'à un membre qui vous a écrit au sujet de cette offre.',
    'not_yours' => 'Vous ne pouvez pas écrire dans cette discussion.',
    'notify' => [
        // Le type « Message » du filtre des notifications (décision 2026-11-26-filtre-type-en-puces).
        'filter_type' => 'Message',
        'title' => 'Message',
        'reply' => 'Écrire',
        'subject' => 'Nouveau message au sujet de « :offer »',
        'intro' => ':name vous a écrit au sujet de « :offer ».',
        'open_app' => 'Répondez depuis l\'onglet Notifications de :app.',
        'sms' => ':app : :name vous a écrit au sujet de « :offer ». Répondez dans l\'app.',
    ],
];
