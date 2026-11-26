<?php

// Writing to the publisher of an offer, and replying (decision 2026-11-26-messages-editeur).
return [
    'action' => 'Send a message',
    'title' => 'Send a message',
    'field' => 'Message',
    'placeholder' => 'Your message',
    'submit' => 'Send',
    'sent' => 'Message sent.',
    'own_offer' => 'You cannot write to yourself.',
    'not_started' => 'You can only reply to a member who has written to you about this offer.',
    'not_yours' => 'You cannot write in this conversation.',
    'notify' => [
        'title' => 'Message',
        'summary' => 'About “:offer”',
        'reply' => 'Reply',
        'subject' => 'New message about “:offer”',
        'intro' => ':name wrote to you about “:offer”.',
        'open_app' => 'Reply from the Notifications tab of :app.',
        'sms' => ':app: :name wrote to you about “:offer”. Reply in the app.',
    ],
];
