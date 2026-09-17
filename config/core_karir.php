<?php

return [
    'app_code' => 'karir-farmasi',
    'issuer' => env('CORE_KARIR_ISSUER'),
    'pharmacy_program_codes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORE_KARIR_PHARMACY_PROGRAM_CODES', 'S1-FARMASI,PSPPA')),
    ))),
];
