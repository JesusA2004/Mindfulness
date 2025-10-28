<?php

return [
    'paths' => ['*'], // <- para asegurar que TODA respuesta lleve CORS (incluye /api/*, errores, 404, etc.)

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8080',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => false, // usas JWT por header; deja false
];
