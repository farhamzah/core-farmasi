<?php

return [
    'role_map' => [
        'admin' => 'admin-kp',
        'koordinator_kp' => 'koordinator-kp',
        'mahasiswa' => 'mahasiswa',
        'pembimbing_dalam' => 'pembimbing-dalam',
        'pembimbing_lapangan' => 'pembimbing-lapangan',
        'penguji' => 'penguji',
    ],

    'study_program_map' => [
        'Farmasi' => 'S1 Farmasi',
        'S1 Farmasi' => 'S1 Farmasi',
        'Profesi Apoteker' => 'Profesi Apoteker',
    ],

    'department_map' => [
        'Farmasi Klinis' => 'Farmasi Klinis',
        'Fakultas Farmasi' => 'Fakultas Farmasi',
    ],

    'field_supervisor_policy' => [
        'profile_location' => 'kp',
        'core_identity_only' => true,
    ],

    'password_policy' => [
        'copy_hash' => true,
        'import_remember_token' => false,
    ],
];
