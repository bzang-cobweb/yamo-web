<?php

// Réservations et visites (décision 2026-11-19-contrat-reservations).

return [
    'types' => ['stay' => 'Stay', 'visit' => 'Visit'],
    'actions' => ['stay' => 'Book', 'visit' => 'Request a visit'],
    'statuses' => ['pending' => 'Pending', 'accepted' => 'Accepted', 'refused' => 'Declined', 'cancelled' => 'Cancelled',
        // Enregistrées chaque nuit par booking:complete et booking:expire (décisions 2026-11-22-statut-termine-enregistre
        // et 2026-11-22-statut-expire-enregistre).
        'completed' => 'Completed', 'expired' => 'Expired'],
    // Les libellés des lignes d'une demande (décision 2026-11-20-lignes-demande).
    'lines' => [
        'member' => 'Member',
        'phone' => 'Phone',
        'email' => 'Email',
        'address' => 'Address',
        'guests' => 'Guests',
        'price' => 'Price',
        'price_per' => 'Price per :unit',
        'rent' => 'Monthly rent',
        'rent_net' => 'Monthly rent (excl. charges)',
        'days' => 'Duration',
        'total' => 'Total',
        'total_without_charges' => 'Total (excl. charges)',
        'charges' => 'Monthly charges',
        'deposit' => 'Deposit',
        'deposit_months' => '{0} :count months of deposit|{1} :count month of deposit|[2,*] :count months of deposit',
        'advance_months' => '{0} :count months in advance|{1} :count month in advance|[2,*] :count months in advance',
    ],
    // Les dates d'une demande, libellé et valeur (décision 2026-11-20-heures-arrivee-depart).
    'dates' => [
        'arrival' => 'Arrival',
        'departure' => 'Departure',
        'visit' => 'Visit',
        'from' => ':date from :time',
        'until' => ':date by :time',
    ],
    'period' => [
        'stay' => 'from :from to :to',
        'visit' => 'on :date',
        // Une visite avec une seconde date proposée.
    ],
    'own_offer' => 'You cannot book your own offer.',
    // Le bouton d'une demande terminée, et de l'invitation à donner son avis (décision 2026-11-22-avis-apres-demande-terminee).
    // La sortie de booking:complete (décision 2026-11-22-statut-termine-enregistre).
    'completed_count' => ':count request(s) marked as completed.',
    // La sortie de booking:expire (décision 2026-11-22-statut-expire-enregistre).
    'expired_count' => ':count request(s) marked as expired.',
    // Inviter à donner son avis depuis la page d'une demande terminée (décision 2026-11-22-inviter-avis-depuis-la-demande).
    'invite_review' => [
        'button' => 'Invite to review',
        'title' => 'Invite to review',
        'submit' => 'Send the invitation',
        'sent' => 'Invitation sent.',
        'not_possible' => 'The member cannot be invited: the request is not over or they already reviewed.',
    ],
    'review_cta' => 'Leave a review',
    'expired' => 'This request has expired: its date has passed.',
    'not_cancellable' => 'This request can no longer be cancelled.',
    'already_answered' => 'This request has already been answered.',
    'not_editable' => 'This request already has an answer: it can no longer be edited.',
    'unavailable' => 'These dates are not available: please choose other days.',
    'accept_unavailable' => 'These dates overlap an accepted stay or a blocked day: the request cannot be accepted.',
    'overlap_refusal' => 'These dates are no longer available.',
    'form' => [
        'stay_title' => 'Book',
        'visit_title' => 'Request a visit',
        'check_in' => 'Arrival date',
        'check_out' => 'Departure date',
        'visit_date' => 'Preferred date',
        'guests' => 'Guests',
        'message' => 'Message to the owner',
        'message_placeholder' => 'Introduce yourself, tell them what you need…',
        'price_help' => ':price; the total appears in the booking details once the request is sent.',
        'submit_stay' => 'Send request',
        'submit_visit' => 'Send request',
        'sent_stay' => 'Request sent. Find it, with its total, under “My bookings”.',
        'sent_visit' => 'Visit request sent. The owner will reply.',
        'pending_exists' => 'You already have a pending request for this offer.',
        'own_overlap' => 'You already have a request for this home on these dates.',
        'edit_title' => 'Edit the request',
        'save' => 'Save',
        'updated' => 'Request updated.',
        'edit' => 'Edit',
        'too_far' => 'Choose a date within the next :days days.',
    ],
    'answer' => [
        'title' => 'Reply to the request',
        'guest' => 'Requested by',
        'dates' => 'Dates',
        'answer' => 'Your reply',
        'accept' => 'Accept',
        'refuse' => 'Decline',
        'refusal_message' => 'Message (optional)',
        // Le message d'une acceptation (décision 2026-11-22-accepter-avec-message).
        'acceptance_message' => 'Message (optional)',
        'accept_other_visits' => '{1} Also accept the other visit that day|[2,*] Also accept the :count other visits that day',
        'accept_other_visits_short' => 'Also accept the other visits that day',
        'submit' => 'Send reply',
        'accepted' => 'Request accepted.',
        'refused' => 'Request declined.',
        'sent' => 'Reply sent.',
    ],
    'cancel' => [
        'label' => 'Cancel request',
        'confirm' => 'Cancel this request? The owner will be told.',
        'done' => 'Request cancelled.',
        // Depuis la page de la demande (décision 2026-11-21-annuler-depuis-le-detail).
        'button' => 'Cancel',
        'submit' => 'Confirm',
        'message' => 'Message to the owner',
        'message_placeholder' => 'Explain why you are cancelling (optional)…',
    ],
    'list' => [
        // Le nombre de la liste, sous les filtres (décision 2026-11-22-listes-nombre-en-tete).
        'count' => '{0} No bookings|{1} :count booking|[2,*] :count bookings',
        'mine' => 'My bookings',
        'mine_empty' => 'No requests yet.',
        'received' => 'Bookings received',
        'received_empty' => 'No requests received yet.',
        'guests' => ':count guest(s)',
    ],
    // The follow-up of a request (decision 2026-11-20-suivi-demande).
    'history' => [
        'received' => 'Request sent',
        'accepted' => 'Request accepted',
        'refused' => 'Request declined',
        'cancelled' => 'Request cancelled',
        'updated' => 'Request changed',
    ],
    // Le titre de la page d'une demande (décision 2026-11-22-detail-titre-sejour-visite).
    'detail_title' => ['stay' => 'Stay request', 'visit' => 'Visit request'],
    // Le titre de la barre du haut de la page d'une demande (décision 2026-11-22-detail-titre-compose-et-dates).
    'detail' => ['mine' => 'My booking', 'received' => 'Request received'],
    'notify' => [
        // Le type « Réservation » du filtre des notifications (décision 2026-11-24-notifications-types-reservation-partenaire).
        'filter_type' => 'Booking',
        // Le type d'une notification de demande, par action, sur sa carte et dans le filtre « Type » (décisions
        // 2026-11-22-notification-par-action et 2026-11-22-types-sans-logement) ; `received_own` : sa propre demande.
        'types' => [
            'received' => 'New request',
            'received_own' => 'Request sent',
            'updated' => 'Request updated',
            'accepted' => 'Request accepted',
            'refused' => 'Request declined',
            'cancelled' => 'Request cancelled',
        ],
        'filter_rental_type' => 'Rental type',
        // Le nombre de personnes d'une demande, dans le texte de la carte.
        'days' => '{1} :count day|[2,*] :count days',
        'people' => '{1} :count person|[2,*] :count people',
        'handle' => 'Handle the request',
        'title' => 'Request',
        'greeting' => 'Hello :name,',
        'salutation' => 'The :app team',
        'requested' => [
            'subject' => 'New request: :offer',
            'body' => ':guest sent a :request for “:offer” (:period).',
            // Sa propre demande, dans le fil du membre (décision 2026-11-21-reservation-comme-notification).
            'own' => 'You sent a :request for “:offer” (:period).',
            // Le texte de la carte (décision 2026-11-22-textes-visite-sejour) ; un séjour, avec les personnes.
            'summary' => 'A :request for “:offer” (:period) has been recorded.',
            'summary_stay' => 'A :request for “:offer” (:period, :people) has been recorded.',
            'sms' => ':app: :guest sent a :request for “:offer” (:period). Reply in the app.',
        ],
        'accepted' => [
            'subject' => 'Request accepted: :offer',
            'body' => 'Your :request for “:offer” (:period) has been accepted.',
            // Sa propre réponse, dans le fil du propriétaire (décision 2026-11-20-reponse-dans-son-fil).
            'own' => 'You accepted :guest’s :request for “:offer” (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-reponse-visite-sejour).
            'summary' => 'Your :request for “:offer” (:period) has been accepted.',
            'summary_stay' => 'Your :request for “:offer” (:period, :people) has been accepted.',
            'sms' => ':app: your :request for “:offer” (:period) has been accepted.',
        ],
        'refused' => [
            'subject' => 'Request declined: :offer',
            'body' => 'Your :request for “:offer” (:period) has been declined.',
            // Sa propre réponse, dans le fil du propriétaire (décision 2026-11-20-reponse-dans-son-fil).
            'own' => 'You declined :guest’s :request for “:offer” (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-reponse-visite-sejour).
            'summary' => 'Your :request for “:offer” (:period) has been declined.',
            'summary_stay' => 'Your :request for “:offer” (:period, :people) has been declined.',
            'sms' => ':app: your :request for “:offer” (:period) has been declined.',
        ],
        'cancelled' => [
            'subject' => 'Request cancelled: :offer',
            'body' => ':guest cancelled their :request for “:offer” (:period).',
            'own' => 'You cancelled your :request for “:offer” (:period).',
            // Le texte de la carte, sans le nom de qui agit (décision 2026-11-22-annulation-visite-sejour).
            'summary' => 'The :request for “:offer” (:period) has been cancelled.',
            'summary_stay' => 'The :request for “:offer” (:period, :people) has been cancelled.',
            'sms' => ':app: :guest cancelled their :request for “:offer” (:period).',
        ],
        // La demande modifiée par le membre (décision 2026-11-21-demande-modifiee-notification).
        'updated' => [
            'subject' => 'Request changed: :offer',
            'body' => ':guest changed their :request for “:offer” (:period).',
            'own' => 'You changed your :request for “:offer” (:period).',
            'summary' => 'The :request for “:offer” has been changed (:period).',
            'summary_stay' => 'The :request for “:offer” has been changed (:period, :people).',
            'sms' => ':app: :guest changed their :request for “:offer” (:period). Reply in the app.',
        ],
        'message' => 'Message: :message',
        'open_app' => 'Find your requests in the app, under “:section”.',
        'type_lower' => ['stay' => 'a stay', 'visit' => 'a visit'],
        // L'invitation à donner son avis, le lendemain de la fin (décision 2026-11-22-invitation-avis).
        'review' => [
            'title' => 'Your review',
            'subject' => 'Your review of “:offer”',
            'body' => 'Your stay at “:offer” (:period) is over. Leave a review.',
            'body_visit' => 'Your visit of “:offer” (:period) is over. Leave a review.',
            'sms' => ':app: your stay at “:offer” is over. Leave a review in the app.',
            'sms_visit' => ':app: your visit of “:offer” is over. Leave a review in the app.',
            'open_app' => 'Open the app to review the home.',
            'sent' => ':count review invitation(s) sent.',
        ],
        // « demande de séjour », « demande de visite » : les textes d'une annulation (décision 2026-11-22-annulation-visite-sejour).
        'request_lower' => ['stay' => 'stay request', 'visit' => 'visit request'],
    ],
    'resource' => [
        'singular' => 'Booking',
        'plural' => 'Bookings',
        'offer' => 'Offer',
        'guest' => 'Member',
        'type' => 'Type',
        'check_in' => 'Arrival',
        'check_out' => 'Departure',
        'guests' => 'Guests',
        'show_address' => 'Address shown to the guest',
        'message' => 'Message',
        'total' => 'Total',
        'status' => 'Status',
        'acceptance_message' => 'Acceptance message',
        'refusal_message' => 'Decline message',
        'answered_at' => 'Replied on',
        'cancelled_at' => 'Cancelled on',
        'created_at' => 'Requested on',
    ],
    // Profil : réservations, logements, demandes par logement (décision 2026-11-19-contrat-reservations-logements-profil).
    'profile' => [
        'filter_status' => 'Status',
        // L'ancien « Statut » de « Mes réservations », renommé (décision 2026-11-21-reservations-filtre-statut).
        'filter_period' => 'Period',
        // D'après les dates seulement (décision 2026-11-21-reservations-periode-dates).
        'period_upcoming' => 'Upcoming',
        'period_ongoing' => 'Ongoing',
        'period_past' => 'Past',
        'filter_any' => 'All',
        'filter_upcoming' => 'Upcoming',
        'filter_pending' => 'Pending',
        'filter_past' => 'Past',
        'filter_region' => 'Region',
        'filter_city' => 'City',
        'filter_offer' => 'Home',
        'search_home' => 'Find a home',
        'requests_all' => 'Requests',
        'requests_count' => '{0} No requests received|{1} :count request received|[2,*] :count requests received',
        'pending_count' => '{1} :count pending request|[2,*] :count pending requests',
        'unread_count' => '{1} :count unread notification|[2,*] :count unread notifications',
        'notifications' => 'Requests received',
        'show_address' => 'Show the address',
        'hide_address' => 'Hide the address',
        'address_shown' => 'Address shown to the guest.',
        'address_hidden' => 'Address hidden from the guest.',
        'accept' => 'Accept',
        // Le nombre des autres demandes, sans leurs noms (décision 2026-11-22-accepter-nombre-chevauchements).
        'accept_overlaps' => '{1} :count other request overlaps these dates and will be declined.|[2,*] :count other requests overlap these dates and will be declined.',
        // Les autres visites du même jour (décision 2026-11-22-accepter-autres-visites).
        'accept_same_day_visits' => '{1} :count other visit is requested that day.|[2,*] :count other visits are requested that day.',
        'accepted_notice' => 'Request accepted.',
        'refuse' => 'Decline',
        'accept_title' => 'Accept the request',
        'accept_submit' => 'Accept the request',
        'refuse_title' => 'Decline the request',
        'refuse_submit' => 'Decline the request',
        'offer_status' => 'Status',
        'filter_booking_status' => 'Request',
    ],
    // Le calendrier d'une offre (décision 2026-11-19-contrat-calendrier-offre).
    'calendar' => [
        'title' => 'Calendar',
        'button' => 'Calendar',
        'block' => 'Block this day',
        'block_confirm' => 'Block this day? It can no longer be requested.',
        'blocked' => 'Day blocked.',
        'unblock' => 'Unblock',
        'unblocked' => 'Day unblocked.',
        'block_accepted' => 'An accepted stay takes this day: it cannot be blocked.',
        'block_past' => 'This day has passed.',
        'empty_day' => 'No requests on this day.',
        'legend' => [
            'accepted' => 'Accepted stay',
            'pending' => 'Pending request',
            'conflict' => 'Overlap',
            'blocked' => 'Blocked',
            'visit_accepted' => 'Accepted visit',
            'visit_pending' => 'Pending visit',
        ],
    ],
];
