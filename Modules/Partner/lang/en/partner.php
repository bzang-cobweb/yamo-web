<?php

// Le module Partner : les partenaires de l'accueil (décision 2026-11-23-module-partenaires).

return [
    'singular' => 'Partner',
    'plural' => 'Partners',
    'sections' => [
        'identity' => 'Partner',
        'content' => 'What the app shows',
        'campaign' => 'Campaign',
        'contact' => 'Contact',
        'stats' => 'Statistics',
    ],
    // Décision 2026-11-24-partenaires-statuts.
    'statuses' => [
        'pending' => 'Under review',
        'published' => 'Published',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ],
    'fields' => [
        'slogan' => 'Slogan',
        'status' => 'Status',
        'user' => 'Account',
        'is_admin' => 'Administrator',
        'make_admin' => 'Make administrator',
        'remove_admin' => 'Remove administration',
        'proposed' => 'Proposed from the app',
        'name' => 'Name',
        'country' => 'Country',
        'logo' => 'Logo',
        // L'aide du champ logo : la conversion `list` en minimum (décisions 2026-11-24-logo-taille-minimale et 2026-11-24-partenaire-logo-original).
        'logo_help' => 'Square logo, at least :pixels × :pixels px.',
        'description' => 'Description',
        'url' => 'Link',
        'cta_label' => 'Button label',
        'published' => 'Published',
        'starts_at' => 'Starts',
        'expires_at' => 'Expires on',
        'contact_email' => 'Contact email',
        'contact_phone' => 'Contact phone',
        'views_count' => 'Views',
        'clicks_count' => 'Clicks',
    ],
    // « Mes partenaires » dans l'app (décision 2026-11-24-partenaires-par-les-membres).
    'member' => [
        // La barre fixe de « Partenaires » et « Mes partenaires » (décision 2026-11-24-devenir-partenaire).
        'become' => 'Become a partner',
        // Le bouton du champ logo (décision 2026-11-24-champ-fichier-libelle-ajout).
        'add_logo' => 'Add a logo',
        // Les étapes du formulaire de l'app (décision 2026-11-24-etape-presentation).
        'steps' => [
            'presentation' => 'Presentation',
        ],
        'group' => 'Partner',
        'count' => '{0} No partner|{1} :count partner|[2,*] :count partners',
        'mine' => 'My partners',
        'add' => 'Add a partner',
        'edit' => 'Edit the partner',
        'submit' => 'Send for review',
        'save' => 'Save and resend',
        'submitted' => 'Partner sent: the team reviews it before publishing.',
        'saved' => 'Partner saved: the team reviews it again before publishing.',
        'review_notice' => 'The team reviews every partner, and every change, before showing it in the app.',
        'cta_help' => 'The text of the button leading to your link. Empty: « Visit the website ».',
        'empty' => 'No partner yet.',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove this partner?',
        'removed' => 'Partner removed.',
        'status' => [
            'shown' => 'Shown',
            'hidden' => 'Not shown',
        ],
    ],
    // Les comptes qui administrent un partenaire, au back-office.
    'admins' => 'Administrators',
    // « Valider » au back-office (décision 2026-11-24-partenaires-validation-back-office).
    'validate' => [
        'label' => 'Approve',
        'heading' => 'Approve this partner?',
        'description' => 'It becomes « Published » and its accounts are notified.',
        'done' => 'Partner accepted.',
    ],
    // Un changement de statut, aux comptes du partenaire (décision 2026-11-24-partenaires-notifications).
    'notify' => [
        // La liste « Notifications » : le type « Partenaire », puis un type par statut (décision 2026-11-24-partenaire-notification-par-statut).
        'filter_type' => 'Partner',
        'greeting' => 'Hello :name,',
        'salutation' => 'The :app team',
        'title' => [
            'pending' => 'Partner under review',
            'published' => 'Partner accepted',
            'rejected' => 'Partner rejected',
            'archived' => 'Partner archived',
        ],
        'body' => [
            'pending' => 'Your partner « :name » is under review by the team.',
            'published' => 'Your partner « :name » was accepted by the team.',
            'rejected' => 'Your partner « :name » was rejected by the team.',
            'archived' => 'Your partner « :name » is archived.',
        ],
        'submitted' => [
            'title' => ['created' => 'Partner added', 'updated' => 'Partner updated'],
            'own' => [
                'created' => 'Your partner « :name » is under review: the team checks it before showing it.',
                'updated' => 'Your partner « :name » is under review: the team checks your changes before showing it.',
            ],
            'others' => [
                'created' => '« :name » was added by :actor; it is under review.',
                'updated' => '« :name » was updated by :actor; it is under review.',
            ],
        ],
    ],
    // La langue de saisie à la modification (décision 2026-11-24-mes-partenaires-cartes).
    'content_locale' => [
        'step' => 'Language',
        'label' => 'Writing language',
        'help' => 'In which language are you editing this partner? A language to translate is prefilled with an existing text.',
        'translated' => 'translated',
        'to_translate' => 'to translate',
    ],
];
