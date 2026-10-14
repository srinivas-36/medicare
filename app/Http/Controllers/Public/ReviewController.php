<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorReviewRequest;
use App\Models\Doctor;
use App\Support\DoctorReviews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, Doctor $doctor): JsonResponse
    {
        $doctor->refresh();

        return response()
            ->json(DoctorReviews::feed($doctor, max(1, (int) $request->query('page', 1))))
            ->header('Cache-Control', 'no-store');
    }

    public function begin(Request $request, Doctor $doctor): RedirectResponse
    {
        $slug = $doctor->user?->slug;

        abort_unless(is_string($slug) && $slug !== '', 404);

        $back = route('doctors.show', ['slug' => $slug, 'feedback' => 1]);

        if ($request->user()) {
            return redirect()->to($back);
        }

        redirect()->setIntendedUrl($back);

        return redirect()->route('login');
    }

    public function store(StoreDoctorReviewRequest $request, Doctor $doctor): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            $request->session()->put('review_draft.'.$doctor->id, [
                'rating' => (int) $request->validated('rating'),
                'body' => $request->validated('reviewText'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sign in to submit your review.',
                ], 401);
            }

            return redirect()->route('doctors.reviews.begin', $doctor);
        }

        abort_unless($user->role === 'patient', 403);

        $review = DoctorReviews::record(
            $doctor,
            $user,
            (int) $request->validated('rating'),
            $request->validated('reviewText'),
        );

        $request->session()->forget('review_draft.'.$doctor->id);

        $created = $review->wasRecentlyCreated;
        $status = $created
            ? 'Your review is on this profile.'
            : 'Your review was updated.';

        if ($request->expectsJson()) {
            return response()->json([
                'review' => DoctorReviews::present($review),
                'average' => number_format((float) $doctor->average_rating, 1),
                'total' => (int) $doctor->total_rating,
                'updated' => ! $created,
            ]);
        }

        return back()->with('status', $status);
    }
}
