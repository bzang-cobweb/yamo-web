<?php

// Account addresses (`UserAddress`): recipient, app form and list
// (decision 2026-11-18-useraddress-dans-auth). Labels of the address itself
// (street, zip, city) stay in `location::address`.

return [
    'recipient' => 'Recipient',
    'indication' => 'Directions to the address',
    'indication_placeholder' => 'E.g. behind the market, blue gate',
    'detach' => 'Remove',
    'form' => [
        'create_title' => 'Add an address',
        'edit_title' => 'Edit address',
        'save' => 'Save',
        'saved' => 'Address saved.',
    ],
    'list' => [
        'title' => 'My addresses',
        'empty' => 'No address yet.',
        'search' => 'Search an address…',
        'create' => 'Add an address',
        'confirm' => 'Remove this address?',
        'detached' => 'Address removed.',
    ],
];
