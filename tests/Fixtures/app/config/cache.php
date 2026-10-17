<?php

declare(strict_types=1);

/**
 * Configuration cache de l'application factice (tests/Fixtures/app).
 *
 * Chargée par CacheServiceProvider::boot() puis recopiée dans la clé "cache"
 * de la configuration. Les tests surchargent ces valeurs à la volée.
 */
return [
    'default' => 'array',

    'prefix'  => 'tonka_tests_',

    'stores'  => [
        'array'    => ['driver' => 'array'],

        // Pas de "path" : retombe sur storage_path('framework/cache/data')
        'file'     => ['driver' => 'file'],

        'database' => [
            'driver'   => 'database',
            'table'    => 'cache',
            'lifetime' => 0,
            'lock'     => true,
        ],

        'chain'    => [
            'driver'         => 'chain',
            'stores'         => ['array', 'file'],
            'default_lifetime' => 0,
        ],
    ],
];
