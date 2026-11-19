<?php

return [
    'save' => 'Save',
    'login' => [
        'title' => 'Sign in',
        'subtitle' => 'Access your yamo account.',
        'submit' => 'Sign in',
    ],
    'register' => [
        'title' => 'Create an account',
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
    ],
    'phone' => [
        'add' => 'Add a phone number',
        'change' => 'Change phone number',
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
    'personal' => [
        'title' => 'Personal information',
    ],
    'password' => [
        'title' => 'Change password',
        'submit' => 'Change password',
        'help' => 'At least 8 characters.',
    ],
    'preferences' => [
        'title' => 'Preferences',
        'locale_help' => 'Language of the app.',
    ],
    'delete_account' => [
        'title' => 'Delete my account',
        'warning' => 'Your account will be deactivated and your devices signed out. It will be permanently deleted, with your data and photo, in :days days; until then, contact us to recover it. Confirm with your password.',
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
