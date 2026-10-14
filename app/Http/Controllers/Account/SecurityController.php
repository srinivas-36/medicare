<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.security', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        Auth::logoutOtherDevices($validated['current_password']);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('status', 'Password updated.');
    }
}
