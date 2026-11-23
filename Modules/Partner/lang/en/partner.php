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
        'pending' => 'Awaiting review',
        'published' => 'Published',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ],
    'fields' => [
        'status' => 'Status',
        'user' => 'Account',
        'is_admin' => 'Administrator',
        'make_admin' => 'Make administrator',
        'remove_admin' => 'Remove administration',
        'proposed' => 'Proposed from the app',
        'name' => 'Name',
        'country' => 'Country',
        'logo' => 'Logo',
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
        'group' => 'Partner',
        'mine' => 'My partners',
        'add' => 'Propose a partner',
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
];
