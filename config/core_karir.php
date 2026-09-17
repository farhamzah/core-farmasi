<?php

return [
    'app_code' => 'karir-farmasi',
    'issuer' => env('CORE_KARIR_ISSUER'),
    'client_abilities' => [
        'verify:karir-identity',
        'create:karir-alumni-registration',
        'read:karir-alumni-registrations',
        'read:karir-alumni-registration-status',
        'approve:karir-alumni-registration',
        'reject:karir-alumni-registration',
        'read:karir-person',
        'read:karir-study-program',
    ],
    'pharmacy_program_codes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORE_KARIR_PHARMACY_PROGRAM_CODES', 'S1-FARMASI,PSPPA')),
    ))),
];
