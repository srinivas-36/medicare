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
                ],
                [
                    'label' => 'Appointments',
                    'route' => 'doctor.appointments',
                ],
                [
                    'label' => 'Referrals',
                    'route' => 'doctor.referrals',
                ],
                [
                    'label' => 'Patients',
                    'route' => 'doctor.patients',
                ],
                [
                    'label' => 'Payment',
                    'route' => 'doctor.payment',
                ],
                [
                    'label' => 'Receptionist',
                    'route' => 'doctor.receptionist',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'doctor.profile.show',
                    'fallback' => '/doctor/profile',
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
                ],
                [
                    'label' => 'Referrals',
                    'route' => 'receptionist.referrals',
                ],
                [
                    'label' => 'Patients',
                    'route' => 'receptionist.patients',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'receptionist.profile',
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
                ],
                [
                    'label' => 'My Bookings',
                    'route' => 'patient.bookings',
                ],
                [
                    'label' => 'Profile',
                    'route' => 'patient.profile.show',
                    'fallback' => '/patient/profile',
                ],
            ],
        ],
    ],
];
