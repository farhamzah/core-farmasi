<?php

return [
    'identity_types' => [
        'student' => 'Student',
        'lecturer' => 'Lecturer',
        'employee' => 'Employee',
        'admin' => 'Admin',
        'external' => 'External',
        'system' => 'System',
    ],

    'initial_password_format' => 'd/m/Y',

    'initial_password_strategy' => env('CORE_INITIAL_PASSWORD_STRATEGY', 'name'),

    'auto_user' => [
        'enabled' => env('CORE_AUTO_CREATE_USER_FROM_PROFILE', true),
        'password_policy' => 'first_name_identifier_suffix',
    ],
];
