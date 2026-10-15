<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('public.contact', [
            'subjects' => ContactMessage::SUBJECTS,
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        if (filled($request->input('company'))) {
            return redirect()
                ->route('contact')
                ->with('status', 'Thanks. We received your message and will reply by email.');
        }

        $message = ContactMessage::query()->create([
            'user_id' => $request->user()?->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'subject' => $request->validated('subject'),
            'body' => $request->validated('message'),
        ]);

        $inbox = config('services.contact.inbox');

        if (is_string($inbox) && $inbox !== '') {
            try {
                Mail::to($inbox)->send(new ContactMessageReceived($message));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('contact')
            ->with('status', 'Thanks. We received your message and will reply by email.');
    }
}
