<?php

return [
    'roles' => [
        'doctor' => [
            'label' => 'Doctor',
            'workspace' => 'Clinical workspace',
            'groups' => [
                [
                    'label' => 'Clinical',
                    'links' => [
                        ['label' => 'Dashboard', 'route' => 'doctor.dashboard', 'icon' => 'grid'],
                        ['label' => 'Appointments', 'route' => 'doctor.appointments', 'icon' => 'calendar'],
                        ['label' => 'Patients', 'route' => 'doctor.patients', 'icon' => 'user'],
                        ['label' => 'Referrals', 'route' => 'doctor.referrals', 'icon' => 'file'],
                        ['label' => 'Availability', 'route' => 'doctor.availability', 'icon' => 'clock'],
                        ['label' => 'Receptionists', 'route' => 'doctor.receptionists', 'icon' => 'user'],
                    ],
                ],
                [
                    'label' => 'Account',
                    'links' => [
                        [
                            'label' => 'Profile',
                            'route' => 'doctor.profile.show',
                            'icon' => 'user',
                            'active' => ['doctor.profile.show', 'doctor.profile.edit'],
                        ],
                        ['label' => 'Notifications', 'route' => 'account.notifications', 'icon' => 'bell'],
                        ['label' => 'Settings', 'route' => 'account.security', 'icon' => 'settings'],
                    ],
                ],
            ],
        ],

        'receptionist' => [
            'label' => 'Receptionist',
            'workspace' => 'Front desk',
            'groups' => [
                [
                    'label' => 'Desk',
                    'links' => [
                        ['label' => 'Dashboard', 'route' => 'receptionist.dashboard', 'icon' => 'grid'],
                        [
                            'label' => 'Appointments',
                            'route' => 'receptionist.appointments',
                            'icon' => 'calendar',
                            'exact' => true,
                            'query' => ['range' => 'all'],
                            'missing' => ['range' => ''],
                            'active' => ['receptionist.appointments', 'receptionist.appointments.show'],
                        ],
                        // [
                        //     'label' => 'Calendar',
                        //     'route' => 'receptionist.appointments',
                        //     'icon' => 'clock',
                        //     'exact' => true,
                        //     'query' => ['range' => 'upcoming'],
                        // ],
                        ['label' => 'Book Appointment', 'route' => 'receptionist.appointments.create', 'icon' => 'plus'],
                        ['label' => 'Patients', 'route' => 'receptionist.patients', 'icon' => 'user'],
                    ],
                ],
                [
                    'label' => 'Account',
                    'links' => [
                        [
                            'label' => 'Profile',
                            'route' => 'receptionist.profile',
                            'icon' => 'user',
                            'active' => ['receptionist.profile', 'receptionist.profile.edit'],
                        ],
                        ['label' => 'Notifications', 'route' => 'account.notifications', 'icon' => 'bell'],
                        ['label' => 'Settings', 'route' => 'account.security', 'icon' => 'settings'],
                    ],
                ],
            ],
        ],

        'patient' => [
            'label' => 'Patient',
            'workspace' => 'Patient workspace',
            'groups' => [
                [
                    'label' => 'Care',
                    'links' => [
                        ['label' => 'Dashboard', 'route' => 'patient.dashboard', 'icon' => 'home'],
                        [
                            'label' => 'Find a Doctor',
                            'route' => 'patient.book',
                            'icon' => 'search',
                            'active' => ['patient.book', 'patient.book.create'],
                        ],
                        ['label' => 'My Appointments', 'route' => 'patient.bookings', 'icon' => 'calendar'],
                        ['label' => 'Medical History', 'route' => 'patient.history', 'icon' => 'clipboard'],
                    ],
                ],
                [
                    'label' => 'Account',
                    'links' => [
                        [
                            'label' => 'Profile',
                            'route' => 'patient.profile.show',
                            'icon' => 'user',
                            'active' => ['patient.profile.show', 'patient.profile.edit'],
                        ],
                        ['label' => 'Notifications', 'route' => 'account.notifications', 'icon' => 'bell'],
                        ['label' => 'Settings', 'route' => 'account.security', 'icon' => 'settings'],
                    ],
                ],
            ],
        ],

        'admin' => [
            'label' => 'Administrator',
            'workspace' => 'Administration',
            'groups' => [
                [
                    'label' => 'Clinic',
                    'links' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
                        ['label' => 'Doctors', 'route' => 'admin.doctors', 'icon' => 'user'],
                        ['label' => 'Users', 'route' => 'admin.users', 'icon' => 'users'],
                        ['label' => 'Settings', 'route' => 'admin.settings', 'icon' => 'file'],
                    ],
                ],
                [
                    'label' => 'Account',
                    'links' => [
                        ['label' => 'Notifications', 'route' => 'account.notifications', 'icon' => 'bell'],
                        ['label' => 'Security', 'route' => 'account.security', 'icon' => 'shield'],
                    ],
                ],
            ],
        ],
    ],
];
