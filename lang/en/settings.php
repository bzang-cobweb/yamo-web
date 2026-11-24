<?php

return [
    'title' => 'Application',
    'navigation_group' => 'Settings',
    'sections' => [
        'home' => 'App home',
        'general' => 'Presentation',
        'payment' => 'Payment',
        'users' => 'Users',
        'partners' => 'Partners',
    ],
    'description' => 'Description',
    'payment_required' => 'Apply payment for owners',
    'partner_notify_user' => 'Account notified of partner requests',
    'partner_payment_required' => 'Apply payment for partners',
    'minimum_age' => 'Minimum age',
    'years' => 'years',
    'home_offers_per_city' => 'Offers per city',
    'home_partners' => 'Partners shown',
    'help' => [
        'partner_notify_user' => 'This back-office account is notified each time a partner is added or edited from the app. Empty: nobody.',
        'partner_payment_required' => 'When checked, a partner is no longer visible in the app after its « Expires on » date; without a date, it is not visible. Unchecked, the date is ignored.',
        'home_count' => 'Drawn at random each time the home is shown. Empty: 10.',
        'description' => 'Introduction to the application on the home page, one per language.',
        'payment_required' => 'When checked, an offer is no longer visible to others after its « Paid until » date; without a date, it is not visible. Its author always sees it.',
        'minimum_age' => 'When set, the date of birth becomes required at sign-up and in personal information, and must give at least this age. Empty: no limit.',
    ],
];
