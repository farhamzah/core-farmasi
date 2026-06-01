<?php

return [
    'template_disk' => 'local',

    'upload' => [
        'disk' => 'local',
        'directory' => 'core-imports/pending',
        'max_size_kb' => 5120,
        'preview_limit' => 10,
        'accepted_extensions' => ['xlsx', 'xls', 'csv'],
    ],

    'types' => [
        'users' => [
            'label' => 'Users',
            'description' => 'Template akun user Core tanpa kolom password.',
            'template_filename' => 'core-users-template.xlsx',
            'required_columns' => ['name', 'username', 'identity_type', 'identity_number'],
            'optional_columns' => ['email', 'birth_date', 'phone', 'is_active', 'must_change_password'],
            'sample_rows' => [
                ['Nama Admin', 'ADM001', 'admin', 'ADM001', 'admin@example.test', '01/01/1990', '081234567890', 'active', 'true'],
            ],
            'notes' => [
                'Password tidak ditulis di template.',
                'Initial password mengikuti CORE_INITIAL_PASSWORD_STRATEGY; default name, opsi birth_date tetap tersedia.',
                'User hasil import nanti wajib mengganti password.',
                'Role global dan app access memakai template terpisah.',
            ],
            'is_enabled' => true,
        ],
        'students' => [
            'label' => 'Students',
            'description' => 'Template master data mahasiswa.',
            'template_filename' => 'core-students-template.xlsx',
            'required_columns' => ['nim', 'name', 'study_program_code'],
            'optional_columns' => ['email', 'phone', 'birth_date', 'gender', 'status', 'username', 'identity_number'],
            'sample_rows' => [
                ['230001', 'Nama Mahasiswa', 'S1-FAR', 'student@example.test', '081234567891', '07/08/2001', 'female', 'active', '230001', '230001'],
            ],
            'notes' => [
                'Password awal import default mengikuti nama mahasiswa; birth_date tetap data profil dan opsi strategi lama.',
                'Password tidak boleh ditulis di template.',
            ],
            'is_enabled' => true,
        ],
        'lecturers' => [
            'label' => 'Lecturers',
            'description' => 'Template master data dosen.',
            'template_filename' => 'core-lecturers-template.xlsx',
            'required_columns' => ['name'],
            'optional_columns' => ['nidn', 'nip', 'email', 'phone', 'birth_date', 'department_code', 'study_program_code', 'status', 'username', 'identity_number'],
            'sample_rows' => [
                ['Nama Dosen', '0011223344', '198801012010011001', 'lecturer@example.test', '081234567892', '09/10/1988', 'FAR', 'S1-FAR', 'active', '0011223344', '0011223344'],
            ],
            'notes' => [
                'Gunakan NIDN/NIP sesuai data yang tersedia.',
                'Password tidak boleh ditulis di template.',
            ],
            'is_enabled' => true,
        ],
        'employees' => [
            'label' => 'Employees / Tendik / Staff',
            'description' => 'Template tendik, admin, staf TU, laboran, dan pegawai non-dosen.',
            'template_filename' => 'core-employees-template.xlsx',
            'required_columns' => ['name', 'staff_type'],
            'optional_columns' => ['employee_number', 'national_id_number', 'email', 'phone', 'birth_date', 'department_code', 'study_program_code', 'position_title', 'status', 'username', 'identity_number'],
            'sample_rows' => [
                ['Nama Staff', 'tendik', 'EMP001', '3276000000000001', 'staff@example.test', '081234567893', '11/12/1991', 'FAR', 'S1-FAR', 'Laboran', 'active', 'EMP001', 'EMP001'],
            ],
            'notes' => [
                'staff_type: tendik, admin, staf_tu, laboran, other.',
                'Password tidak boleh ditulis di template.',
            ],
            'is_enabled' => true,
        ],
        'departments' => [
            'label' => 'Departments',
            'description' => 'Template fakultas/departemen/unit.',
            'template_filename' => 'core-departments-template.xlsx',
            'required_columns' => ['code', 'name'],
            'optional_columns' => ['status', 'description'],
            'sample_rows' => [
                ['FAR', 'Fakultas Farmasi', 'active', 'Fakultas Farmasi UBP'],
            ],
            'notes' => ['Code harus unik.'],
            'is_enabled' => true,
        ],
        'study_programs' => [
            'label' => 'Study Programs',
            'description' => 'Template program studi.',
            'template_filename' => 'core-study-programs-template.xlsx',
            'required_columns' => ['code', 'name', 'department_code'],
            'optional_columns' => ['level', 'status', 'head_lecturer_identity'],
            'sample_rows' => [
                ['S1-FAR', 'S1 Farmasi', 'FAR', 'S1', 'active', '0011223344'],
            ],
            'notes' => ['department_code harus mengacu ke department yang valid.'],
            'is_enabled' => true,
        ],
        'roles' => [
            'label' => 'Roles',
            'description' => 'Template role global Core.',
            'template_filename' => 'core-roles-template.xlsx',
            'required_columns' => ['name', 'slug'],
            'optional_columns' => ['description', 'is_active'],
            'sample_rows' => [
                ['Admin Core', 'admin-core', 'Administrator Core Farmasi', 'active'],
            ],
            'notes' => ['slug akan dipakai sebagai identifier role.'],
            'is_enabled' => true,
        ],
        'user_role_assignments' => [
            'label' => 'User Role Assignments',
            'description' => 'Template assignment role global ke user.',
            'template_filename' => 'core-user-role-assignments-template.xlsx',
            'required_columns' => ['username', 'role_slug'],
            'optional_columns' => ['action', 'notes'],
            'sample_rows' => [
                ['ADM001', 'admin-core', 'assign', 'Assignment role global Core'],
            ],
            'notes' => ['action: assign atau skip. App role tidak diproses di template ini.'],
            'is_enabled' => true,
        ],
        'user_app_accesses' => [
            'label' => 'User App Accesses',
            'description' => 'Template akses aplikasi internal.',
            'template_filename' => 'core-user-app-accesses-template.xlsx',
            'required_columns' => ['username', 'app_code', 'role_slug'],
            'optional_columns' => ['is_active', 'notes', 'action'],
            'sample_rows' => [
                ['ADM001', 'core-farmasi', 'admin-core', 'active', 'Akses admin Core', 'assign'],
            ],
            'notes' => ['action: assign, deactivate, atau skip. App access bukan SSO dan tidak membuat auto-login.'],
            'is_enabled' => true,
        ],
    ],
];
