<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Home',
        'profile' => 'Profile',
    ],
    'home' => [
        'greeting' => 'Hello :name',
    ],
    'profile' => [
        'personalInfo' => 'Personal information',
        'password' => 'Change password',
        'preferences' => 'Preferences',
        'passwordSaved' => 'Password changed.',
        'signOut' => 'Sign out',
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
