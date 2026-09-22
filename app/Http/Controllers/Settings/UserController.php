<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = User::with(['role'])->filter($request)->paginate(100);

        return Inertia::render('Settings/User/UserIndex',
            [
                'users' => $users,
                'filters' => $request->only('search'),
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $roles = Role::orderBy('name')->get();

        return Inertia::render('Settings/User/UserForm',
            [
                'user' => null,
                'roles' => $roles,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'max:50'],
            'email' => [
                'required', 'max:150',
                Rule::unique('users'),
            ],
            'password' => [
                'required', 'confirmed',
                Password::min(8)->mixedCase(),
            ],
            'role_id' => ['required', 'integer'],
        ]);
        $data = $request->only('name', 'email', 'role_id', 'password');
        $data['password'] = Hash::make($data['password']);
        $data['password_changed_at'] = now();
        User::create($data);

        return Redirect::route('settings.users.index')
            ->with(['success' => 'User created successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $roles = Role::orderBy('name')->get();

        return Inertia::render('Settings/User/UserForm',
            ['user' => $user, 'roles' => $roles]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->only('name', 'password', 'email', 'role_id');

        $rules = [
            'name' => ['required', 'max:50'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users')->ignore($user),
            ],
            'password' => 'sometimes|required|confirmed',
            'role_id' => ['required', 'integer'],
        ];
        if (! $data['password']) {
            unset($rules['password']);
        }
        $request->validate($rules);
        $user->update($request->only('name', 'email', 'role_id'));
        if ($data['password']) {
            $user->changePassword($data['password']);
        }

        return Redirect::route('settings.users.index')
            ->with(['success' => 'User updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->active = ! $user->active; // Deactivate the user instead of deleting
        $user->save();

        return Redirect::route('settings.users.index')->with('success', 'User status updated successfully.');
    }
}
