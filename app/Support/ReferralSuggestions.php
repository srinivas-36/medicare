<?php

namespace App\Support;

/**
 * Maps a visit note to specialties so a referral list can lead with
 * doctors who actually treat those symptoms.
 */
class ReferralSuggestions
{
    /**
     * Keyword to the label shown to the referring doctor.
     *
     * @return array<string, array<string, string>>
     */
    public static function map(): array
    {
        return [
            'Cardiology' => [
                'chest pain' => 'Chest pain',
                'palpitations' => 'Palpitations',
                'palpitation' => 'Palpitations',
                'heart' => 'Heart symptoms',
                'cardiac' => 'Cardiac symptoms',
                'hypertension' => 'Hypertension',
                'blood pressure' => 'Blood pressure',
                'angina' => 'Angina',
                'shortness of breath' => 'Shortness of breath',
            ],
            'Neurology' => [
                'headache' => 'Headache',
                'migraine' => 'Migraine',
                'seizure' => 'Seizure',
                'numbness' => 'Numbness',
                'tingling' => 'Tingling',
                'stroke' => 'Stroke symptoms',
                'dizziness' => 'Dizziness',
                'tremor' => 'Tremor',
                'memory' => 'Memory change',
            ],
            'Endocrinology' => [
                'diabetes' => 'Diabetes',
                'blood sugar' => 'Blood sugar',
                'thyroid' => 'Thyroid',
                'hormone' => 'Hormone symptoms',
            ],
            'Orthopedics' => [
                'joint pain' => 'Joint pain',
                'knee' => 'Knee pain',
                'back pain' => 'Back pain',
                'fracture' => 'Fracture',
                'shoulder' => 'Shoulder pain',
                'arthritis' => 'Arthritis',
                'sprain' => 'Sprain',
            ],
            'Dermatology' => [
                'rash' => 'Rash',
                'skin' => 'Skin symptoms',
                'acne' => 'Acne',
                'itching' => 'Itching',
                'eczema' => 'Eczema',
                'psoriasis' => 'Psoriasis',
            ],
            'Gastroenterology' => [
                'stomach' => 'Stomach pain',
                'abdominal' => 'Abdominal pain',
                'nausea' => 'Nausea',
                'vomiting' => 'Vomiting',
                'constipation' => 'Constipation',
                'diarrhea' => 'Diarrhea',
                'diarrhoea' => 'Diarrhoea',
                'acid reflux' => 'Acid reflux',
                'liver' => 'Liver symptoms',
            ],
            'Pulmonology' => [
                'cough' => 'Cough',
                'asthma' => 'Asthma',
                'wheeze' => 'Wheeze',
                'lung' => 'Lung symptoms',
                'breathing' => 'Breathing difficulty',
            ],
            'ENT' => [
                'sinus' => 'Sinus symptoms',
                'ear pain' => 'Ear pain',
                'sore throat' => 'Sore throat',
                'hearing' => 'Hearing change',
                'tonsil' => 'Tonsil symptoms',
                'nosebleed' => 'Nosebleed',
            ],
            'Ophthalmology' => [
                'blurred vision' => 'Blurred vision',
                'eye pain' => 'Eye pain',
                'red eye' => 'Red eye',
            ],
            'Psychiatry' => [
                'anxiety' => 'Anxiety',
                'depression' => 'Depression',
                'panic' => 'Panic',
                'insomnia' => 'Insomnia',
            ],
            'Nephrology' => [
                'kidney' => 'Kidney symptoms',
                'creatinine' => 'Creatinine',
            ],
            'Gynecology' => [
                'menstrual' => 'Menstrual symptoms',
                'period pain' => 'Period pain',
                'pregnancy' => 'Pregnancy',
                'pelvic pain' => 'Pelvic pain',
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $doctors
     * @return array{doctors: list<array<string, mixed>>, symptoms: list<string>, specialties: list<string>}
     */
    public static function prepare(array $doctors, string $text): array
    {
        $analysis = self::analyze($text);

        $doctors = array_map(function (array $doctor) use ($analysis): array {
            $specialty = (string) ($doctor['specialization'] ?? '');
            $hits = $analysis['bySpecialty'][$specialty] ?? [];
            $doctor['matched'] = $hits !== [];
            $doctor['matchedOn'] = array_values($hits);

            return $doctor;
        }, $doctors);

        usort($doctors, function (array $left, array $right): int {
            $match = ((int) $right['matched']) <=> ((int) $left['matched']);

            if ($match !== 0) {
                return $match;
            }

            return strcasecmp((string) $left['name'], (string) $right['name']);
        });

        return [
            'doctors' => array_values($doctors),
            'symptoms' => $analysis['symptoms'],
            'specialties' => $analysis['specialties'],
        ];
    }

    /**
     * @return array{symptoms: list<string>, specialties: list<string>, bySpecialty: array<string, list<string>>}
     */
    public static function analyze(string $text): array
    {
        $symptoms = [];
        $specialties = [];
        $bySpecialty = [];

        foreach (self::map() as $specialty => $keywords) {
            $hits = [];

            foreach ($keywords as $keyword => $label) {
                if (! self::contains($text, $keyword)) {
                    continue;
                }

                $hits[] = $label;
                $symptoms[] = $label;
            }

            $hits = array_values(array_unique($hits));

            if ($hits === []) {
                continue;
            }

            $specialties[] = $specialty;
            $bySpecialty[$specialty] = $hits;
        }

        return [
            'symptoms' => array_values(array_unique($symptoms)),
            'specialties' => $specialties,
            'bySpecialty' => $bySpecialty,
        ];
    }

    public static function initials(string $name): string
    {
        $stripped = preg_replace('/^dr\.?\s+/i', '', trim($name)) ?? '';
        $parts = array_values(array_filter(preg_split('/\s+/', $stripped) ?: []));
        $letters = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : 'DR';
    }

    private static function contains(string $haystack, string $keyword): bool
    {
        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/iu';

        return preg_match($pattern, $haystack) === 1;
    }
}
