<?php

namespace App\Http\Controllers;

use App\Rules\NotInPasswordHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the security settings (password, two-factor, passkeys).
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Security', [
            'twoFactorEnabled' => ! is_null($user->two_factor_confirmed_at),
            'twoFactorPending' => ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at),
            'passkeys' => $user->passkeys()->orderByDesc('created_at')->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', new NotInPasswordHistory($request->user())],
        ]);

        $request->user()->changePassword($validated['password']);

        return back()->with(['success' => 'Password updated successfully']);
    }
}
