<?php

// Le module Partner : les partenaires de l'accueil (décision 2026-11-23-module-partenaires).

return [
    'singular' => 'Partenaire',
    'plural' => 'Partenaires',
    'sections' => [
        'identity' => 'Partenaire',
        'content' => 'Ce que montre l\'app',
        'campaign' => 'Campagne',
        'contact' => 'Contact',
        'stats' => 'Statistiques',
    ],
    // Décision 2026-11-24-partenaires-statuts.
    'statuses' => [
        'pending' => 'En validation',
        'awaiting_payment' => 'En attente de paiement',
        'published' => 'En ligne',
        'rejected' => 'Refusé',
    ],
    'fields' => [
        'slogan' => 'Slogan',
        'status' => 'Statut',
        'user' => 'Compte',
        'is_admin' => 'Administrateur',
        'make_admin' => 'Faire administrateur',
        'remove_admin' => "Retirer l'administration",
        'proposed' => "Proposé depuis l'app",
        'name' => 'Nom',
        'country' => 'Pays',
        'logo' => 'Logo',
        // L'aide du champ logo : la conversion `list` en minimum (décisions 2026-11-24-logo-taille-minimale et 2026-11-24-partenaire-logo-original).
        'logo_help' => 'Logo carré, au moins :pixels × :pixels px.',
        'description' => 'Description',
        'website' => 'Lien',
        'cta_label' => 'Libellé du bouton',
        'published' => 'Publié',
        'starts_at' => 'Début',
        'expires_at' => 'Expire le',
        'featured_until' => "En vedette jusqu'au",
        'featured_help' => "Vide : pas en vedette. En vedette, il passe avant les autres partenaires, sur l'accueil et sur la page Partenaires.",
        'contact_email' => 'E-mail de contact',
        'contact_phone' => 'Téléphone de contact',
        'views_count' => 'Vues',
        'clicks_count' => 'Clics',
    ],
    // « Mes partenaires » dans l'app (décision 2026-11-24-partenaires-par-les-membres).
    'member' => [
        // La barre fixe de « Partenaires » et « Mes partenaires » (décision 2026-11-24-devenir-partenaire).
        'become' => 'Devenir partenaire',
        // Le bouton de « Mes partenaires » (décision 2026-11-27-mes-partenaires-ajouter).
        'add' => 'Ajouter un partenaire',
        // Le bouton du champ logo (décision 2026-11-24-champ-fichier-libelle-ajout).
        'add_logo' => 'Ajouter un logo',
        // Les étapes du formulaire de l'app (décision 2026-11-24-etape-presentation).
        'steps' => [
            'presentation' => 'Présentation',
        ],
        'group' => 'Partenaire',
        'count' => '{0} Aucun partenaire|{1} :count partenaire|[2,*] :count partenaires',
        // Le nombre de vues, sur les cartes du propriétaire (décision 2026-11-25-compteurs-offres).
        'views' => '{0} Aucune vue|{1} :count vue|[2,*] :count vues',
        'mine' => 'Mes partenaires',
        'add' => 'Ajouter un partenaire',
        'edit' => 'Modifier le partenaire',
        'submit' => 'Envoyer pour validation',
        'save' => 'Enregistrer',
        'submitted' => "Partenaire envoyé : l'équipe le valide avant de le publier.",
        'saved' => "Partenaire enregistré : l'équipe le valide de nouveau avant de le publier.",
        // La note du formulaire, à la création seulement ; le paiement s'il est appliqué (décision 2026-11-24-partenaire-note-creation).
        'review_notice' => "Nous allons d'abord valider vos données avant d'afficher ce partenaire sur l'application.",
        'review_notice_paid' => "Nous allons d'abord valider vos données et confirmer le paiement avant d'afficher ce partenaire sur l'application.",
        // La note du formulaire à la modification, selon le statut (décision 2026-11-24-partenaire-attente-paiement).
        'edit_notice' => [
            'pending' => "Ce partenaire est encore en cours de validation et n'est pas affiché sur l'application.",
            'rejected' => "Ce partenaire a été refusé et n'est pas affiché sur l'application.",
            'awaiting_payment' => "Ce partenaire est en attente de paiement et n'est pas affiché sur l'application.",
            'shown' => "Ce partenaire est affiché sur l'application.",
            'shown_until' => "Ce partenaire est affiché sur l'application jusqu'au :date.",
        ],
        'cta_help' => 'Le texte du bouton qui mène à votre lien. Vide : « Visiter le site ».',
        'empty' => "Aucun partenaire pour l'instant.",
        'remove' => 'Retirer',
        'remove_confirm' => 'Retirer ce partenaire ?',
        'removed' => 'Partenaire retiré.',
        'status' => [
            'shown' => 'En ligne',
            'hidden' => 'Pas affiché',
        ],
    ],
    // Les comptes qui administrent un partenaire, au back-office.
    'admins' => 'Administrateurs',
    // « Valider » au back-office (décision 2026-11-24-partenaires-validation-back-office).
    'validate' => [
        'label' => 'Valider',
        'heading' => 'Valider ce partenaire ?',
        'description' => 'Il passe en « Publié » et ses comptes sont prévenus.',
        'done' => 'Partenaire accepté.',
    ],
    // Un changement de statut, aux comptes du partenaire (décision 2026-11-24-partenaires-notifications).
    'notify' => [
        // Les champs changés d'une modification (décision 2026-11-26-partenaire-modifications).
        'changes' => ['line' => ':field : :value', 'empty' => '(vide)', 'before' => ':field (avant)', 'after' => ':field (après)', 'at' => 'Dernière modification', 'by' => 'Modifié par', 'title' => 'Modifications'],
        // La liste « Notifications » : le type « Partenaire », puis un type par statut, ses statuts dans le filtre « Statut » (décision 2026-11-24-notifications-types-groupes-statut).
        'filter_type' => 'Partenaire',
        'greeting' => 'Bonjour :name,',
        'salutation' => "L'équipe :app",
        'title' => [
            'pending' => 'Partenaire en validation',
            'awaiting_payment' => 'Partenaire en attente de paiement',
            'published' => 'Partenaire accepté',
            'rejected' => 'Partenaire refusé',
        ],
        'body' => [
            'pending' => "Votre partenaire « :name » est en validation par l'équipe.",
            'awaiting_payment' => 'Votre partenaire « :name » est en attente de paiement.',
            'published' => "Votre partenaire « :name » a été accepté par l'équipe.",
            'rejected' => "Votre partenaire « :name » a été refusé par l'équipe.",
        ],
        // Ajouté ou modifié depuis l'app, en validation (décision 2026-11-24-partenaires-validation-notifications).
        // Ajouté ou modifié au back-office, aux administrateurs du partenaire (décision 2026-11-24-partenaire-demandes).
        'team' => [
            'created' => "Votre partenaire « :name » a été ajouté par l'équipe.",
            'updated' => "Votre partenaire « :name » a été modifié par l'équipe.",
        ],
        'submitted' => [
            'title' => ['created' => 'Partenaire ajouté', 'updated' => 'Partenaire modifié'],
            'own' => [
                'created' => "Votre partenaire « :name » est en validation : l'équipe le vérifie avant de l'afficher.",
                'updated' => 'Votre partenaire « :name » a été modifié.',
            ],
            'others' => [
                'created' => '« :name » a été ajouté par :actor ; il est en validation.',
                // Pour les destinataires : le partenaire, sans qui l'a modifié, nommé au-dessus sur la carte (décision
                // 2026-11-26-partenaire-modifie-destinataires).
                'updated' => 'Le partenaire « :name » a été modifié.',
            ],
        ],
    ],
    // La langue de saisie à la modification (décision 2026-11-24-mes-partenaires-cartes).
    'content_locale' => [
        'step' => 'Langue',
        'label' => 'Langue de saisie',
        'help' => 'Dans quelle langue modifiez-vous ce partenaire ? Une langue à traduire est pré-remplie avec un texte existant.',
        'translated' => 'traduite',
        'to_translate' => 'à traduire',
    ],
    // La commande partner:await-payment (décision 2026-11-24-partenaire-attente-paiement-commande).
    'await_payment' => [
        'disabled' => "Le paiement des partenaires n'est pas appliqué : rien à faire.",
        'count' => '{0} Aucun partenaire passé en attente de paiement.|{1} :count partenaire passé en attente de paiement.|[2,*] :count partenaires passés en attente de paiement.',
    ],
];
