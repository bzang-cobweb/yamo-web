<?php

// Account identification (decision 2026-11-25-identification).
return [
    'title' => 'Verify my identity',
    'documents' => 'Identity document',
    'documents_help' => 'Clear photos of your identity card, passport or driving licence (front and back), up to :max. Only the team sees them.',
    'add' => 'Add photos',
    'submit' => 'Send',
    'sent' => 'Thank you! The team will check your identity document.',
    'not_shared' => "We won't share this information.",
    'country' => 'Issuing country',
    'document_type' => 'Document type',
    'document_types' => [
        'driving_licence' => 'Driving licence',
        'passport' => 'Passport',
        'identity_card' => 'Identity card',
    ],
    'notice' => [
        'missing' => 'Send an identity document: once the team has checked it, your profile will show as verified.',
        'pending' => 'The team is checking your identity document.',
        'identified' => 'Your account has been verified since :date. The validated photos stay; you can add more.',
    ],
    'admin' => [
        'section' => 'Identification',
        'identified_at' => 'Identification date',
        'country' => 'Issuing country',
        'document_type' => 'Document type',
        'documents' => 'Identity documents',
        'no_documents' => 'No document sent.',
        'validate' => 'Validate identity',
        'validated' => 'Identity validated.',
        'revoke' => 'Remove identification',
        'revoked' => 'Identification removed.',
        'identified' => 'Verified',
    ],
];
