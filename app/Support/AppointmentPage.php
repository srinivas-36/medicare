<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\AppointmentMessage;
use App\Models\ClinicSetting;
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
     * @return array{date: string, startingTime: string, endingTime: string, status: string, q: string, tab: string}
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

        $tab = $request->string('tab')->toString();

        if (! in_array($tab, ['upcoming', 'completed', 'cancelled'], true)) {
            $tab = '';
        }

        return [
            'date' => $date,
            'startingTime' => $start,
            'endingTime' => $end,
            'status' => $status,
            'q' => trim($request->string('q')->toString()),
            'tab' => $tab,
        ];
    }

    /**
     * Query string for the doctor status tabs. Keeps the selected day and drops pagination.
     *
     * @param  array{date?: string, startingTime?: string, endingTime?: string, status?: string, q?: string, tab?: string}  $filters
     * @return array<string, string>
     */
    public static function tabQuery(array $filters, ?string $tab = null): array
    {
        $query = [];

        if (in_array($tab, ['upcoming', 'completed', 'cancelled'], true)) {
            $query['tab'] = $tab;
        }

        if (($filters['date'] ?? '') !== '') {
            $query['date'] = $filters['date'];
        }

        return $query;
    }

    /**
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string, tab: string}  $filters
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

        foreach (['date', 'startingTime', 'endingTime', 'status', 'q', 'tab'] as $key) {
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
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string, tab: string}  $filters
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

        if ($filters['tab'] === 'upcoming') {
            $query->upcoming()->whereNotIn('status', ['cancelled', 'completed', 'no_show']);
        } elseif ($filters['tab'] === 'completed') {
            $query->where('status', 'completed');
        } elseif ($filters['tab'] === 'cancelled') {
            $query->where('status', 'cancelled');
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
     * @param  array{date: string, startingTime: string, endingTime: string, status: string, q: string, tab: string}  $filters
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
            'pending' => 'bg-[#E8F2FF] text-[#1D4ED8]',
            'approved' => 'bg-[#DCFCE7] text-[#15803D]',
            'confirmed' => 'bg-[#DCFCE7] text-[#15803D]',
            'checked_in' => 'bg-[#E8F2FF] text-[#1D4ED8]',
            'completed' => 'bg-[#F1F5F9] text-[#475569]',
            'cancelled' => 'bg-[#FEE2E2] text-[#B91C1C]',
            'no_show' => 'bg-[#FEE2E2] text-[#B91C1C]',
        ];
        $labels = [
            'checked_in' => 'Checked in',
            'no_show' => 'No-show',
        ];

        return [
            'value' => $value,
            'label' => $value === '' ? '' : ($labels[$value] ?? ucfirst($value)),
            'class' => $classes[$value] ?? 'bg-[#f7faff] text-[#4E545F]',
        ];
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function payment(?string $status): array
    {
        if ($status === 'paid') {
            return [
                'label' => 'Paid',
                'class' => 'bg-[#e8f8ef] text-[#0f9f6e]',
            ];
        }

        if ($status === 'unpaid') {
            return [
                'label' => 'Unpaid',
                'class' => 'bg-[#fff7e8] text-[#b7791f]',
            ];
        }

        if ($status === 'failed') {
            return [
                'label' => 'Payment failed',
                'class' => 'bg-red-50 text-red-600',
            ];
        }

        $policy = null;

        try {
            $policy = ClinicSetting::query()->value('payment_policy');
        } catch (\Throwable) {
            $policy = null;
        }

        if ($policy === 'clinic') {
            return [
                'label' => 'Payment handled at clinic',
                'class' => 'bg-[#f7faff] text-[#4E545F]',
            ];
        }

        return [
            'label' => null,
            'class' => '',
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
                'origin' => ($patient && $appointment->doctor)
                    ? PatientRoster::labelFor($appointment->doctor, $patient, $appointment->doctor->user)
                    : null,
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
            'notes' => self::notes($appointment->consultation, $role),
            'analysis' => self::presentAnalysis($appointment->consultation, $role, $appointment),
            'messages' => self::messages($appointment),
            'patient_record' => [
                'name' => $patient?->name,
                'age' => $profile?->age,
                'gender' => filled($patient?->gender) ? ucfirst((string) $patient->gender) : null,
                'blood_type' => filled($profile?->blood_type) ? $profile->blood_type : null,
                'email' => $role === 'doctor' ? $patient?->email : null,
                'phone' => $role === 'doctor' ? $patient?->phone : null,
            ],
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
     * @return list<array{name: string, files: list<array{id: int, name: string, url: string, size: string, uploaded: string}>}>
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
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'url' => route($route, [$appointment, $file]),
                        'size' => self::bytes(MedicalFile::size((string) $file->path)),
                        'uploaded' => $file->created_at ? $file->created_at->format('d M Y') : '',
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private static function bytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    }

    /**
     * @return list<array{text: string, date: string}>
     */
    private static function notes(?Consultation $consultation, string $role): array
    {
        if (! $consultation) {
            return [];
        }

        $parts = array_values(array_filter([
            filled($consultation->clinical_notes) ? trim((string) $consultation->clinical_notes) : null,
            filled($consultation->diagnosis) ? trim((string) $consultation->diagnosis) : null,
            filled($consultation->plan) ? trim((string) $consultation->plan) : null,
            filled($consultation->follow_up_instructions) ? trim((string) $consultation->follow_up_instructions) : null,
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
     * Mark incoming messages as read and return the thread for the signed-in viewer.
     *
     * @return list<array{id: int, body: string, name: string, initials: string, from_patient: bool, mine: bool, time: string, day: string, read: bool}>
     */
    public static function messageFeed(Appointment $appointment, int $viewerId): array
    {
        $appointment->messages()
            ->where('sender_id', '!=', $viewerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $appointment->unsetRelation('messages');
        $appointment->load([
            'messages' => fn ($query) => $query->with('sender')->orderBy('id'),
        ]);

        return self::messages($appointment);
    }

    /**
     * @return array{id: int, body: string, name: string, initials: string, from_patient: bool, mine: bool, time: string, day: string, read: bool}
     */
    public static function presentMessage(AppointmentMessage $message, Appointment $appointment): array
    {
        $created = $message->created_at;
        $name = $message->sender?->name ?: 'User';

        return [
            'id' => (int) $message->id,
            'body' => (string) $message->body,
            'name' => $name,
            'initials' => self::initials($name, 'U'),
            'from_patient' => (int) $message->sender_id === (int) $appointment->patient_id,
            'mine' => (int) $message->sender_id === (int) auth()->id(),
            'time' => $created ? $created->format('g:i A') : '',
            'day' => $created ? self::chatDay($created) : '',
            'read' => $message->read_at !== null,
        ];
    }

    /**
     * @return list<array{id: int, body: string, name: string, initials: string, from_patient: bool, mine: bool, time: string, day: string, read: bool}>
     */
    private static function messages(Appointment $appointment): array
    {
        if (! $appointment->relationLoaded('messages')) {
            return [];
        }

        return $appointment->messages
            ->map(fn (AppointmentMessage $message) => self::presentMessage($message, $appointment))
            ->values()
            ->all();
    }

    private static function chatDay(Carbon $created): string
    {
        if ($created->isToday()) {
            return 'Today';
        }

        if ($created->isYesterday()) {
            return 'Yesterday';
        }

        return $created->format('j M Y');
    }

    /**
     * @return array<string, mixed>
     */
    private static function presentAnalysis(?Consultation $consultation, string $role, ?Appointment $appointment = null): array
    {
        $stored = is_array($consultation?->ai_analysis) ? $consultation->ai_analysis : [];
        $gemini = self::geminiAnalysis($stored);
        $files = $appointment?->reports?->values() ?? collect();
        $summary = $gemini
            ? $gemini['overallSummary']
            : (filled($consultation?->ai_summary) ? (string) $consultation->ai_summary : null);

        $reviews = [];

        if ($gemini) {
            foreach ($gemini['reportAnalyses'] as $position => $report) {
                $index = (int) ($report['fileIndex'] ?? 0);
                $matched = $files->get(($index > 0 ? $index : $position + 1) - 1);
                $group = $report['groupName'] ?: ($matched?->group_name ?: null);
                $fileName = self::reportLabel(
                    $report['fileName'] ?? null,
                    $matched?->original_name,
                    $group,
                    $index > 0 ? $index : $position + 1,
                );
                $fileUrl = $matched && $appointment
                    ? route($role === 'doctor' ? 'doctor.appointments.reports.show' : 'patient.bookings.reports.show', [$appointment, $matched])
                    : null;
                $reading = self::withoutStorageNames($report['analysis']);
                $findings = $report['findings'];
                $warnings = $report['warnings'];

                if ($reading === null && $findings === [] && $warnings === [] && $report['reportUrls'] === []) {
                    continue;
                }

                $reviews[] = [
                    'group' => $group,
                    'file' => $fileName,
                    'file_url' => $fileUrl,
                    'reading' => $reading,
                    'attention' => null,
                    'urls' => $report['reportUrls'],
                    'warnings' => $warnings,
                    'findings' => $findings,
                ];
            }
        } else {
            foreach (is_array($stored['report_reviews'] ?? null) ? $stored['report_reviews'] : [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $reviews[] = [
                    'group' => self::text($item['group'] ?? null),
                    'file' => self::text($item['file'] ?? null),
                    'file_url' => null,
                    'reading' => self::text($item['reading'] ?? null),
                    'attention' => self::text($item['attention'] ?? null),
                    'urls' => [],
                    'warnings' => [],
                    'findings' => [],
                ];
            }
        }

        $recommendations = $gemini
            ? $gemini['doctorRecommendations']
            : self::bulletList($stored['clinical_recommendations'] ?? []);
        $points = $gemini ? [] : self::bulletList($stored['points_for_the_doctor'] ?? '');
        $history = $gemini ? null : self::text($stored['history_relevant_to_this_doctor'] ?? null);
        $inventory = $gemini ? null : self::text($stored['report_inventory'] ?? null);
        $picture = $gemini ? null : self::text($stored['clinical_picture'] ?? null);
        $brief = $role === 'doctor' ? self::text($stored['referral_brief'] ?? null) : null;
        $overview = $gemini['patientInfo'] ?? null;
        $overviewFilled = is_array($overview) && collect($overview)->contains(fn ($value) => filled($value));
        $ready = filled($summary)
            || $reviews !== []
            || $recommendations !== []
            || $points !== []
            || filled($history)
            || filled($inventory)
            || filled($picture)
            || filled($brief)
            || $overviewFilled;
        $status = $consultation?->ai_status;

        if (! $ready) {
            $notice = match ($status) {
                'pending' => 'Analysis is running in the background. Refresh this page in a moment.',
                'failed' => 'Analysis could not be completed. The doctor can start it again from AI Analysis.',
                default => 'AI analysis is not available for this booking yet.',
            };
        } elseif ($status === 'pending') {
            $notice = 'A newer analysis is running in the background.';
        } else {
            $notice = null;
        }

        return [
            'ready' => $ready,
            'notice' => $notice,
            'state_label' => match (true) {
                $status === 'pending' => 'Preparing',
                $ready => 'Ready',
                $status === 'failed' => 'Unavailable',
                default => 'Not ready',
            },
            'generated' => $consultation?->ai_generated_at
                ? Carbon::parse($consultation->ai_generated_at)->format('j M Y, g:i A')
                : '',
            'overview' => $overviewFilled ? $overview : null,
            'summary' => filled($summary) ? $summary : null,
            'history' => $history,
            'inventory' => $inventory,
            'picture' => $picture,
            'reviews' => $reviews,
            'points' => $points,
            'recommendations' => $recommendations,
            'referral_brief' => $brief,
        ];
    }

    /**
     * @return list<string>
     */
    private static function bulletList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split("/\r\n|\n|\r/", $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $value,
        )));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function geminiAnalysis(mixed $analysis): ?array
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
                'fileIndex' => isset($item['fileIndex']) ? (int) $item['fileIndex'] : null,
                'groupName' => filled($item['groupName'] ?? null) ? (string) $item['groupName'] : null,
                'fileName' => self::text($item['fileName'] ?? null),
                'reportUrls' => array_values(array_filter(
                    is_array($urls) ? $urls : [],
                    fn ($url) => is_string($url) && $url !== '',
                )),
                'analysis' => filled($item['analysis'] ?? null) ? (string) $item['analysis'] : null,
                'findings' => self::findings($item['findings'] ?? []),
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

    /**
     * @return list<array{name: string, value: string, unit: string, range: string, flag: string}>
     */
    private static function findings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = self::text($item['name'] ?? null);
            $result = self::text($item['value'] ?? null);

            if ($name === null && $result === null) {
                continue;
            }

            $flag = strtolower((string) self::text($item['flag'] ?? null));

            if (! in_array($flag, ['low', 'high', 'normal', 'unknown'], true)) {
                $flag = 'unknown';
            }

            $rows[] = [
                'name' => $name ?? '',
                'value' => $result ?? '',
                'unit' => self::text($item['unit'] ?? null) ?? '',
                'range' => self::text($item['range'] ?? null) ?? '',
                'flag' => $flag,
            ];
        }

        return $rows;
    }

    private static function reportLabel(?string $stored, ?string $original, ?string $group, int $number): ?string
    {
        foreach ([$original, $stored] as $name) {
            $name = self::text($name);

            if ($name !== null && ! self::machineFileName($name)) {
                return $name;
            }
        }

        if (filled($group)) {
            return $group.' file '.$number;
        }

        return null;
    }

    private static function machineFileName(string $name): bool
    {
        $base = pathinfo($name, PATHINFO_FILENAME);

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $base) === 1
            || preg_match('/^[A-Za-z0-9]{32,}$/', $base) === 1;
    }

    private static function withoutStorageNames(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/\s*\([0-9a-f]{8}-[0-9a-f-]{27,}(?:\.[A-Za-z0-9]+)?\)/i', '', $value) ?? $value;
        $clean = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}(?:\.[A-Za-z0-9]+)?\b/i', 'the uploaded file', $clean) ?? $clean;
        $clean = trim(preg_replace('/\s{2,}/', ' ', $clean) ?? $clean);

        return $clean !== '' ? $clean : null;
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
