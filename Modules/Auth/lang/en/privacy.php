<?php

// Confidentialité du profil (décision 2026-11-27-confidentialite).
return [
    'visibility' => [
        'title' => 'Profile visibility',
        'subtitle' => 'Choose who can see your profile.',
        'label' => 'Who can see my profile',
        'help' => 'My connections: members you share a request with. Owners: members who offer at least one listing.',
        'options' => [
            'anyone' => 'Anyone on yamo',
            'owners' => 'Owners',
            'connections' => 'My connections',
            'owners_and_connections' => 'Owners and my connections',
        ],
    ],
    'reviews' => [
        'title' => 'Reviews',
        'label' => 'Show my country, region and city',
        'help' => 'Under your reviews, next to your first name.',
    ],
    'data' => [
        'title' => 'My data',
        'subtitle' => 'We prepare a file of your data (JSON) and send it to your verified email address.',
        'submit' => 'Request my data',
        'sent' => 'Your data has been sent by email.',
        'no_verified_email' => 'Verify your email address first: your data is only sent to a verified address.',
        'mail' => [
            'subject' => 'Your yamo data',
            'intro' => 'Attached are the data of your yamo account, in JSON format.',
            'ignore' => 'If you did not make this request, change your email address from your profile and contact us.',
        ],
    ],
];
