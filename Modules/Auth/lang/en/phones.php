<?php

// "My phone numbers": the main number and the additional numbers (decision 2026-11-27-plusieurs-numeros).
return [
    'title' => 'Additional numbers',
    'list' => [
        'title' => 'My phone numbers',
        'create' => 'Add a number',
        'primary' => 'Main',
        'no_primary' => 'No main number',
        'add_primary' => 'Add',
        'change' => 'Change',
        'verify' => 'Verify',
        'not_verified' => 'not verified',
        'make_primary' => 'Make main',
        'make_primary_confirm' => 'Use this number to sign in and receive your codes? Your current main number will stay in your numbers.',
        'made_primary' => 'Main number changed.',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove this number?',
        'removed' => 'Number removed.',
    ],
    'form' => [
        'title' => 'Add a number',
        'phone' => 'Number',
        'help' => 'A code will be sent to this number by text message.',
        'saved' => 'Number added.',
    ],
    'errors' => [
        'primary' => 'This is already your main number.',
        'duplicate' => 'This number is already in your numbers.',
    ],
    'verified_at' => 'Verified on',
    'added_at' => 'Added on',
];
