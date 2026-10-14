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
            self::row('sample-in-meera', 'Meera Joshi', 'Dr Kabir Shah', 'Orthopedics', 'Knee pain after a fall. Please review before the next clinic.', 'Sent', 'Incoming'),
            self::row('sample-in-arjun', 'Arjun Patel', 'Dr Meera Kapoor', 'Dermatology', 'Rash for three weeks that has not settled with the first cream.', 'Accepted', 'Incoming'),
            self::row('sample-in-fatima', 'Fatima Khan', 'Dr Arjun Desai', 'Gastroenterology', 'Recurring upper abdominal pain. Asking whether this needs a specialist visit.', 'Appointment booked', 'Incoming'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function outgoing(): array
    {
        return [
            self::row('sample-out-ananya', 'Rahul Menon', 'Dr Ananya Iyer', 'Cardiology', 'Chest discomfort on exertion. Please assess whether this is cardiac.', 'Sent', 'Outgoing'),
            self::row('sample-out-rohan', 'Sneha Iyer', 'Dr Rohan Mehta', 'Neurology', 'New headache for five days. Please advise if imaging is needed.', 'Accepted', 'Outgoing'),
            self::row('sample-out-priya', 'Dev Sharma', 'Dr Priya Nair', 'Endocrinology', 'Blood sugar remains high on the current plan.', 'Appointment booked', 'Outgoing'),
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
            'clinic' => '',
            'urgency' => 'Routine',
            'saved' => false,
        ];
    }

    private static function key(int $userId): string
    {
        return 'mock_referrals.'.$userId;
    }
}
