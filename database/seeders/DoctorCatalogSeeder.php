<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorCatalogSeeder extends Seeder
{
    /**
     * Password for every seeded doctor account.
     */
    private const PASSWORD = '12345678';

    public function run(): void
    {
        foreach ($this->doctors() as $record) {
            DB::transaction(function () use ($record) {
                $user = User::query()->updateOrCreate(
                    ['email' => $record['email']],
                    [
                        'name' => $record['name'],
                        'password' => self::PASSWORD,
                        'phone' => $record['phone'],
                        'photo' => $record['photo'],
                        'role' => 'doctor',
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ],
                );

                if (! is_string($user->slug) || $user->slug === '') {
                    $user->forceFill([
                        'slug' => User::uniqueSlug($record['name']),
                    ])->save();
                }

                $doctor = Doctor::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'specialization' => $record['specialization'],
                        'ticket_price' => $record['ticket_price'],
                        'bio' => $record['bio'],
                        'about' => $record['about'],
                        'approval_status' => 'approved',
                        'average_rating' => $record['average_rating'],
                        'total_rating' => $record['total_rating'],
                    ],
                );

                $doctor->qualifications()->delete();
                $doctor->experiences()->delete();
                $doctor->timeSlots()->delete();

                foreach ($record['qualifications'] as $row) {
                    $doctor->qualifications()->create($row);
                }

                foreach ($record['experiences'] as $row) {
                    $doctor->experiences()->create($row);
                }

                foreach ($record['time_slots'] as $row) {
                    $doctor->timeSlots()->create([
                        'day' => $row['day'],
                        'starting_time' => AppointmentBooking::normalizeTime($row['starting_time']),
                        'ending_time' => AppointmentBooking::normalizeTime($row['ending_time']),
                        'is_available' => $row['is_available'],
                    ]);
                }
            });
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function doctors(): array
    {
        return [
            [
                'name' => 'Dr. Reddi Prasad Yadavali, FRCR',
                'email' => 'seenu2003@gmail.com',
                'phone' => '7842527879',
                'photo' => 'https://res.cloudinary.com/srinivascloud/image/upload/v1753446344/dtpkschn1enoaiqalgag.webp',
                'specialization' => 'Interventional Radiology',
                'ticket_price' => 100,
                'average_rating' => 4.67,
                'total_rating' => 3,
                'bio' => 'Dr. Reddi Prasad Yadavali is Chairman, Head of Department & Consultant in Interventional Radiology at Manipal Hospital, Old Airport Road, Bangalore, with over 15 years of experience.',
                'about' => 'Dr. Yadavali holds MBBS and international fellowships including MRCS (Edinburgh), FRCR (London), CCT (London) and EBIR (Vienna); he specialises in minimally invasive image-guided procedures such as TACE, SIRT, microwave ablation, uterine/fibroid embolisation, hepato-biliary drainage, TIPSS/BRTO, dialysis access, urological interventions, angioplasty/stenting, endovascular aneurysm repair and emergency embolisation. He received a gold medal in general surgery and topped his MBBS exams, and also serves as Adjunct Professor at Manipal University.',
                'qualifications' => [
                    [
                        'degree' => 'MRCS (Edinburgh)',
                        'institution' => 'Royal College of Surgeons, Edinburgh',
                        'start_date' => '1996-06-07',
                        'end_date' => '2000-05-17',
                    ],
                    [
                        'degree' => 'FRCR (London)',
                        'institution' => 'Royal College of Radiologists, London',
                        'start_date' => '2004-06-18',
                        'end_date' => '2007-08-16',
                    ],
                ],
                'experiences' => [
                    [
                        'designation' => 'Chairman, HOD & Consultant – Interventional Radiology',
                        'hospital_name' => 'Manipal Hospital, HAL Airport Road, Bangalore',
                        'start_date' => '2017-06-17',
                        'end_date' => '2024-09-22',
                        'is_current' => false,
                    ],
                ],
                'time_slots' => [
                    ['day' => 'monday', 'starting_time' => '10:00', 'ending_time' => '11:30', 'is_available' => true],
                    ['day' => 'monday', 'starting_time' => '12:00', 'ending_time' => '13:00', 'is_available' => true],
                    ['day' => 'monday', 'starting_time' => '17:00', 'ending_time' => '18:30', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '10:00', 'ending_time' => '11:30', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '12:06', 'ending_time' => '13:30', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '15:00', 'ending_time' => '16:30', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '17:20', 'ending_time' => '18:40', 'is_available' => true],
                ],
            ],
            [
                'name' => 'Dr. Karthik Damodharan, FRCR',
                'email' => 'seenu456@gmail.com',
                'phone' => '8247770490',
                'photo' => 'https://res.cloudinary.com/srinivascloud/image/upload/v1753444320/qxcdhwzbfddw4ll5cii6.webp',
                'specialization' => 'Vascular & Interventional Radiology (VIR)',
                'ticket_price' => 150,
                'average_rating' => 5,
                'total_rating' => 7,
                'bio' => 'Dr. Karthik Damodharan is a senior Vascular & Interventional Radiologist with over 24 years of experience across India, UK, USA and Singapore. He currently serves as the Director of Vascular & Interventional Radiology at MIOT International in Chennai.',
                'about' => 'Dr. Karthik has over 24 years of experience in Vascular & Interventional Radiology, including MBBS from Madras Medical College (2000), MRCP in 2003, FRCR in 2007, fellowship years in the UK and at Cleveland Clinic USA, followed by senior roles in Singapore before returning to India in 2020 to head interventional radiology at MIOT International, Chennai.',
                'qualifications' => [
                    [
                        'degree' => 'MBBS',
                        'institution' => 'Madras Medical College, Chennai',
                        'start_date' => '1996-06-05',
                        'end_date' => '2000-11-25',
                    ],
                    [
                        'degree' => 'MRCP (UK)',
                        'institution' => 'Royal College of Physicians, Edinburgh',
                        'start_date' => '2003-01-25',
                        'end_date' => '2003-07-24',
                    ],
                    [
                        'degree' => 'FAMS',
                        'institution' => 'European Board of Interventional Radiology',
                        'start_date' => '2017-02-22',
                        'end_date' => '2017-10-19',
                    ],
                ],
                'experiences' => [
                    [
                        'designation' => 'Director – Vascular & Interventional Radiology',
                        'hospital_name' => 'MIOT International Hospital, Chennai',
                        'start_date' => '2020-07-17',
                        'end_date' => '2023-11-23',
                        'is_current' => false,
                    ],
                    [
                        'designation' => 'Senior Consultant',
                        'hospital_name' => 'Singapore General Hospital',
                        'start_date' => '2018-07-04',
                        'end_date' => '2020-05-06',
                        'is_current' => false,
                    ],
                ],
                'time_slots' => [
                    ['day' => 'monday', 'starting_time' => '10:30', 'ending_time' => '11:40', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '10:30', 'ending_time' => '11:40', 'is_available' => true],
                    ['day' => 'wednesday', 'starting_time' => '10:30', 'ending_time' => '11:40', 'is_available' => true],
                    ['day' => 'thursday', 'starting_time' => '10:30', 'ending_time' => '11:40', 'is_available' => true],
                    ['day' => 'monday', 'starting_time' => '12:40', 'ending_time' => '13:30', 'is_available' => true],
                    ['day' => 'tuesday', 'starting_time' => '12:40', 'ending_time' => '13:30', 'is_available' => true],
                    ['day' => 'wednesday', 'starting_time' => '12:40', 'ending_time' => '13:30', 'is_available' => true],
                    ['day' => 'thursday', 'starting_time' => '12:40', 'ending_time' => '13:30', 'is_available' => true],
                ],
            ],
            [
                'name' => 'Dr. Ram Kishore Gurajala, MD',
                'email' => 'ramkishore@gmail.com',
                'phone' => '12164445600',
                'photo' => 'https://res.cloudinary.com/srinivascloud/image/upload/v1753443286/yx4gexasyrpkqq5wggem.webp',
                'specialization' => 'Interventional Radiology',
                'ticket_price' => 100,
                'average_rating' => 5,
                'total_rating' => 1,
                'bio' => 'Dr. Gurajala is a board-certified Interventional Radiologist and Diagnostic Radiologist, affiliated with Cleveland Clinic in Ohio.',
                'about' => "Dr. Ram Kishore Gurajala, MD, is a distinguished Interventional Radiologist.\n\nDr. Gurajala embarked on his medical journey at Gandhi Medical College in Hyderabad, India, where he laid the foundation for his exceptional medical education. Subsequently, he pursued his residency training in Diagnostic and Interventional Radiology at the esteemed Mersey School of Radiology in Liverpool, UK. This phase of his training not only honed his diagnostic skills but also provided him with a solid background in interventional techniques.\n\nRecognizing the importance of advanced specialization, Dr. Gurajala completed a Fellowship in Interventional Radiology at the renowned Cleveland Clinic. This experience further elevated his expertise and allowed him to contribute significantly to the field.\n\nIn addition to his clinical pursuits, Dr. Gurajala is also the visionary behind IR4U.info, a comprehensive educational website. Through this platform, he strives to disseminate knowledge about Interventional Radiology to a broader audience, including medical students, fellow healthcare professionals, and the general public.\n\nDr. Ram Kishore Gurajala's dedication to his patients, coupled with his commitment to advancing the field of Interventional Radiology, has positioned him as a respected figure in the medical community. His journey from Gandhi Medical College to his current role at Cleveland Clinic exemplifies his relentless pursuit of excellence and innovation in the realm of radiology.",
                'qualifications' => [
                    [
                        'degree' => 'MBBS',
                        'institution' => 'Gandhi Medical College, Hyderabad, India',
                        'start_date' => '1995-06-06',
                        'end_date' => '1999-10-14',
                    ],
                    [
                        'degree' => 'Fellowship in Vascular & Interventional Radiology',
                        'institution' => 'Cleveland Clinic Foundation',
                        'start_date' => '2013-06-13',
                        'end_date' => '2014-11-25',
                    ],
                ],
                'experiences' => [
                    [
                        'designation' => 'Interventional Radiologist',
                        'hospital_name' => 'Cleveland Clinic, Ohio',
                        'start_date' => '2013-07-15',
                        'end_date' => '2025-07-24',
                        'is_current' => false,
                    ],
                ],
                'time_slots' => [
                    ['day' => 'sunday', 'starting_time' => '10:00', 'ending_time' => '11:30', 'is_available' => true],
                    ['day' => 'sunday', 'starting_time' => '12:00', 'ending_time' => '13:30', 'is_available' => true],
                    ['day' => 'monday', 'starting_time' => '08:00', 'ending_time' => '09:30', 'is_available' => true],
                    ['day' => 'monday', 'starting_time' => '10:00', 'ending_time' => '12:30', 'is_available' => true],
                    ['day' => 'wednesday', 'starting_time' => '10:00', 'ending_time' => '11:30', 'is_available' => true],
                ],
            ],
        ];
    }
}
