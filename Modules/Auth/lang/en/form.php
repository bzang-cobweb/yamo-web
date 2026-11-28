<?php

return [
    'save' => 'Save',
    'login' => [
        'title' => 'Sign in',
        'subtitle' => 'Your email or phone number: a code is sent to you. No account yet? It is created next.',
        'submit' => 'Sign in',
    ],
    'register' => [
        'title' => 'Create your account',
        'submit' => 'Create my account',
        'contact_help' => 'An email, a phone number or both: a verification code is sent to each.',
        'steps' => [
            'identity' => 'About you',
            'contact' => 'How to reach you',
            'password' => 'Password',
        ],
    ],
    'code' => [
        'label' => 'Code received',
        'title' => 'Enter the code',
        'submit' => 'Confirm',
        'send' => 'Send the code',
    ],
    'email' => [
        'add' => 'Add an email address',
        'change' => 'Change email address',
        // Vérifier l'adresse actuelle (décision 2026-11-27-verifier-email-telephone).
        'verify_link' => 'Verify my address',
        'verify_title' => 'Verify my email address',
        'verify_subtitle' => 'A code will be sent to this address.',
        'verified' => 'Email address verified.',
        'not_verified' => 'This address is not verified yet.',
    ],
    'phone' => [
        'add' => 'Add a phone number',
        'change' => 'Change phone number',
        'verify_link' => 'Resend the code',
        'verify_title' => 'Verify my number',
        'verify_subtitle' => 'A code will be sent by SMS to this number.',
        'verified' => 'Phone number verified.',
        'not_verified' => 'This number is not verified yet.',
    ],
    'password_forgot' => [
        'title' => 'Forgot password',
        'subtitle' => 'Enter the email or phone number of your account: we will send you a code.',
        'subtitle_email' => 'Enter the email of your account: we will send you a code.',
        'subtitle_phone' => 'Enter the phone number of your account: we will send you a code.',
    ],
    'password_reset' => [
        'title' => 'New password',
        'submit' => 'Change password',
    ],
    // L'étape du mot de passe, après le code (décision 2026-11-28-connexion-panneau).
    'session' => [
        'password' => [
            'title' => 'Your password',
            'submit' => 'Sign in',
        ],
        'new_password' => [
            'title' => 'Choose a password',
            'submit' => 'Continue',
        ],
        'wrong_password' => 'Incorrect password.',
        'too_many' => 'Too many attempts. Start signing in again.',
        'password_missing' => 'Choose a password first.',
    ],
    'personal' => [
        'title' => 'Personal information',
    ],
    'password' => [
        'title' => 'Change password',
        'create_title' => 'Create a password',
        'submit' => 'Change password',
        'help' => 'At least 8 characters.',
    ],
    'preferences' => [
        'title' => 'Preferences',
        'locale_help' => 'Language of the app.',
    ],
    'delete_account' => [
        'title' => 'Delete my account',
        'warning' => 'Your account will be deactivated and your devices signed out. It will be permanently deleted, with your data and photo, in :days days; until then, sign in again to recover it. Tell us why you are leaving: a code will then be sent to you to confirm.',
        'reason' => 'Why are you leaving?',
        'reason_help' => 'Your answer is read by our team and helps us improve yamo.',
        'no_destination' => 'Add an email address or a phone number to receive the confirmation code.',
        'submit' => 'Delete my account',
        'last_super_admin' => 'You are the last super administrator: appoint another one before deleting your account.',
        'purged' => ':count account(s) purged.',
    ],

    // Âge minimum (décision 2026-11-19-age-minimum).
    'birth_date' => [
        'too_young' => 'You must be at least :age years old.',
        'help' => 'At least :age years old.',
    ],
];
