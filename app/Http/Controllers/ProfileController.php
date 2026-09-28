<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the authenticated user's profile information.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/ProfileIndex', [
            'user' => $user->only(['name', 'email']),
            'role' => Role::find($user->role_id)?->only(['name']),
        ]);
    }
}
