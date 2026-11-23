<?php

// Réservations et visites (décision 2026-11-19-contrat-reservations).

return [
    'types' => ['stay' => 'Séjour', 'visit' => 'Visite'],
    'actions' => ['stay' => 'Réserver', 'visit' => 'Demander une visite'],
    'statuses' => ['pending' => 'En attente', 'accepted' => 'Acceptée', 'refused' => 'Refusée', 'cancelled' => 'Annulée',
        // Enregistrées chaque nuit par booking:complete et booking:expire (décisions 2026-11-22-statut-termine-enregistre
        // et 2026-11-22-statut-expire-enregistre).
        'completed' => 'Terminée', 'expired' => 'Expirée'],
    // Les libellés des lignes d'une demande (décision 2026-11-20-lignes-demande).
    'lines' => [
        'member' => 'Membre',
        'phone' => 'Téléphone',
        'email' => 'E-mail',
        'address' => 'Adresse',
        'guests' => 'Personnes',
        'price' => 'Prix',
        'price_per' => 'Prix par :unit',
        'rent' => 'Loyer mensuel',
        'rent_net' => 'Loyer mensuel (sans charges)',
        'days' => 'Durée',
        'total' => 'Total',
        'total_without_charges' => 'Total (sans charges)',
        'charges' => 'Charges mensuelles',
        'deposit' => 'Caution',
        'deposit_months' => ':count mois de caution',
        'advance_months' => ':count mois d’avance',
    ],
    // Les dates d'une demande, libellé et valeur (décision 2026-11-20-heures-arrivee-depart).
    'dates' => [
        'arrival' => 'Arrivée',
        'departure' => 'Départ',
        'visit' => 'Visite',
        'second' => 'Autre visite',
        'from' => ':date à partir de :time',
        'until' => ':date au plus tard à :time',
    ],
    'period' => [
        'stay' => 'du :from au :to',
        'visit' => 'le :date',
        // Une visite avec une seconde date proposée.
        'visit_or' => 'le :date ou :second',
    ],
    'own_offer' => 'Vous ne pouvez pas réserver votre propre offre.',
    // Le bouton d'une demande terminée, et de l'invitation à donner son avis (décision 2026-11-22-avis-apres-demande-terminee).
    // La sortie de booking:complete (décision 2026-11-22-statut-termine-enregistre).
    'completed_count' => ':count demande(s) passée(s) au statut « Terminée ».',
    // La sortie de booking:expire (décision 2026-11-22-statut-expire-enregistre).
    'expired_count' => ':count demande(s) passée(s) au statut « Expirée ».',
    // Inviter à donner son avis depuis la page d'une demande terminée (décision 2026-11-22-inviter-avis-depuis-la-demande).
    'invite_review' => [
        'button' => 'Inviter à donner un avis',
        'title' => 'Inviter à donner un avis',
        'submit' => 'Envoyer l\'invitation',
        'sent' => 'Invitation envoyée.',
        'not_possible' => 'Le membre ne peut pas être invité : la demande n\'est pas terminée ou il a déjà donné son avis.',
    ],
    'review_cta' => 'Laisser un avis',
    'expired' => 'Cette demande a expiré : sa date est passée.',
    'not_cancellable' => 'Cette demande ne peut plus être annulée.',
    'already_answered' => 'Cette demande a déjà une réponse.',
    'not_editable' => 'Cette demande a déjà une réponse : elle ne se modifie plus.',
    'unavailable' => "Ces dates ne sont pas disponibles : choisissez d'autres jours.",
    'accept_unavailable' => 'Ces dates chevauchent un séjour accepté ou un jour bloqué : la demande ne peut pas être acceptée.',
    'overlap_refusal' => 'Ces dates ne sont plus disponibles.',
    'form' => [
        'stay_title' => 'Réserver',
        'visit_title' => 'Demander une visite',
        'check_in' => "Date d'arrivée",
        'check_out' => 'Date de départ',
        'visit_date' => 'Date souhaitée',
        'visit_second_date' => 'Autre date (facultatif)',
        'guests' => 'Voyageurs',
        'message' => 'Message au propriétaire',
        'message_placeholder' => 'Présentez-vous, précisez vos besoins…',
        'price_help' => ':price ; le total figure dans le détail de la réservation une fois la demande envoyée.',
        'submit_stay' => 'Envoyer la demande',
        'submit_visit' => 'Envoyer la demande',
        'sent_stay' => 'Demande envoyée. Retrouvez-la, avec son total, dans « Mes réservations ».',
        'sent_visit' => 'Demande de visite envoyée. Le propriétaire vous répondra.',
        'pending_exists' => 'Vous avez déjà une demande en attente pour cette offre.',
        'own_overlap' => 'Vous avez déjà une demande pour ce logement sur ces dates.',
        'edit_title' => 'Modifier la demande',
        'save' => 'Enregistrer',
        'updated' => 'Demande modifiée.',
        'edit' => 'Modifier',
        'too_far' => 'Choisissez une date dans les :days prochains jours.',
    ],
    'answer' => [
        'title' => 'Répondre à la demande',
        'guest' => 'Demandée par',
        'dates' => 'Dates',
        'answer' => 'Votre réponse',
        'accept' => 'Accepter',
        'refuse' => 'Refuser',
        'refusal_message' => 'Message (facultatif)',
        // Le message d'une acceptation (décision 2026-11-22-accepter-avec-message).
        'acceptance_message' => 'Message (facultatif)',
        'submit' => 'Envoyer la réponse',
        'accepted' => 'Demande acceptée.',
        'refused' => 'Demande refusée.',
        'sent' => 'Réponse envoyée.',
    ],
    'cancel' => [
        'label' => 'Annuler la demande',
        'confirm' => 'Annuler cette demande ? Le propriétaire sera prévenu.',
        'done' => 'Demande annulée.',
        // Depuis la page de la demande (décision 2026-11-21-annuler-depuis-le-detail).
        'button' => 'Annuler',
        'submit' => 'Confirmer',
        'message' => 'Message au propriétaire',
        'message_placeholder' => 'Expliquez pourquoi vous annulez (facultatif)…',
    ],
    'list' => [
        // Le nombre de la liste, sous les filtres (décision 2026-11-22-listes-nombre-en-tete).
        'count' => '{0} Aucune réservation|{1} :count réservation|[2,*] :count réservations',
        'mine' => 'Mes réservations',
        'mine_empty' => "Aucune demande pour l'instant. Réservez ou demandez une visite depuis la fiche d'une offre.",
        'received' => 'Réservations reçues',
        'received_empty' => 'Aucune demande reçue pour l\'instant.',
        'guests' => ':count voyageur(s)',
    ],
    // Le suivi d'une demande (décision 2026-11-20-suivi-demande).
    'history' => [
        'received' => 'Demande envoyée',
        'accepted' => 'Demande acceptée',
        'refused' => 'Demande refusée',
        'cancelled' => 'Demande annulée',
        'updated' => 'Demande modifiée',
    ],
    // Le titre de la page d'une demande (décision 2026-11-22-detail-titre-sejour-visite).
    'detail_title' => ['stay' => 'Demande de séjour', 'visit' => 'Demande de visite'],
    'notify' => [
        // Le type d'une notification de demande, par action, sur sa carte et dans le filtre « Type » (décisions
        // 2026-11-22-notification-par-action et 2026-11-22-types-sans-logement) ; `received_own` : sa propre demande.
        'types' => [
            'received' => 'Nouvelle demande',
            'received_own' => 'Demande envoyée',
            'updated' => 'Demande modifiée',
            'accepted' => 'Demande acceptée',
            'refused' => 'Demande refusée',
            'cancelled' => 'Demande annulée',
        ],
        'filter_rental_type' => 'Type de location',
        // Le nombre de personnes d'une demande, dans le texte de la carte.
        'days' => '{1} :count jour|[2,*] :count jours',
        'people' => '{1} :count personne|[2,*] :count personnes',
        'handle' => 'Traiter la demande',
        'title' => 'Demande',
        'greeting' => 'Bonjour :name,',
        'salutation' => "L'équipe :app",
        'requested' => [
            'subject' => 'Nouvelle demande : :offer',
            'body' => ':guest a envoyé une :request pour « :offer » (:period).',
            // Sa propre demande, dans le fil du membre (décision 2026-11-21-reservation-comme-notification).
            'own' => 'Vous avez envoyé une :request pour « :offer » (:period).',
            // Le texte de la carte (décision 2026-11-22-textes-visite-sejour) ; un séjour, avec les personnes.
            'summary' => 'Une :request pour « :offer » (:period) a été enregistrée.',
            'summary_stay' => 'Une :request pour « :offer » (:period, :people) a été enregistrée.',
            'sms' => ':app : :guest a envoyé une :request pour « :offer » (:period). Répondez dans l\'app.',
        ],
        'accepted' => [
            'subject' => 'Demande acceptée : :offer',
            'body' => 'Votre :request pour « :offer » (:period) a été acceptée.',
            // Sa propre réponse, dans le fil du propriétaire (décision 2026-11-20-reponse-dans-son-fil).
            'own' => 'Vous avez accepté la :request de :guest pour « :offer » (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-reponse-visite-sejour).
            'summary' => 'Votre :request pour « :offer » (:period) a été acceptée.',
            'summary_stay' => 'Votre :request pour « :offer » (:period, :people) a été acceptée.',
            'sms' => ':app : votre :request pour « :offer » (:period) a été acceptée.',
        ],
        'refused' => [
            'subject' => 'Demande refusée : :offer',
            'body' => 'Votre :request pour « :offer » (:period) a été refusée.',
            // Sa propre réponse, dans le fil du propriétaire (décision 2026-11-20-reponse-dans-son-fil).
            'own' => 'Vous avez refusé la :request de :guest pour « :offer » (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-reponse-visite-sejour).
            'summary' => 'Votre :request pour « :offer » (:period) a été refusée.',
            'summary_stay' => 'Votre :request pour « :offer » (:period, :people) a été refusée.',
            'sms' => ':app : votre :request pour « :offer » (:period) a été refusée.',
        ],
        'cancelled' => [
            'subject' => 'Demande annulée : :offer',
            'body' => ':guest a annulé sa :request pour « :offer » (:period).',
            'own' => 'Vous avez annulé votre :request pour « :offer » (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-annulation-visite-sejour).
            'summary' => 'La :request pour « :offer » (:period) a été annulée.',
            'summary_stay' => 'La :request pour « :offer » (:period, :people) a été annulée.',
            'sms' => ':app : :guest a annulé sa :request pour « :offer » (:period).',
        ],
        // La demande modifiée par le membre (décision 2026-11-21-demande-modifiee-notification).
        'updated' => [
            'subject' => 'Demande modifiée : :offer',
            'body' => ':guest a modifié sa :request pour « :offer » (:period).',
            'own' => 'Vous avez modifié votre :request pour « :offer » (:period).',
            'summary' => 'La :request pour « :offer » a été modifiée (:period).',
            'summary_stay' => 'La :request pour « :offer » a été modifiée (:period, :people).',
            'sms' => ':app : :guest a modifié sa :request pour « :offer » (:period). Répondez dans l\'app.',
        ],
        'message' => 'Message : :message',
        'open_app' => 'Retrouvez vos demandes dans l\'app, rubrique « :section ».',
        'type_lower' => ['stay' => 'un séjour', 'visit' => 'une visite'],
        // L'invitation à donner son avis, le lendemain de la fin (décision 2026-11-22-invitation-avis).
        'review' => [
            'title' => 'Votre avis',
            'subject' => 'Votre avis sur « :offer »',
            'body' => 'Votre séjour à « :offer » (:period) est terminé. Laissez un avis.',
            'body_visit' => 'Votre visite de « :offer » (:period) est terminée. Laissez un avis.',
            'sms' => ':app : votre séjour à « :offer » est terminé. Laissez un avis dans l\'app.',
            'sms_visit' => ':app : votre visite de « :offer » est terminée. Laissez un avis dans l\'app.',
            'open_app' => 'Ouvrez l\'app pour donner votre avis sur le logement.',
            'sent' => ':count invitation(s) à donner son avis envoyée(s).',
        ],
        // « demande de séjour », « demande de visite » : les textes d'une annulation (décision 2026-11-22-annulation-visite-sejour).
        'request_lower' => ['stay' => 'demande de séjour', 'visit' => 'demande de visite'],
    ],
    'resource' => [
        'singular' => 'Réservation',
        'plural' => 'Réservations',
        'offer' => 'Offre',
        'guest' => 'Membre',
        'type' => 'Type',
        'check_in' => 'Arrivée',
        'check_out' => 'Départ',
        'guests' => 'Voyageurs',
        'show_address' => 'Adresse affichée au voyageur',
        'message' => 'Message',
        'total' => 'Total',
        'status' => 'Statut',
        'acceptance_message' => 'Message d\'acceptation',
        'refusal_message' => 'Message de refus',
        'answered_at' => 'Réponse le',
        'cancelled_at' => 'Annulée le',
        'created_at' => 'Demandée le',
    ],
    // Profil : réservations, logements, demandes par logement (décision 2026-11-19-contrat-reservations-logements-profil).
    'profile' => [
        'filter_status' => 'Statut',
        // L'ancien « Statut » de « Mes réservations », renommé (décision 2026-11-21-reservations-filtre-statut).
        'filter_period' => 'Période',
        // D'après les dates seulement (décision 2026-11-21-reservations-periode-dates).
        'period_upcoming' => 'À venir',
        'period_ongoing' => 'En cours',
        'period_past' => 'Passées',
        'filter_any' => 'Tous',
        'filter_upcoming' => 'À venir',
        'filter_pending' => 'En attente',
        'filter_past' => 'Passée',
        'filter_city' => 'Ville',
        'filter_offer' => 'Logement',
        'search_home' => 'Rechercher un logement',
        'requests_all' => 'Demandes',
        'requests_count' => '{0} Aucune demande reçue|{1} :count demande reçue|[2,*] :count demandes reçues',
        'pending_count' => '{1} :count demande en attente|[2,*] :count demandes en attente',
        'unread_count' => '{1} :count notification non lue|[2,*] :count notifications non lues',
        'notifications' => 'Demandes reçues',
        'show_address' => 'Afficher l\'adresse',
        'hide_address' => 'Masquer l\'adresse',
        'address_shown' => 'Adresse affichée au voyageur.',
        'address_hidden' => 'Adresse masquée au voyageur.',
        'accept' => 'Accepter',
        // Le nombre des autres demandes, sans leurs noms (décision 2026-11-22-accepter-nombre-chevauchements).
        'accept_overlaps' => '{1} :count autre demande chevauche ces dates et sera refusée.|[2,*] :count autres demandes chevauchent ces dates et seront refusées.',
        'accepted_notice' => 'Demande acceptée.',
        'refuse' => 'Refuser',
        'accept_title' => 'Accepter la demande',
        'accept_submit' => 'Accepter la demande',
        'refuse_title' => 'Refuser la demande',
        'refuse_submit' => 'Refuser la demande',
        'offer_status' => 'Statut',
        'filter_booking_status' => 'Demande',
    ],
    // Le calendrier d'une offre (décision 2026-11-19-contrat-calendrier-offre).
    'calendar' => [
        'title' => 'Calendrier',
        'button' => 'Calendrier',
        'block' => 'Bloquer ce jour',
        'block_confirm' => 'Bloquer ce jour ? Il ne pourra plus être demandé.',
        'blocked' => 'Jour bloqué.',
        'unblock' => 'Débloquer',
        'unblocked' => 'Jour débloqué.',
        'block_accepted' => 'Un séjour accepté occupe ce jour : il ne peut pas être bloqué.',
        'block_past' => 'Ce jour est passé.',
        'empty_day' => 'Aucune demande ce jour-là.',
        'legend' => [
            'accepted' => 'Séjour accepté',
            'pending' => 'Demande en attente',
            'conflict' => 'Chevauchement',
            'blocked' => 'Bloqué',
            'visit_accepted' => 'Visite acceptée',
            'visit_pending' => 'Visite en attente',
        ],
    ],
];
