<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'home' => [
        'greeting' => 'Hello :name',
        'signedInAs' => 'Signed in as :fullName',
        'email' => 'Email: :email',
        'signOut' => 'Sign out',
        'profile' => 'My profile',
    ],
    'profile' => [
        'title' => 'Profile',
        'saved' => 'Changes saved.',
        'back' => 'Back',
    ],
    'avatar' => [
        'label' => 'Profile photo',
        'change' => 'Change photo',
        'choose' => 'Choose a photo',
        'remove' => 'Remove photo',
        'cancel' => 'Cancel',
        'uploading' => 'Uploading photo…',
    ],
];
