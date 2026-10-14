<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.notifications', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $url = data_get($record->data, 'url');

        return $url ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return back()->with('status', 'Notifications marked as read.');
    }
}
