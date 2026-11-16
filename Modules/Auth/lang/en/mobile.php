<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Home',
        'profile' => 'Profile',
    ],
    'auth' => [
        'createAccount' => 'Create an account',
        'haveAccount' => 'I already have an account',
        'codeSent' => 'Code sent to :destination.',
        'resend' => 'Resend the code',
        'resent' => 'New code sent.',
    ],
    'home' => [
        'greeting' => 'Hello :name',
    ],
    'profile' => [
        'personalInfo' => 'Personal information',
        'password' => 'Change password',
        'preferences' => 'Preferences',
        'passwordSaved' => 'Password changed.',
        'addEmail' => 'Add an email address',
        'changeEmail' => 'Change email address',
        'addPhone' => 'Add a phone number',
        'changePhone' => 'Change phone number',
        'emailSaved' => 'Email address saved.',
        'phoneSaved' => 'Phone number saved.',
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
