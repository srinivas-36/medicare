<?php

return [
    'roles' => [
        'doctor' => [
            'label' => 'Doctor',

            'links' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'doctor.dashboard',
                    'fallback' => '/doctors/profile/me',
                    'icon' => 'grid',
                ],
                [
                    'label' => 'Appointments',
                    'route' => 'doctor.appointments',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Referrals',
                    'route' => 'doctor.referrals',
                    'icon' => 'file',
                ],
                [
                    'label' => 'Patients',
                    'route' => 'doctor.patients',
                    'icon' => 'user',
                ],
                // [
                //     'label' => 'Payment',
                //     'route' => 'doctor.payment',
                // ],
                [
                    'label' => 'Receptionist',
                    'route' => 'doctor.receptionist',
                    'icon' => 'stethoscope',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'doctor.profile.show',
                    'fallback' => '/doctor/profile',
                    'icon' => 'user',
                ],
            ],
        ],

        'receptionist' => [
            'label' => 'Receptionist',

            'links' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'receptionist.dashboard',
                    'fallback' => '/receptionists/profile/me',
                    'icon' => 'grid',
                ],
                [
                    'label' => 'Appointments',
                    'route' => 'receptionist.appointments',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Referrals',
                    'route' => 'receptionist.referrals',
                    'icon' => 'file',
                ],
                [
                    'label' => 'Patients',
                    'route' => 'receptionist.patients',
                    'icon' => 'user',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'receptionist.profile',
                    'icon' => 'user',
                ],
            ],
        ],

        'patient' => [
            'label' => 'Patient',

            'links' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'patient.dashboard',
                    'fallback' => '/users/profile/me',
                    'icon' => 'grid',
                ],
                [
                    'label' => 'Book a doctor',
                    'route' => 'patient.book',
                    'icon' => 'plus',
                ],
                [
                    'label' => 'My Bookings',
                    'route' => 'patient.bookings',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'patient.profile.show',
                    'fallback' => '/patient/profile',
                    'icon' => 'user',
                ],
            ],
        ],
    ],
];
