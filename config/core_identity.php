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
];
