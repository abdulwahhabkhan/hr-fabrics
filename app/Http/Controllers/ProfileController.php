<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Rules\NotInPasswordHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/ProfileIndex', [
            'user' => $user,
            'role' => Role::find($user->role_id),
            'twoFactorEnabled' => ! is_null($user->two_factor_confirmed_at),
            'twoFactorPending' => ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at),
            'passkeys' => $user->passkeys()->orderByDesc('created_at')->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', new NotInPasswordHistory($request->user())],
        ]);

        $request->user()->changePassword($validated['password']);

        return Redirect::route('profile.index')->with(['success' => 'Password updated successfully']);
    }
}
