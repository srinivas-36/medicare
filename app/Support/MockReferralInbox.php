<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Sample referral rows, plus referrals the doctor sent to a sample specialist.
 * Sent rows live in the session so they show on the Referrals tab.
 */
class MockReferralInbox
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function incoming(): array
    {
        return [
            self::sample('sample-in-meera', 'Meera Joshi', 'Dr Kabir Shah', 'Orthopedics', 'Knee pain after a fall. Please review before the next clinic.', 'Sent', 'Incoming', [
                'age' => 46,
                'gender' => 'female',
                'conditions' => [],
                'question' => 'Does this knee need imaging before she is seen again?',
                'text' => 'A 46-year-old woman reports knee pain after a fall. The attached blood tests are from an earlier visit and do not reflect this injury.',
                'highlights' => ['Knee pain', 'After a fall', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Clarify how the fall happened and which knee is painful.',
                    'Examine the knee for swelling, tenderness, and range of motion.',
                    'Decide whether imaging is needed before the next clinic.',
                ],
            ]),
            self::sample('sample-in-arjun', 'Arjun Patel', 'Dr Meera Kapoor', 'Dermatology', 'Rash for three weeks that has not settled with the first cream.', 'Accepted', 'Incoming', [
                'age' => 29,
                'gender' => 'male',
                'conditions' => [],
                'question' => 'Is this rash still the same process, or has it changed?',
                'text' => 'A 29-year-old man has a rash for three weeks that has not settled with the first cream. The attached blood tests are from an earlier visit.',
                'highlights' => ['Rash', 'Three weeks', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Confirm where the rash started and how it has spread.',
                    'Review the cream already used and any reaction to it.',
                    'Examine the skin before changing treatment.',
                ],
            ]),
            self::sample('sample-in-fatima', 'Fatima Khan', 'Dr Arjun Desai', 'Gastroenterology', 'Recurring upper abdominal pain. Asking whether this needs a specialist visit.', 'Appointment booked', 'Incoming', [
                'age' => 52,
                'gender' => 'female',
                'conditions' => [],
                'question' => 'Does this pain need a specialist visit now?',
                'text' => 'A 52-year-old woman has recurring upper abdominal pain. The attached blood tests are from an earlier visit and do not describe the current episode.',
                'highlights' => ['Abdominal pain', 'Recurring', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Clarify how long each episode lasts and what brings it on.',
                    'Ask about weight loss, vomiting, or black stools.',
                    'Decide whether this needs a specialist visit now.',
                ],
            ]),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function outgoing(): array
    {
        return [
            self::sample('sample-out-ananya', 'Rahul Menon', 'Dr Ananya Iyer', 'Cardiology', 'Chest discomfort on exertion. Please assess whether this is cardiac.', 'Sent', 'Outgoing', [
                'age' => 58,
                'gender' => 'male',
                'conditions' => ['bp' => '148/92'],
                'question' => 'Is this chest discomfort cardiac?',
                'text' => 'A 58-year-old man with known high blood pressure reports chest discomfort on exertion. The attached blood tests are from an earlier visit and do not reflect this episode.',
                'highlights' => ['Chest discomfort', 'On exertion', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Clarify when the discomfort starts and how long it lasts.',
                    'Review current blood pressure treatment.',
                    'Assess whether this episode is cardiac.',
                ],
            ]),
            self::sample('sample-out-rohan', 'Sneha Iyer', 'Dr Rohan Mehta', 'Neurology', 'New headache for five days. Please advise if imaging is needed.', 'Accepted', 'Outgoing', [
                'age' => 31,
                'gender' => 'female',
                'conditions' => [],
                'question' => 'Is imaging needed for this new headache?',
                'text' => 'A 31-year-old woman reports a new headache for five days. The attached blood tests are from an earlier visit and do not explain the current headache.',
                'highlights' => ['Headache', 'Five days', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Clarify the onset and any associated symptoms.',
                    'Check for neurological warning signs.',
                    'Advise whether imaging is needed.',
                ],
            ]),
            self::sample('sample-out-priya', 'Dev Sharma', 'Dr Priya Nair', 'Endocrinology', 'Blood sugar remains high on the current plan.', 'Appointment booked', 'Outgoing', [
                'age' => 47,
                'gender' => 'male',
                'conditions' => ['diabetic' => 'Yes'],
                'question' => 'Does the current plan need to change?',
                'text' => 'A 47-year-old man with known diabetes still has high blood sugar on the current plan. The attached blood tests are from an earlier visit.',
                'highlights' => ['High blood sugar', 'Diabetes', 'Prior labs only'],
                'finding' => 'Previous blood test results were within the stated reference ranges.',
                'steps' => [
                    'Review the current medicines and how they are taken.',
                    'Record recent home glucose readings.',
                    'Decide whether the current plan needs to change.',
                ],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function remember(int $userId, array $payload): array
    {
        $record = self::row(
            'sent-'.Str::lower(Str::random(8)),
            (string) $payload['patient'],
            (string) $payload['to'],
            (string) $payload['specialization'],
            (string) $payload['reason'],
            'Sent',
            'Outgoing',
        );
        $record['appointment_id'] = (int) $payload['appointment_id'];
        $record['question'] = (string) ($payload['question'] ?? '');
        $record['clinic'] = (string) ($payload['clinic'] ?? '');
        $record['urgency'] = (string) ($payload['urgency'] ?? 'Routine');
        $record['saved'] = true;
        $record['patient_details'] = is_array($payload['patient_details'] ?? null) ? $payload['patient_details'] : [];
        $record['summary'] = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $record['reports'] = is_array($payload['reports'] ?? null) ? $payload['reports'] : [];
        $record['report_ids'] = array_values(array_map('intval', (array) ($payload['report_ids'] ?? [])));

        $key = self::key($userId);
        $all = session($key, []);
        array_unshift($all, $record);
        session([$key => array_values($all)]);

        return $record;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sent(int $userId): array
    {
        return array_values(session(self::key($userId), []));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forAppointment(int $userId, int $appointmentId): array
    {
        return array_values(array_filter(
            self::sent($userId),
            fn (array $row) => (int) ($row['appointment_id'] ?? 0) === $appointmentId,
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $userId, string $id): ?array
    {
        foreach ([...self::incoming(), ...self::outgoing(), ...self::sent($userId)] as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(
        string $id,
        string $patient,
        string $doctor,
        string $specialization,
        string $reason,
        string $status,
        string $direction,
    ): array {
        $sentAt = now()->subHours($direction === 'Incoming' ? 5 : 2);

        return [
            'id' => $id,
            'patient' => $patient,
            'name' => $doctor,
            'from' => $doctor,
            'specialization' => $specialization,
            'reason' => $reason,
            'sent' => AppointmentPresenter::date($sentAt).' · '.AppointmentPresenter::time($sentAt),
            'status' => $status,
            'preview' => true,
            'direction' => $direction,
            'question' => '',
            'clinic' => (string) (collect(MockReferralDirectory::doctors())->firstWhere('name', $doctor)['clinic'] ?? ''),
            'urgency' => 'Routine',
            'saved' => false,
        ];
    }

    /**
     * A sample row with the same frozen patient details and summary a sent referral keeps.
     *
     * @param  array{
     *     age: int,
     *     gender: string,
     *     conditions?: array<string, string>,
     *     question?: string,
     *     text: string,
     *     highlights: list<string>,
     *     finding: string,
     *     steps: list<string>
     * }  $case
     * @return array<string, mixed>
     */
    private static function sample(
        string $id,
        string $patient,
        string $doctor,
        string $specialization,
        string $reason,
        string $status,
        string $direction,
        array $case,
    ): array {
        $record = self::row($id, $patient, $doctor, $specialization, $reason, $status, $direction);
        $notes = [
            'bp' => ['known' => false, 'value' => ''],
            'diabetic' => ['known' => false, 'value' => ''],
            'hyperthyroidism' => ['known' => false, 'value' => ''],
        ];

        foreach ($case['conditions'] ?? [] as $key => $value) {
            if (array_key_exists($key, $notes)) {
                $notes[$key] = ['known' => true, 'value' => $value];
            }
        }

        $record['question'] = (string) ($case['question'] ?? '');
        $record['patient_details'] = [
            'age' => $case['age'],
            'gender' => $case['gender'],
            'health_notes' => $notes,
        ];
        $record['summary'] = [
            'text' => $case['text'],
            'source' => 'approved',
            'highlights' => $case['highlights'],
            'findings' => [[
                'title' => 'Earlier blood tests',
                'explanation' => $case['finding'],
                'value' => '',
                'range' => '',
            ]],
            'steps' => $case['steps'],
            'limitations' => 'Current lab results for this episode are not available.',
            'report_notes' => [self::bloodReading()],
            'referral_brief' => null,
        ];
        $record['reports'] = [self::bloodReport()];

        return $record;
    }

    /**
     * @return array{report_id: int, label: string, narrative: string, rows: list<array{parameter: string, value: string, range: string, flag: string}>}
     */
    private static function bloodReading(): array
    {
        return [
            'report_id' => 1,
            'label' => 'Blood Test Reports',
            'narrative' => 'The attached blood test report shows all tested parameters within normal reference ranges.',
            'rows' => [
                ['parameter' => 'Hemoglobin (Hb)', 'value' => '15.2 g/dL', 'range' => '13.0 – 17.0', 'flag' => 'normal'],
                ['parameter' => 'Total RBC Count', 'value' => '5.18 million/cumm', 'range' => '4.5 – 5.9', 'flag' => 'normal'],
                ['parameter' => 'Hematocrit (HCT)', 'value' => '46.1 %', 'range' => '40 – 52', 'flag' => 'normal'],
                ['parameter' => 'MCV', 'value' => '89.7 fL', 'range' => '80 – 100', 'flag' => 'normal'],
                ['parameter' => 'MCH', 'value' => '30.2 pg', 'range' => '27 – 33', 'flag' => 'normal'],
                ['parameter' => 'MCHC', 'value' => '33.8 g/dL', 'range' => '31.5 – 36.0', 'flag' => 'normal'],
                ['parameter' => 'RDW-CV', 'value' => '12.9 %', 'range' => '11.5 – 14.5', 'flag' => 'normal'],
                ['parameter' => 'Total Leukocyte Count (WBC)', 'value' => '7,400 /cumm', 'range' => '4,000 – 11,000', 'flag' => 'normal'],
            ],
        ];
    }

    /**
     * @return array{id: int, title: string, group: string, kind: string, date: string}
     */
    private static function bloodReport(): array
    {
        return [
            'id' => 1,
            'title' => 'Blood Test Reports',
            'group' => 'Blood Test Reports',
            'kind' => 'Image',
            'date' => now()->format('j M Y'),
        ];
    }

    private static function key(int $userId): string
    {
        return 'mock_referrals.'.$userId;
    }
}
