<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorReviews
{
    public const PER_PAGE = 10;

    /**
     * @return array{
     *     reviews: list<array<string, mixed>>,
     *     average: string,
     *     total: int,
     *     page: int,
     *     lastPage: int,
     *     perPage: int
     * }
     */
    public static function feed(Doctor $doctor, int $page = 1): array
    {
        $page = max(1, $page);

        $paginator = $doctor->reviews()
            ->with('user:id,name,photo')
            ->latest('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        if ($page > $paginator->lastPage()) {
            $paginator = $doctor->reviews()
                ->with('user:id,name,photo')
                ->latest('id')
                ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $paginator->lastPage()));
        }

        return [
            'reviews' => $paginator->getCollection()
                ->map(fn (DoctorReview $review) => self::present($review))
                ->values()
                ->all(),
            'average' => number_format((float) $doctor->average_rating, 1),
            'total' => (int) $paginator->total(),
            'page' => $paginator->currentPage(),
            'lastPage' => max(1, $paginator->lastPage()),
            'perPage' => self::PER_PAGE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function page(Doctor $doctor, ?User $viewer, int $page = 1): array
    {
        $mine = null;

        if ($viewer?->role === 'patient') {
            $mine = DoctorReview::query()
                ->where('doctor_id', $doctor->id)
                ->where('user_id', $viewer->id)
                ->first();
        }

        $pending = session('review_draft.'.$doctor->id);
        $pendingRating = is_array($pending) ? (int) ($pending['rating'] ?? 0) : 0;
        $pendingBody = is_array($pending) ? trim((string) ($pending['body'] ?? '')) : '';

        if ($mine && $pendingBody !== '') {
            session()->forget('review_draft.'.$doctor->id);
            $pendingBody = '';
            $pendingRating = 0;
        }

        $rating = (int) old('rating', $mine?->rating ?? $pendingRating);
        $body = old('reviewText', $mine?->body ?? $pendingBody);
        $hasDraft = old('reviewText') !== null
            || old('rating') !== null
            || ($mine === null && $pendingBody !== '');
        $feed = self::feed($doctor, $page);
        $canUseForm = $viewer === null || $viewer->role === 'patient';
        $errorBag = session('errors');
        $formOpen = $canUseForm && (
            ($errorBag && ($errorBag->has('rating') || $errorBag->has('reviewText')))
            || ($mine === null && $pendingBody !== '')
        );

        return [
            ...$feed,
            'doctorId' => $doctor->id,
            'canWrite' => $viewer?->role === 'patient',
            'showForm' => $canUseForm,
            'formOpen' => $formOpen,
            'prevUrl' => $feed['page'] > 1 ? self::profilePageUrl($doctor, $feed['page'] - 1) : null,
            'nextUrl' => $feed['page'] < $feed['lastPage'] ? self::profilePageUrl($doctor, $feed['page'] + 1) : null,
            'pollUrl' => route('doctors.reviews.index', $doctor),
            'sendUrl' => route('doctors.reviews.store', $doctor),
            'loginUrl' => route('doctors.reviews.begin', $doctor),
            'mine' => ($mine || $hasDraft) ? [
                'id' => $mine?->id,
                'rating' => $rating,
                'body' => is_string($body) ? $body : '',
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(DoctorReview $review): array
    {
        $user = $review->user;
        $name = $user?->name ?: 'Patient';
        $edited = $review->updated_at !== null
            && $review->created_at !== null
            && $review->updated_at->gt($review->created_at);
        $when = $edited ? $review->updated_at : $review->created_at;

        return [
            'id' => $review->id,
            'rating' => (int) $review->rating,
            'body' => $review->body,
            'name' => $name,
            'photo' => AppointmentPage::photoUrl($user?->photo),
            'initials' => AppointmentPage::initials($name, 'P'),
            'date' => $when?->format('F j, Y') ?? '',
            'edited' => $edited,
            'updatedAt' => $review->updated_at?->toIso8601String(),
        ];
    }

    public static function record(Doctor $doctor, User $patient, int $rating, string $body): DoctorReview
    {
        $review = DB::transaction(function () use ($doctor, $patient, $rating, $body) {
            $review = DoctorReview::query()->updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'user_id' => $patient->id,
                ],
                [
                    'rating' => $rating,
                    'body' => $body,
                ],
            );

            self::refreshSummary($doctor);

            return $review;
        });

        $review->setRelation('user', $patient);

        return $review;
    }

    public static function profilePageUrl(Doctor $doctor, int $page): ?string
    {
        $slug = $doctor->user?->slug;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return route('doctors.show', [
            'slug' => $slug,
            'feedback' => 1,
            'reviews_page' => max(1, $page),
        ]);
    }

    public static function refreshSummary(Doctor $doctor): void
    {
        $stats = DoctorReview::query()
            ->where('doctor_id', $doctor->id)
            ->toBase()
            ->selectRaw('COUNT(*) as aggregate_total, AVG(rating) as aggregate_average')
            ->first();

        $doctor->forceFill([
            'total_rating' => (int) ($stats->aggregate_total ?? 0),
            'average_rating' => round((float) ($stats->aggregate_average ?? 0), 2),
        ])->save();
    }
}
