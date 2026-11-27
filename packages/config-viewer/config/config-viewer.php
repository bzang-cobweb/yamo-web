<?php

use Bza\ConfigViewer\Sources\ApplicationConfigSource;
use Bza\ConfigViewer\Sources\ModuleConfigSource;

return [

    /*
    | Sources that attribute configuration files to a group. Each class implements
    | Bza\ConfigViewer\Contracts\ConfigSource and is resolved from the container.
    | Top-level keys claimed by no source are still listed, as package configuration.
    */
    'sources' => [
        ApplicationConfigSource::class,
        ModuleConfigSource::class,
    ],

    /*
    | Values to mask. A masked value never leaves the core: adapters only receive
    | the placeholder. Only non-empty strings are masked.
    |
    | - "segments": patterns matched (case-insensitively) against the last segment
    |   of the key, e.g. "password" in "mail.mailers.smtp.password".
    | - "keys": patterns matched against the full dotted key.
    */
    'mask' => [
        'segments' => [
            '*password*',
            '*secret*',
            '*token*',
            'key',
            '*_key',
            '*-key',
            '*credentials*',
            '*dsn*',
            '*salt*',
            'private*',
        ],
        'keys' => [
            'database.connections.*.url',
            'database.redis.*.url',
            'cache.stores.*.url',
        ],
        'placeholder' => '••••••••',
    ],

    /*
    | Entries that are never listed, as patterns on the full dotted key
    | (e.g. "services.*"). A pattern without a dot hides the whole group.
    */
    'hidden' => [],

];
