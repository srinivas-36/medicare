<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Sample specialists offered when a doctor sends a referral.
 */
class MockReferralDirectory
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function doctors(): array
    {
        $doctors = [
            [
                'name' => 'Dr Ananya Iyer',
                'specialization' => 'Cardiology',
                'degree' => 'MBBS, DM Cardiology',
                'clinic' => 'City Heart Clinic',
                'experience' => '14 years in practice',
                'bio' => 'Consults for chest pain, rhythm symptoms, and blood pressure that needs a specialist opinion. Reviews the referring note before accepting the visit.',
                'rating' => 4.8,
                'reviews' => 126,
                'fee' => '₹900',
                'focus' => ['Chest pain', 'Palpitations', 'Hypertension', 'Heart failure'],
            ],
            [
                'name' => 'Dr Vikram Rao',
                'specialization' => 'Psychiatry',
                'degree' => 'MBBS, MD Psychiatry',
                'clinic' => 'Lakeside Mind Clinic',
                'experience' => '11 years in practice',
                'bio' => 'Sees referrals for anxiety, low mood, panic, and sleep problems. A short history of how long the symptoms have lasted is enough to begin.',
                'rating' => 4.7,
                'reviews' => 84,
                'fee' => '₹850',
                'focus' => ['Anxiety', 'Depression', 'Panic', 'Insomnia'],
            ],
            [
                'name' => 'Dr Rohan Mehta',
                'specialization' => 'Neurology',
                'degree' => 'MBBS, DM Neurology',
                'clinic' => 'Metro Neuro Centre',
                'experience' => '16 years in practice',
                'bio' => 'Takes referrals for headache, dizziness, numbness, and seizure concerns. Asks for the timeline and any examination already done.',
                'rating' => 4.9,
                'reviews' => 203,
                'fee' => '₹1,000',
                'focus' => ['Headache', 'Migraine', 'Dizziness', 'Numbness', 'Seizure'],
            ],
            [
                'name' => 'Dr Leela Menon',
                'specialization' => 'Ophthalmology',
                'degree' => 'MBBS, MS Ophthalmology',
                'clinic' => 'Northside Eye Clinic',
                'experience' => '12 years in practice',
                'bio' => 'Sees referrals for blurred vision, eye pain, and red eye. Note which eye is affected and whether the change was sudden.',
                'rating' => 4.8,
                'reviews' => 141,
                'fee' => '₹950',
                'focus' => ['Blurred vision', 'Eye pain', 'Red eye'],
            ],
            [
                'name' => 'Dr Priya Nair',
                'specialization' => 'Endocrinology',
                'degree' => 'MBBS, DM Endocrinology',
                'clinic' => 'Harmony Endocrine Clinic',
                'experience' => '12 years in practice',
                'bio' => 'Reviews diabetes, thyroid, and other hormone problems that need a specialist plan. Recent labs can be attached with the referral.',
                'rating' => 4.8,
                'reviews' => 151,
                'fee' => '₹800',
                'focus' => ['Diabetes', 'Thyroid', 'Hormone symptoms'],
            ],
            [
                'name' => 'Dr Kabir Shah',
                'specialization' => 'Orthopedics',
                'degree' => 'MBBS, MS Orthopedics',
                'clinic' => 'Joint & Spine Care',
                'experience' => '13 years in practice',
                'bio' => 'Sees joint, spine, and injury referrals. A short note on which joint, how long it has hurt, and what has already been tried is enough to start.',
                'rating' => 4.6,
                'reviews' => 97,
                'fee' => '₹750',
                'focus' => ['Knee pain', 'Back pain', 'Joint pain', 'Fracture', 'Arthritis'],
            ],
            [
                'name' => 'Dr Meera Kapoor',
                'specialization' => 'Dermatology',
                'degree' => 'MBBS, MD Dermatology',
                'clinic' => 'Skin & Laser Studio',
                'experience' => '9 years in practice',
                'bio' => 'Accepts referrals for rash, acne, and other skin conditions that have not settled. Mention how long the skin has been affected and any treatment already used.',
                'rating' => 4.7,
                'reviews' => 118,
                'fee' => '₹700',
                'focus' => ['Rash', 'Acne', 'Eczema', 'Itching'],
            ],
            [
                'name' => 'Dr Arjun Desai',
                'specialization' => 'Gastroenterology',
                'degree' => 'MBBS, DM Gastroenterology',
                'clinic' => 'Digestive Health Centre',
                'experience' => '15 years in practice',
                'bio' => 'Reviews abdominal pain, reflux, and bowel symptoms referred from clinic. Alarm symptoms should be marked urgent.',
                'rating' => 4.8,
                'reviews' => 142,
                'fee' => '₹950',
                'focus' => ['Abdominal pain', 'Acid reflux', 'Nausea', 'Bowel change'],
            ],
            [
                'name' => 'Dr Neha Kulkarni',
                'specialization' => 'ENT',
                'degree' => 'MBBS, MS ENT',
                'clinic' => 'Clearway ENT Clinic',
                'experience' => '10 years in practice',
                'bio' => 'Sees sinus, ear, throat, and hearing referrals. Note which side is affected and whether symptoms are acute or recurring.',
                'rating' => 4.6,
                'reviews' => 76,
                'fee' => '₹650',
                'focus' => ['Sinus symptoms', 'Ear pain', 'Sore throat', 'Hearing change'],
            ],
            [
                'name' => 'Dr Farah Qureshi',
                'specialization' => 'Pulmonology',
                'degree' => 'MBBS, DM Pulmonary Medicine',
                'clinic' => 'Breath Care Clinic',
                'experience' => '12 years in practice',
                'bio' => 'Takes cough, asthma, and breathing referrals. Mention smoking history and any inhaler or antibiotic already tried.',
                'rating' => 4.7,
                'reviews' => 89,
                'fee' => '₹800',
                'focus' => ['Cough', 'Asthma', 'Breathing difficulty', 'Wheeze'],
            ],
        ];

        return array_map(function (array $doctor): array {
            $slug = Str::after(Str::slug($doctor['name']), 'dr-');

            return [
                'id' => 'preview-'.Str::slug($doctor['name']),
                'preview' => true,
                'name' => $doctor['name'],
                'initials' => ReferralSuggestions::initials($doctor['name']),
                'specialization' => $doctor['specialization'],
                'degree' => $doctor['degree'],
                'clinic' => $doctor['clinic'],
                'experience' => $doctor['experience'],
                'bio' => $doctor['bio'],
                'about' => $doctor['about'] ?? $doctor['bio'],
                'rating' => $doctor['rating'],
                'reviews' => $doctor['reviews'],
                'fee' => $doctor['fee'],
                'photo' => AppointmentPage::photoUrl('images/referral-doctors/'.$slug.'.jpg'),
                'focus' => $doctor['focus'],
                'qualifications' => self::qualifications($doctor),
                'experiences' => self::experiences($doctor),
            ];
        }, $doctors);
    }

    /**
     * @param  array<string, mixed>  $doctor
     * @return list<array{degree: string, institution: string, period: string}>
     */
    private static function qualifications(array $doctor): array
    {
        if (isset($doctor['qualifications']) && is_array($doctor['qualifications'])) {
            return array_values($doctor['qualifications']);
        }

        return array_values(array_filter(array_map(function (string $degree): ?array {
            $degree = trim($degree);

            if ($degree === '') {
                return null;
            }

            return [
                'degree' => $degree,
                'institution' => '',
                'period' => '',
            ];
        }, explode(',', (string) ($doctor['degree'] ?? '')))));
    }

    /**
     * @param  array<string, mixed>  $doctor
     * @return list<array{role: string, place: string, period: string}>
     */
    private static function experiences(array $doctor): array
    {
        if (isset($doctor['experiences']) && is_array($doctor['experiences'])) {
            return array_values($doctor['experiences']);
        }

        if (($doctor['clinic'] ?? '') === '' && ($doctor['experience'] ?? '') === '') {
            return [];
        }

        return [[
            'role' => 'Consultant',
            'place' => (string) ($doctor['clinic'] ?? ''),
            'period' => (string) ($doctor['experience'] ?? ''),
        ]];
    }
}
