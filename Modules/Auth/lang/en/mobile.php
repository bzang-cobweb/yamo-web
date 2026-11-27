<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Home',
        // L'onglet de recherche des offres (décision 2026-11-23-onglet-recherche).
        'search' => 'Search',
        'profile' => 'Profile',
    ],
    'auth' => [
        'createAccount' => 'Create an account',
        'codeSent' => 'Code sent to :destination.',
        'resend' => 'Resend the code',
        'resent' => 'New code sent.',
        'accountDeleted' => 'Your account has been deleted.',
        'restoreTitle' => 'Recover my account',
        'restoreMessage' => 'Your account will be permanently deleted on :date. Do you want to recover it?',
        'restoreConfirm' => 'Recover my account',
        'restoreCancel' => 'Cancel',
        'accountRestored' => 'Your account has been recovered.',
        'previousStep' => 'Previous step',
        'signIn' => 'Sign in',
        'signInOrUp' => 'Sign in or sign up',
        'registerIntro' => 'No account with this identifier yet: create one in a few words.',
        'guest' => [
            'notifications' => 'Sign in to follow your requests, bookings and messages.',
            'profile' => 'Sign in to manage your profile, favourites and listings.',
        ],
    ],
    'profile' => [
        'personalInfo' => 'Personal information',
        'details' => 'My details',
        'privacy' => 'Privacy',
        'identity' => 'Verify my identity',
        'password' => 'Change password',
        'preferences' => 'Preferences',
        'addresses' => 'My addresses',
        'phones' => 'My phone numbers',
        'notifications' => 'Notifications',
        'residence' => 'Residential address',
        'emergencyContact' => 'Emergency contact',
        'passwordSaved' => 'Password changed.',
        'addEmail' => 'Add an email address',
        'changeEmail' => 'Change email address',
        'addPhone' => 'Add a phone number',
        'changePhone' => 'Change phone number',
        'emailSaved' => 'Email address saved.',
        'phoneSaved' => 'Phone number saved.',
        'profileVisibility' => 'Profile visibility',
        'reviewPrivacy' => 'Reviews',
        'personalData' => 'My data',
        'deleteAccount' => 'Delete my account',
        'signOut' => 'Sign out',
        'title' => 'Profile',
        'saved' => 'Changes saved.',
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
