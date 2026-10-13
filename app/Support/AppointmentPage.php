<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentPage
{
    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    private const DAYS = [
        'sunday' => 'Sun',
        'monday' => 'Mon',
        'tuesday' => 'Tue',
        'wednesday' => 'Wed',
        'thursday' => 'Thu',
        'friday' => 'Fri',
        'saturday' => 'Sat',
    ];

    /**
     * @return array{date: string, startingTime: string, endingTime: string, status: string, q: string}
     */
    public static function filters(Request $request): array
    {
        $start = '';
        $end = '';
        $slot = $request->string('slot')->toString();

        if (preg_match('/^(\d{2}:\d{2})\|(\d{2}:\d{2})$/', $slot, $matches)) {
            $start = $matches[1];
            $end = $matches[2];
        } else {
            $rawStart = $request->string('startingTime')->toString();
            $rawEnd = $request->string('endingTime')->toString();

            if (preg_match('/^\d{2}:\d{2}$/', $rawStart) && preg_match('/^\d{2}:\d{2}$/', $rawEnd)) {
                $start = $rawStart;
                $end = $rawEnd;
            }
        }

        $date = $request->string('date')->toString();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }

        $status = $request->string('status')->toString();

        if (! array_key_exists($status, self::STATUSES)) {
            $status = '';
        }

        return [
            'date' => $date,
            'startingTime' => $start,
            'endingTime' => $end,
            'status' => $status,
            'q' => trim($request->string('q')->toString()),
        ];
    }

    /**
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string}  $filters
     */
    public static function active(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string|int>
     */
    public static function listQuery(Request $request): array
    {
        $query = array_filter(self::filters($request), fn (string $value) => $value !== '');
        $page = (int) $request->query('page', 0);

        if ($page > 1) {
            $query['page'] = $page;
        }

        return $query;
    }

    public static function redirectCanonical(Request $request): ?RedirectResponse
    {
        $filters = self::filters($request);
        $query = array_filter($filters, fn (string $value) => $value !== '');
        $page = (int) $request->query('page', 0);

        if ($page > 1) {
            $query['page'] = $page;
        }

        $incoming = $request->query();
        $dirty = array_key_exists('slot', $incoming);

        foreach (['date', 'startingTime', 'endingTime', 'status', 'q'] as $key) {
            if (! array_key_exists($key, $incoming)) {
                continue;
            }

            if (trim((string) $incoming[$key]) !== $filters[$key]) {
                $dirty = true;
            }
        }

        if (array_key_exists('page', $incoming) && (string) $incoming['page'] !== ($page > 1 ? (string) $page : '')) {
            $dirty = true;
        }

        if (! $dirty) {
            return null;
        }

        return redirect()->route((string) $request->route()->getName(), $query);
    }

    /**
     * @param  Builder<Appointment>|Relation<Appointment, *, *>  $query
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string}  $filters
     */
    public static function apply(Builder|Relation $query, array $filters, string $search): void
    {
        if ($filters['date'] !== '') {
            $query->whereDate('appointment_date', $filters['date']);
        }

        if ($filters['startingTime'] !== '' && $filters['endingTime'] !== '') {
            $query->whereTime('appointment_time', $filters['startingTime'].':00')
                ->whereTime('ending_time', $filters['endingTime'].':00');
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] === '') {
            return;
        }

        $like = '%'.addcslashes($filters['q'], '%_\\').'%';

        if ($search === 'patient') {
            $query->whereHas('patient', fn (Builder $patient) => $patient->where('name', 'like', $like));

            return;
        }

        $query->whereHas('doctor.user', fn (Builder $user) => $user->where('name', 'like', $like));
    }

    /**
     * @param  Collection<int, TimeSlot>  $slots
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string}  $filters
     * @return list<array{value: string, label: string}>
     */
    public static function slotOptions(Collection $slots, array $filters): array
    {
        $grouped = [];

        foreach ($slots as $slot) {
            $start = self::hourMinute($slot->starting_time);
            $end = self::hourMinute($slot->ending_time);

            if ($start === '' || $end === '') {
                continue;
            }

            $key = $start.'|'.$end;
            $grouped[$key]['start'] = $start;
            $grouped[$key]['end'] = $end;
            $grouped[$key]['days'][] = self::DAYS[strtolower((string) $slot->day)] ?? ucfirst((string) $slot->day);
        }

        $options = [];

        foreach ($grouped as $key => $item) {
            $days = array_values(array_unique(array_filter($item['days'])));
            usort($days, fn (string $left, string $right) => array_search($left, array_values(self::DAYS), true) <=> array_search($right, array_values(self::DAYS), true));
            $prefix = $days === [] ? '' : implode(', ', $days).', ';

            $options[] = [
                'value' => $key,
                'label' => $prefix.self::clock($item['start']).' – '.self::clock($item['end']),
            ];
        }

        $selected = $filters['startingTime'] !== '' && $filters['endingTime'] !== ''
            ? $filters['startingTime'].'|'.$filters['endingTime']
            : '';

        if ($selected !== '' && ! collect($options)->contains(fn (array $option) => $option['value'] === $selected)) {
            array_unshift($options, [
                'value' => $selected,
                'label' => self::clock($filters['startingTime']).' – '.self::clock($filters['endingTime']),
            ]);
        }

        return $options;
    }

    public static function date(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('j M Y');
    }

    public static function weekday(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('l');
    }

    public static function timeSlot(Appointment $appointment): string
    {
        $day = blank($appointment->appointment_date) ? '' : Carbon::parse($appointment->appointment_date)->format('D');
        $start = self::clock($appointment->appointment_time);
        $end = filled($appointment->ending_time) ? self::clock($appointment->ending_time) : '';
        $range = $end !== '' ? $start.' – '.$end : $start;

        return $day !== '' ? $day.', '.$range : $range;
    }

    /**
     * @return array{value: string, label: string, class: string}
     */
    public static function status(?string $value): array
    {
        $value = (string) $value;
        $classes = [
            'pending' => 'bg-[#fff7e8] text-[#b7791f]',
            'approved' => 'bg-[#e8f8ef] text-[#0f9f6e]',
            'confirmed' => 'bg-[#e8f8ef] text-[#0f9f6e]',
            'completed' => 'bg-[#e8f2ff] text-[#0067FF]',
            'cancelled' => 'bg-red-50 text-red-600',
        ];

        return [
            'value' => $value,
            'label' => $value === '' ? '' : ucfirst($value),
            'class' => $classes[$value] ?? 'bg-[#f7faff] text-[#4E545F]',
        ];
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function payment(?string $status): array
    {
        $paid = $status === 'paid';

        return [
            'label' => $paid ? 'Paid' : 'Unpaid',
            'class' => $paid
                ? 'bg-[#e8f8ef] text-[#0f9f6e]'
                : 'bg-[#fff7e8] text-[#b7791f]',
        ];
    }

    public static function fee(mixed $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return number_format((float) $amount, 2).' USD';
    }

    /**
     * @return array<string, mixed>
     */
    public static function booking(Appointment $appointment, string $role): array
    {
        $doctorUser = $appointment->doctor?->user;
        $patient = $appointment->patient;
        $profile = $patient?->patient_profile;
        $status = self::status($appointment->status);
        $payment = self::payment($appointment->payment_status);

        if ($role === 'doctor') {
            $person = [
                'name' => $patient?->name,
                'email' => $patient?->email,
                'phone' => $patient?->phone,
                'photo' => self::photoUrl($patient?->photo),
                'initials' => self::initials((string) ($patient?->name ?? ''), 'P'),
                'age' => $profile?->age,
                'gender' => filled($patient?->gender) ? ucfirst((string) $patient->gender) : null,
                'blood_type' => filled($profile?->blood_type) ? $profile->blood_type : null,
                'specialization' => null,
            ];
            $health = [
                'bp' => data_get($profile?->health_notes, 'bp', []),
                'diabetic' => data_get($profile?->health_notes, 'diabetic', []),
                'hyperthyroidism' => data_get($profile?->health_notes, 'hyperthyroidism', []),
            ];
            $title = $patient?->name ?: 'Patient';
        } else {
            $person = [
                'name' => $doctorUser?->name,
                'email' => $doctorUser?->email,
                'phone' => $doctorUser?->phone,
                'photo' => self::photoUrl($doctorUser?->photo),
                'initials' => self::initials((string) ($doctorUser?->name ?? ''), 'D'),
                'age' => null,
                'gender' => null,
                'blood_type' => null,
                'specialization' => filled($appointment->doctor?->specialization) ? $appointment->doctor->specialization : null,
            ];
            $health = null;
            $title = $doctorUser?->name ?: 'Doctor';
        }

        return [
            'title' => $title,
            'date' => self::date($appointment->appointment_date),
            'day' => self::weekday($appointment->appointment_date),
            'slot' => self::timeSlot($appointment),
            'status' => $status,
            'payment' => $payment,
            'doctor_name' => $doctorUser?->name,
            'specialization' => filled($appointment->doctor?->specialization) ? $appointment->doctor->specialization : null,
            'patient_name' => $patient?->name,
            'ticket_price' => self::fee($appointment->ticket_price),
            'person' => $person,
            'health' => $health,
            'current_health_issues' => filled($appointment->reason) ? $appointment->reason : null,
            'reports' => self::reports($appointment, $role),
            'notes' => self::notes($appointment->consultation),
            'analysis' => self::analysis($appointment->consultation?->ai_analysis),
        ];
    }

    public static function photoUrl(?string $photo): ?string
    {
        if (! is_string($photo) || $photo === '') {
            return null;
        }

        return str_starts_with($photo, 'http') ? $photo : asset($photo);
    }

    public static function initials(string $name, string $fallback): string
    {
        $letters = collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $letters !== '' ? $letters : $fallback;
    }

    /**
     * @return list<array{name: string, files: list<array{name: string, url: string}>}>
     */
    private static function reports(Appointment $appointment, string $role): array
    {
        $route = $role === 'doctor'
            ? 'doctor.appointments.reports.show'
            : 'patient.bookings.reports.show';

        return $appointment->reports
            ->groupBy('group_name')
            ->map(function ($files, string $group) use ($appointment, $route) {
                return [
                    'name' => $group,
                    'files' => $files->map(fn ($file) => [
                        'name' => $file->original_name,
                        'url' => route($route, [$appointment, $file]),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{text: string, date: string}>
     */
    private static function notes(?Consultation $consultation): array
    {
        if (! $consultation) {
            return [];
        }

        $parts = array_values(array_filter([
            filled($consultation->clinical_notes) ? trim((string) $consultation->clinical_notes) : null,
            filled($consultation->diagnosis) ? trim((string) $consultation->diagnosis) : null,
            filled($consultation->plan) ? trim((string) $consultation->plan) : null,
        ]));

        if ($parts === []) {
            return [];
        }

        return [[
            'text' => implode("\n\n", $parts),
            'date' => $consultation->updated_at ? self::date($consultation->updated_at) : '',
        ]];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function analysis(mixed $analysis): ?array
    {
        if (! is_array($analysis)) {
            return null;
        }

        $known = false;

        foreach (['patientInfo', 'reportAnalyses', 'overallSummary', 'doctorRecommendations'] as $key) {
            if (array_key_exists($key, $analysis)) {
                $known = true;
                break;
            }
        }

        if (! $known) {
            return null;
        }

        $info = is_array($analysis['patientInfo'] ?? null) ? $analysis['patientInfo'] : [];
        $reports = [];

        foreach (is_array($analysis['reportAnalyses'] ?? null) ? $analysis['reportAnalyses'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $urls = $item['reportUrls'] ?? [];

            if (is_string($urls)) {
                $urls = [$urls];
            }

            $warnings = is_array($item['warnings'] ?? null) ? $item['warnings'] : [];

            $reports[] = [
                'groupName' => filled($item['groupName'] ?? null) ? (string) $item['groupName'] : null,
                'reportUrls' => array_values(array_filter(
                    is_array($urls) ? $urls : [],
                    fn ($url) => is_string($url) && $url !== '',
                )),
                'analysis' => filled($item['analysis'] ?? null) ? (string) $item['analysis'] : null,
                'warnings' => array_values(array_filter(array_map(
                    fn ($warning) => is_scalar($warning) ? trim((string) $warning) : '',
                    $warnings,
                ))),
            ];
        }

        $recommendations = is_array($analysis['doctorRecommendations'] ?? null)
            ? $analysis['doctorRecommendations']
            : [];

        return [
            'patientInfo' => [
                'name' => self::text($info['name'] ?? null),
                'age' => self::text($info['age'] ?? null),
                'gender' => self::text($info['gender'] ?? null),
                'bloodPressure' => self::text($info['bloodPressure'] ?? null),
                'diabetic' => self::text($info['diabetic'] ?? null),
                'hyperthyroidism' => self::text($info['hyperthyroidism'] ?? null),
            ],
            'overallSummary' => filled($analysis['overallSummary'] ?? null) ? (string) $analysis['overallSummary'] : null,
            'reportAnalyses' => $reports,
            'doctorRecommendations' => array_values(array_filter(array_map(
                fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $recommendations,
            ))),
        ];
    }

    private static function text(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return (string) $value;
    }

    private static function hourMinute(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse((string) $value)->format('H:i');
    }

    private static function clock(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse((string) $value)->format('g:i A');
    }
}
