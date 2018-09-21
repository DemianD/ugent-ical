<?php

return [
    
    'ugent_cas' => [
        'username' => env('CUSTOM_UGENT_CAS_USERNAME'),
        'password' => env('CUSTOM_UGENT_CAS_PASSWORD'),
    ],

    'ugent' => [
        'group' => env('CUSTOM_UGENT_GROUP'),
    ],

    'authentication' => [
        'enabled' => env('CUSTOM_AUTH_ENABLED', false),
        'password' => env('CUSTOM_AUTH_PASSWORD'),
    ]
    
];
